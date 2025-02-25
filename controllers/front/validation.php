<?php
/**
 * 2007-2022 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2022 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
require_once(dirname(__FILE__) . '/abstractcheckout.php');

class PayoutValidationModuleFrontController extends PayoutAbstractCheckoutFrontController
{
    /**
     * specific post process for validation controller
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function postProcessSpecific(): void
    {
        $this->validateAndCreateCheckout();
    }

    /**
     * validating of cart, creating of order, precheckout, checkout
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function validateAndCreateCheckout(): void
    {
        $cart = $this->context->cart;

        if (!isset($cart) ||
            $cart->id_customer == 0 ||
            $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0) {
            Tools::redirect('index.php?controller=order');
            return;
        }

        // validate customer's currency, country, payout module configurations
        if (!$this->validateCustomerContext() || !$this->validateCheckoutApiConfig()) {
            return;
        }

        $payoutClient = $this->createPayoutClient();

        // if payoutClient is false, redirect is set from createPayoutClient function
        if (!$payoutClient) {
            return;
        }

        $customer = new Customer($cart->id_customer);
        $secureKey = $customer->secure_key;
        $moduleName = $this->module->displayName;
        $currencyId = $cart->id_currency;

        // validating and creating order
        try {
            $this->module->validateOrder(
                $cart->id,
                $this->moduleConfigurations[Payout::PAYOUT_OS_PENDING],
                $cart->getOrderTotal(),
                $moduleName,
                null,
                [],
                $currencyId,
                false,
                $secureKey
            );
        } catch (Exception $e) {
            Payout::addLog($this->module->l("Error while validating order with cart:", 'checkout') . ' ' . $e->getMessage(), 2, null, "Cart", $cart->id);
            $this->payoutErrors[] = $this->module->l("Problem occurred while validating order", 'checkout');
            $this->payoutRedirectWithNotifications('index.php?controller=order');
            return;
        }

        if (version_compare(_PS_VERSION_, '1.7.1.0', '>=')) {
            $orderId = Order::getIdByCartId((int)$cart->id);
        } else {
            /** @noinspection PhpDeprecationInspection */
            $orderId = Order::getOrderByCartId((int)$cart->id);
        }

        // create checkout from order
        $this->createCheckoutForOrder($payoutClient, new Order($orderId));
    }
}
