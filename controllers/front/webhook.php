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

require_once(dirname(__FILE__) . '/abstract.php');

class PayoutWebhookModuleFrontController extends PayoutAbstractFrontController
{
    /**
     * specific post process for webhook controller
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function postProcessSpecific(): void
    {
        $this->parseWebhook(json_decode(Tools::file_get_contents('php://input'), true));
        exit();
    }

    /**
     * parse webhook
     *
     * @param array $webhook
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function parseWebhook(array $webhook): void
    {
        // check if webhook is checkout webhook
        if (!isset($webhook['object']) || $webhook['object'] != "webhook" || !isset($webhook['data']) || !isset($webhook['data']['object']) || $webhook['data']['object'] != "checkout" || !isset($webhook['type']) || explode(".", $webhook['type'])[0] != "checkout") {
            http_response_code(404);
            return;
        }

        // check if payout order for checkout from webhook exists
        $payoutOrder = $this->getPayoutOrderByCheckoutId((int)$webhook['data']['id']);
        if (!$payoutOrder) {
            Payout::addLog($this->module->l('No payout order found for payout checkout webhook', 'webhook'), 2, null, 'Checkout', (int)$webhook['data']['id']);
            http_response_code(404);
            return;
        }

        if ($payoutOrder['id_order'] != $webhook['external_id']) {
            Payout::addLog($this->module->l('Prestashop order id is different from received external_id, external_id:', 'webhook') . ' ' . $webhook['external_id'], 2, null, 'Order', (int)$payoutOrder['id_order']);
            http_response_code(404);
            return;
        }

        Shop::setContext(Shop::CONTEXT_SHOP, (new Order((int)$payoutOrder['id_order']))->id_shop);
        $payoutSecret = Configuration::get(Payout::PAYOUT_SECRET);
        // check if payout secret is filled
        if (empty($payoutSecret)) {
            Payout::addLog($this->module->l('Can not verify webhook: payout secret is not configured for this context', 'webhook'), 3);
            http_response_code(401);
            return;
        }

        // verify webhook signature
        if (
            !PayoutClient::verifySignature(
                [$webhook['external_id'], $webhook['type']],
                $webhook['nonce'],
                $payoutSecret,
                $webhook['signature']
            )
        ) {
            Payout::addLog($this->module->l('No valid signature for payout checkout webhook', 'webhook'), 3, null, 'Checkout', (int)$webhook['data']['id']);
            http_response_code(400);
            return;
        }

        // update payout order with webhook data
        Db::getInstance()->update(Payout::PAYOUT_ORDER_TABLE, [
            'checkout_status' => pSQL($webhook['data']['status']),
            'payment_status' => isset($webhook['data']['payment']) ? pSQL($webhook['data']['payment']['status']) : null,
        ], 'id_checkout = ' . (int)$webhook['data']['id']);

        $order = new Order($payoutOrder['id_order']);
        $currentOrderState = $order->getCurrentOrderState();

        // log webhook to log table
        Db::getInstance()->insert(Payout::PAYOUT_LOG_TABLE, [
            'id_order' => (int)$order->id,
            'id_checkout' => (int)$webhook['data']['id'],
            'data' => pSQL(json_encode($webhook)),
            'data_type' => 'webhook',
            'type' => pSQL($webhook['type']),
            'date_added' => date('Y-m-d H:i:s'),
        ]);

        if ($currentOrderState->id != Configuration::get('PS_OS_PAYMENT') && $webhook['data']['status'] == Payout::CHECKOUT_STATE_SUCCEEDED) {
            // if current order state is pending and response status is succeeded -> set payment accepted order status
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), $order->id);
            $history->add();
        } else if (!in_array($currentOrderState->id, [Configuration::get('PS_OS_PAYMENT'), $this->moduleConfigurations[Payout::PAYOUT_OS_EXPIRED]]) && $webhook['data']['status'] == Payout::CHECKOUT_STATE_EXPIRED) {
            // if current order state is not payment accepted and response status is expired -> set expired order status
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $history->changeIdOrderState($this->moduleConfigurations[Payout::PAYOUT_OS_EXPIRED], $order->id);
            $history->add();
        }
    }
}
