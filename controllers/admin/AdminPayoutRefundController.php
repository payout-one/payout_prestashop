<?php

class AdminPayoutRefundController extends ModuleAdminController
{
    /**
     * process ajax refund
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function displayAjaxRefund(): void
    {
        if (!($this->module instanceof Payout)) {
            $this->renderJsonResult(false, $this->module->l('Module instance is not payout', 'refund'));
            return;
        }

        if (!Tools::getIsset("id_order")) {
            $this->renderJsonResult(false, $this->module->l('Order id is not in request', 'refund'));
            return;
        }

        if (!Tools::getIsset("amount")) {
            $this->renderJsonResult(false, $this->module->l('Amount is not in request', 'refund'));
            return;
        }

        $result = $this->module->refundOrder((int)Tools::getValue("id_order"), (float)Tools::getValue("amount"));
        if ($result['success']) {
            $message = json_decode($result['message']);
            if ($message->fullRefundAchieved) {
                Payout::setPayoutNotifications([], [], [$message->message], true);
            }
        }

        $this->renderJsonResult($result['success'], $result['message']);
    }

    /**
     * find remaining refundable amount for order
     *
     * @return void
     * @throws PrestaShopException
     */
    public function displayAjaxRefundableAmount(): void
    {
        if (!($this->module instanceof Payout)) {
            $this->renderJsonResult(false, $this->module->l('Module instance is not payout', 'refund'));
            return;
        }

        if (!Tools::getIsset("id_order")) {
            $this->renderJsonResult(false, $this->module->l('Order id is not in request', 'refund'));
            return;
        }

        $payoutOrder = Payout::getPayoutOrder((int)Tools::getValue("id_order"));
        if (!$payoutOrder) {
            $this->renderJsonResult(false, $this->module->l('Order is not payout order', 'refund'));
            return;
        }

        $currency = new Currency((new Order((int)Tools::getValue("id_order")))->id_currency);

        $totalAmount = (float)$payoutOrder['amount'];
        $refunded = Payout::getPayoutRefunds((int)$payoutOrder['id_checkout']);
        /** @noinspection PhpDeprecationInspection */
        $currencyPrecision = version_compare(_PS_VERSION_, '1.7.7', '<') ? (int)_PS_PRICE_DISPLAY_PRECISION_ : $currency->precision;
        $refundable = Tools::ps_round($totalAmount - $refunded, $currencyPrecision);
        $message = [
            'total_amount' => $totalAmount,
            'refunded' => $refunded,
            'refundable' => $refundable,
            'refundPossible' => $refundable > 0,
        ];
        $this->renderJsonResult(true, json_encode($message));
    }

    /**
     * check api credentials
     *
     * @return void
     * @throws PrestaShopException
     */
    public function displayAjaxRefundRecords(): void
    {
        if (!($this->module instanceof Payout)) {
            $this->renderJsonResult(false, $this->module->l('Module instance is not payout', 'refund'));
            return;
        }

        if (!Tools::getIsset("id_order")) {
            $this->renderJsonResult(false, $this->module->l('Order id is not in request', 'refund'));
            return;
        }

        $payoutOrder = Payout::getPayoutOrder((int)Tools::getValue("id_order"));
        if (!$payoutOrder) {
            $this->renderJsonResult(false, $this->module->l('Order is not payout order', 'refund'));
            return;
        }

        $records = $this->module->getPayoutOrderRefundRecords((int)Tools::getValue("id_order"));
        $recordsCount = !empty($records) ? count($records) : 0;

        $this->renderJsonResult(
            true,
            json_encode(
                [
                    'records_html' => $this->module->getPayoutOrderRefundRecordsTemplate((int)Tools::getValue("id_order"), $payoutOrder['checkout_status'] == Payout::CHECKOUT_STATE_SUCCEEDED),
                    'records_count' => $recordsCount,
                ]
            )
        );
    }

    /**
     * render json result
     *
     * @param bool $result
     * @param string $message
     *
     * @return void
     * @throws PrestaShopException
     */
    private function renderJsonResult(bool $result, string $message): void
    {
        ob_end_clean();
        header('Content-Type: application/json');

        $responseArray = [
            'result' => $result,
            'message' => $message
        ];

        if ($this->module->isPrestashop1_6) {
            /** @noinspection PhpDeprecationInspection */
            die(Tools::jsonEncode($responseArray));
        } else {
            $this->ajaxRender(json_encode($responseArray));
        }
    }
}
