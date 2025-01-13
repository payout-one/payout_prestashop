<?php


require_once(dirname(__FILE__) . '/abstract.php');

abstract class PayoutAbstractCheckoutFrontController extends PayoutAbstractFrontController
{
    /**
     * validate payout module configurations
     *
     * @return bool
     */
    protected function validateCheckoutApiConfig(): bool
    {
        if (empty($this->moduleConfigurations[Payout::PAYOUT_CLIENT_ID]) || empty($this->moduleConfigurations[Payout::PAYOUT_SECRET])) {
            $this->addLog($this->module->l("Client id or secret is not filled in payout module configuration!", 'checkout'), 3, null, 'Cart', Context::getContext()->cart->id);
            $this->payoutErrors[] = $this->module->l("Problem occurred while initializing payout payment, contact support please.", 'checkout');
            $this->payoutRedirectWithNotifications("index.php?controller=order");
            return false;
        }

        return true;
    }

    /**
     * validating of customer's currency, country
     *
     * @return void
     */
    protected function validateCustomerContext(): bool
    {
        if (!$this->moduleConfigurations[Payout::PAYOUT_ALLOWED_FOR_CURRENT_CONTEXT_CART]) {
            $this->addLog($this->module->l("Payout payment is not allowed for country or currency of customer", 'checkout'), 2, null, 'Cart', Context::getContext()->cart->id);
            $this->payoutErrors[] = $this->module->l("Problem occurred while initializing payout payment, contact support please.", 'checkout');
            $this->payoutRedirectWithNotifications("index.php?controller=order");
            return false;
        }

        return true;
    }

    /**
     * creating checkout for order
     *
     * @param PayoutClient $payoutClient
     * @param Order $order
     * @param bool $repeatedInitialization
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */
    protected function createCheckoutForOrder(PayoutClient $payoutClient, Order $order, bool $repeatedInitialization = false): void
    {
        $cart = new Cart($order->id_cart);
        $checkoutData = $this->createCheckoutData($order, new Currency($order->id_currency));

        $preCheckout = $this->getPreCheckoutForOrder($order->id);
        if ($preCheckout) {
            // if precheckout for order exists, check if there is assigned checkout to id
            $idempotencyKey = $preCheckout['idempotency_key'];
            if ($preCheckout['id_checkout'] != null) {
                // if checkout already exists -> log it
                $this->addLog($this->module->l("Checkout already exists for order", 'checkout'), 2, null, "Order", $order->id);
                $error = $this->module->l("Problem occurred while initializing payment, try it again down below.", 'checkout');
                if ($repeatedInitialization) {
                    $this->redirectToOrderDetail($order, $error);
                } else {
                    $this->redirectToOrderConfirmation($order->id, $cart->id, $error);
                }
            }
        } else {
            // precheckout do not exists -> create one
            $idempotencyKey = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
            try {
                $preCheckoutInsertResult = Db::getInstance()->insert(
                    Payout::PAYOUT_PRE_CHECKOUT_TABLE, [
                        'idempotency_key' => $idempotencyKey,
                        'id_order' => (int)$order->id,]
                );
            } catch (PrestaShopDatabaseException $e) {
                $exceptionMessage = $e->getMessage();
            } finally {
                if (isset($exceptionMessage) || (isset($preCheckoutInsertResult) && !$preCheckoutInsertResult)) {
                    $additionalMessage = isset($exceptionMessage) ? ' : ' . $exceptionMessage : '';
                    $this->addLog($this->module->l("Error while inserting to precheckout table", 'checkout') . $additionalMessage, 2, null, "Order", $order->id);
                    $error = $this->module->l("Problem occurred while initializing payment, try it again down below.", 'checkout');
                    if ($repeatedInitialization) {
                        $this->redirectToOrderDetail($order, $error);
                    } else {
                        $this->redirectToOrderConfirmation($order->id, $cart->id, $error);
                    }
                    return;
                }
            }
        }

        try {
            $checkoutData['idempotency_key'] = $idempotencyKey;
            $response = $payoutClient->createCheckout($checkoutData);
            $this->createOrUpdatePayoutOrder($checkoutData, $response, $order, true);

            // update checkout for precheckout record
            Db::getInstance()->update(Payout::PAYOUT_PRE_CHECKOUT_TABLE, [
                'id_checkout' => (int)$response->id
            ], 'idempotency_key = "' . $idempotencyKey . '" AND id_order = ' . (int)$order->id);

            // if it is repeated initialization(first initialization attempt before order confirmation page was unsuccessful)
            // -> redirect directly to payment gateway
            if ($repeatedInitialization) {
                Tools::redirect($response->checkout_url);
            } else {
                // redirect to order confirmation
                $this->redirectToOrderConfirmation($order->id, $cart->id);
            }
        } catch (Exception $e) {
            $this->addLog($this->module->l("Error while creating checkout:", 'checkout') . ' ' . $e->getMessage(), 2, null, "Order", $order->id);
            $error = $this->module->l("Problem occurred while initializing payment, try it again down below.", 'checkout');
            if ($repeatedInitialization) {
                $this->redirectToOrderDetail($order, $error);
            } else {
                $this->redirectToOrderConfirmation($order->id, $cart->id, $error);
            }
        }
    }

