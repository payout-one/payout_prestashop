<?php
require_once(dirname(__FILE__) . '/abstractcheckout.php');

class PayoutRepeatModuleFrontController extends PayoutAbstractCheckoutFrontController
{
    /**
     * specific post process for repeat controller
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function postProcessSpecific(): void
    {
        if (Tools::getIsset('id_order') && Tools::getIsset('key')) {
            $this->repeatCheckout(Tools::getValue('id_order'), Tools::getValue('key'));
        } else {
            Tools::redirect('index.php?controller=order');
        }
    }

    /**
     * repeat checkout
     *
     * @param int $orderId
     * @param string $secureKey
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function repeatCheckout(int $orderId, string $secureKey): void
    {
        $payoutClient = $this->createPayoutClient();

        // if payoutClient is false, redirect is set from createPayoutClient function
        if ($payoutClient) {
            $order = new Order($orderId);

            if ($order->module != $this->module->name || $order->secure_key != $secureKey) {
                Tools::redirect("");
                return;
            }

            // validate payout module configurations
            if (!$this->validateCheckoutApiConfig()) {
                return;
            }

            $payoutOrder = Payout::getPayoutOrder($orderId);
            if ($payoutOrder) {
                // if checkout for order already exists -> check if it is still processing
                if (in_array($payoutOrder['checkout_status'], [Payout::CHECKOUT_STATE_PROCESSING, Payout::CHECKOUT_STATE_EXPIRED])) {
                    $checkoutUrl = $payoutOrder['checkout_url'];
                    try {
                        // retrieve checkout to check if state was not changed
                        $retrieve = $payoutClient->retrieveCheckout($payoutOrder['id_checkout']);
                        $this->createOrUpdatePayoutOrder(json_decode($payoutOrder['checkout_data'], true), $retrieve, $order);
                        $checkoutUrl = $retrieve->checkout_url;
                    } catch (Exception $e) {
                        Payout::addLog($this->module->l("Error while retrieving checkout:", 'repeat') . ' ' . $e->getMessage(), 2, null, "Order", $orderId);
                    }

                    // if retrieve failed or status is still processing, redirect customer to payment gateway
                    if (!isset($retrieve) || in_array($retrieve->status, [Payout::CHECKOUT_STATE_PROCESSING, Payout::CHECKOUT_STATE_EXPIRED])) {
                        Tools::redirect($checkoutUrl);
                        die(0);
                    }
                }
                // to repeat payment state needed to be processing - customer clicked from old loaded page or retrieved status was no processing -> notify customer with message
                $this->redirectToOrderDetail($order, null, $this->module->l('Status of payment was changed.', 'repeat'));
            } else {
                // if checkout for order not exists -> create one
                $this->createCheckoutForOrder($payoutClient, $order, true);
            }
        }
    }
}
