<?php

abstract class PayoutAbstractFrontController extends ModuleFrontController
{
    protected $moduleConfigurations;
    protected $payoutErrors = [];
    protected $payoutInfo = [];

    /**
     * init for all front controller - include all needed php classes
     *
     * @return void
     * @throws PrestaShopException
     */
    public function init(): void
    {
        parent::init();
        require_once(dirname(__FILE__) . '/../../classes/init.php');
    }

    /**
     * post process for all payout front controllers - check if module is payout, then execute specific post process for child controller
     * load module configurations to field
     *
     * @return void
     */
    public function postProcess(): void
    {
        if (!($this->module instanceof Payout)) {
            Tools::redirect('index.php?controller=order');
            return;
        }
        $this->moduleConfigurations = $this->module->getModuleConfigFields();
        $this->postProcessSpecific();
    }

    /**
     * specific post process for child controller
     *
     * @return void
     */
    protected abstract function postProcessSpecific(): void;

    /**
     * get payout order by checkout id
     *
     * @return array|bool
     * @throws PrestaShopDatabaseException
     */
    protected function getPayoutOrderByCheckoutId($checkoutId)
    {
        $payoutOrder = DB::getInstance()->executeS(
            'SELECT id_checkout, id_order, amount, checkout_data, checkout_status, checkout_url, payment_status, external_id, idempotency_key FROM `' . _DB_PREFIX_ . Payout::PAYOUT_ORDER_TABLE . '` WHERE id_checkout = ' . (int)$checkoutId
        );
        if (!empty($payoutOrder)) {
            return $payoutOrder[0];
        }
        return false;
    }

    /**
     * create payout order if not exists, update if exists,log if it is new checkout, check for possible order state changes
     *
     * @param array $checkoutData
     * @param mixed $checkoutResponse
     * @param Order $order
     * @param bool $newCheckout
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function createOrUpdatePayoutOrder(array $checkoutData, $checkoutResponse, Order $order, bool $newCheckout = false): void
    {
        if ($this->getPayoutOrderByCheckoutId((int)$checkoutResponse->id)) {
            // payout order existed -> update
            Db::getInstance()->update(Payout::PAYOUT_ORDER_TABLE, [
                'checkout_status' => pSQL($checkoutResponse->status),
                'checkout_url' => pSQL($checkoutResponse->checkout_url),
                'payment_status' => isset($checkoutResponse->payment) ? pSQL($checkoutResponse->payment->status) : null,
            ], 'id_checkout = ' . (int)$checkoutResponse->id);
        } else {
            // payout order need to be created
            Db::getInstance()->insert(Payout::PAYOUT_ORDER_TABLE, [
                'id_checkout' => (int)$checkoutResponse->id,
                'id_order' => (int)$order->id,
                'amount' => $order->total_paid,
                'checkout_data' => pSQL(json_encode($checkoutData)),
                'checkout_status' => pSQL($checkoutResponse->status),
                'checkout_url' => pSQL($checkoutResponse->checkout_url),
                'payment_status' => isset($checkoutResponse->payment) ? pSQL($checkoutResponse->payment->status) : null,
                'external_id' => pSQL($checkoutData['external_id']),
                'idempotency_key' => pSQL($checkoutResponse->idempotency_key),
            ]);
        }

        // if new checkout is created log it to log table
        if ($newCheckout) {
            Db::getInstance()->insert(Payout::PAYOUT_LOG_TABLE, [
                'id_order' => (int)$order->id,
                'id_checkout' => (int)$checkoutResponse->id,
                'data' => pSQL(json_encode($checkoutResponse)),
                'data_type' => 'checkout_response',
                'type' => 'checkout.created',
                'date_added' => date('Y-m-d H:i:s'),
            ]);
        }

        $currentOrderState = $order->getCurrentOrderState();
        if ($currentOrderState->id != Configuration::get('PS_OS_PAYMENT') && $checkoutResponse->status == Payout::CHECKOUT_STATE_SUCCEEDED) {
            // if current order state is pending and response status is succeeded -> set payment accepted order status and log to log table
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), $order->id);
            $history->add();
            Db::getInstance()->insert(Payout::PAYOUT_LOG_TABLE, [
                'id_order' => (int)$order->id,
                'id_checkout' => (int)$checkoutResponse->id,
                'data' => pSQL(json_encode($checkoutResponse)),
                'data_type' => 'checkout_response',
                'type' => 'checkout.succeeded',
                'date_added' => date('Y-m-d H:i:s'),
            ]);
        } else if (!in_array($currentOrderState->id, [Configuration::get('PS_OS_PAYMENT'), $this->moduleConfigurations[Payout::PAYOUT_OS_EXPIRED]]) && $checkoutResponse->status == Payout::CHECKOUT_STATE_EXPIRED) {
            // if current order state is not payment accepted and response status is expired -> set expired order status and log to log table
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $history->changeIdOrderState($this->moduleConfigurations[Payout::PAYOUT_OS_EXPIRED], $order->id);
            $history->add();
            Db::getInstance()->insert(Payout::PAYOUT_LOG_TABLE, [
                'id_order' => (int)$order->id,
                'id_checkout' => (int)$checkoutResponse->id,
                'data' => pSQL(json_encode($checkoutResponse)),
                'data_type' => 'checkout_response',
                'type' => 'checkout.expired',
                'date_added' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * redirect with notifications - method was created because of prestashop 1.6 to be able redirecting with notifications
     *
     * @param string $url
     *
     * @return void
     */
    protected function payoutRedirectWithNotifications(string $url): void
    {
        if ($this->module->isPrestashop1_6) {
            // save notifications to session or cookies like it is in prestashop 1.7
            Payout::setPayoutNotifications($this->payoutErrors, $this->payoutInfo, []);
            Tools::redirect($url);
        } else {
            // merge native notification with payout notifications and continue as normal
            $this->errors = array_merge($this->errors, $this->payoutErrors);
            $this->info = array_merge($this->info, $this->payoutInfo);
            parent::redirectWithNotifications($url);
        }
    }
}