    /**
     * validating of cart, creating of order, precheckout, checkout
     *
     * @return array|bool
     * @throws PrestaShopDatabaseException
     */
    private function getPreCheckoutForOrder($orderId)
    {
        $preCheckout = DB::getInstance()->executeS(
            'SELECT idempotency_key, id_checkout, id_order FROM `' . _DB_PREFIX_ . Payout::PAYOUT_PRE_CHECKOUT_TABLE . '` WHERE id_order = ' . (int)$orderId
        );
        if (!empty($preCheckout)) {
            return $preCheckout[0];
        }
        return false;
    }

    /**
     * create checkout data array for order
     *
     * @param Order $order
     * @param Currency $currency
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createCheckoutData(Order $order, Currency $currency): array
    {
        $productAttributes = [];
        $customer = $order->getCustomer();

        foreach ($order->getProducts() as $product) {
            $productAttributes[] = [
                'name' => $product['product_name'],
                'unit_price' => "" . intval($product['product_price_wt'] * 100),
                'quantity' => (int)$product['product_quantity'],
            ];
        }

        if ($order->total_shipping > 0) {
            $productAttributes[] = [
                'name' => $this->module->l('shipping cost', 'checkout'),
                'unit_price' => "" . intval($order->total_shipping * 100),
                'quantity' => 1,
            ];
        }

        if ($order->total_wrapping > 0) {
            $productAttributes[] = [
                'name' => $this->module->l('wrapping cost', 'checkout'),
                'unit_price' => "" . intval($order->total_wrapping * 100),
                'quantity' => 1,
            ];
        }

        return [
            'amount' => intval($order->total_paid * 100),
            'currency' => $currency->iso_code,
            'customer' => [
                'first_name' => $customer->firstname,
                'last_name' => $customer->lastname,
                'email' => $customer->email
            ],
            'billing_address' => $this->createCheckoutAddress(new Address($order->id_address_invoice)),
            'shipping_address' => $this->createCheckoutAddress(new Address($order->id_address_delivery)),
            'products' => $productAttributes,
            'external_id' => "$order->id",
            'redirect_url' => $this->getOrderDetailUrl($order),
        ];
    }

    /**
     * redirect customer to order detail with possible error / info message
     *
     * @param Order $order
     * @param string|null $errorMessage
     * @param string|null $infoMessage
     *
     * @return void
     */
    protected function redirectToOrderDetail(Order $order, string $errorMessage = null, string $infoMessage = null): void
    {
        $this->redirectWithMessages($this->getOrderDetailUrl($order), $errorMessage, $infoMessage);
    }

    /**
     * get order detail url based on customer type - guest or not guest
     *
     * @param Order $order
     *
     * @return string
     */
    private function getOrderDetailUrl(Order $order): string
    {
        $customer = $order->getCustomer();
        if ($customer->is_guest) {
            return $this->context->link->getPageLink(
                "guest-tracking", null, null, 'order_reference=' . $order->reference . '&email=' . $customer->email . ($this->module->isPrestashop1_6 ? "&submitGuestTracking" : "")
            );
        } else {
            return $this->context->link->getPageLink(
                "order-detail", null, null, 'id_order=' . $order->id
            );
        }
    }

    /**
     * redirect customer to order confirmation with possible error / info message
     *
     * @param int $orderId
     * @param int $cartId
     * @param string|null $errorMessage
     * @param string|null $infoMessage
     *
     * @return void
     */
    private function redirectToOrderConfirmation(int $orderId, int $cartId, string $errorMessage = null, string $infoMessage = null): void
    {
        $this->redirectWithMessages(
            'index.php?controller=order-confirmation&id_cart=' . $cartId . '&id_module=' . $this->module->id .
            '&id_order=' . $orderId . '&key=' . Context::getContext()->customer->secure_key,
            $errorMessage,
            $infoMessage
        );
    }

    /**
     * redirect customer to url with possible error / info message
     *
     * @param string $url
     * @param string|null $errorMessage
     * @param string|null $infoMessage
     *
     * @return void
     */
    private function redirectWithMessages(string $url, string $errorMessage = null, string $infoMessage = null): void
    {
        if (isset($errorMessage)) {
            $this->payoutErrors[] = $errorMessage;
        }
        if (isset($infoMessage)) {
            $this->payoutInfo[] = $infoMessage;
        }
        $this->payoutRedirectWithNotifications($url);
    }

    /**
     * create payout client
     *
     * @return PayoutClient|bool
     */
    protected function createPayoutClient()
    {
        try {
            return new PayoutClient($this->moduleConfigurations);
        } catch (\Exception $e) {
            $this->addLog($this->module->l('Payout api client initialization failed for this context. Error:', 'checkout') . ' ' . $e->getMessage(), 3);
            $this->payoutErrors[] = $this->module->l("Server side problem, contact shop support, please.", 'checkout');
            $this->payoutRedirectWithNotifications("index.php?controller=order");
            return false;
        }
    }

    /**
     * create payout checkout address from prestashop address
     *
     * @param Address $address
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createCheckoutAddress(Address $address): array
    {
        $billing_country_code = new Country($address->id_country);
        $bcc = $billing_country_code->iso_code;
        return [
            'name' => $address->firstname . ' ' . $address->lastname,
            'address_line_1' => $address->address1,
            'address_line_2' => $address->address2,
            'postal_code' => $address->postcode,
            'country_code' => $bcc,
            'city' => $address->city,
        ];
    }
}
