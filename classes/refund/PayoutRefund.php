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

require_once(dirname(__FILE__) . '/../../classes/PayoutClient.php');

class PayoutRefund
{
    private $module;

    public function __construct(Payout $module)
    {
        $this->module = $module;
    }

    /**
     * create payout client
     *
     * @return PayoutClient|string
     */
    private function createPayoutClient($moduleConfigurations)
    {
        try {
            return new PayoutClient($moduleConfigurations);
        } catch (\Exception $e) {
            Payout::addLog($this->module->l('Payout api client initialization failed for this context. Error:', 'checkout') . ' ' . $e->getMessage(), 3);
            return $e->getMessage();
        }
    }

    /**
     * @param int $orderId
     * @param int|null $amount
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function refund(int $orderId, float $amount): array
    {
        $employee = Context::getContext()->employee;
        if (!isset($employee) || empty($employee->id)) {
            $message = $this->getPayoutRefundErrorLog($this->module->l('Can not find employee', 'refund'));
            Payout::addLog($message, 2, null, 'Order', $orderId);
            return ['success' => false, 'message' => $message];
        }

        $order = new Order($orderId);
        if (empty($order->id)) {
            $message = $this->getPayoutRefundErrorLog($this->module->l('Order id is not valid', 'refund'));
            Payout::addLog($message, 2, null, 'Order', $orderId);
            return ['success' => false, 'message' => $message];
        }

        $payoutOrder = Payout::getPayoutOrder($orderId);
        if (!$payoutOrder) {
            $message = $this->getPayoutRefundErrorLog($this->module->l('Order is not payout order', 'refund'));
            Payout::addLog($message, 2, null, 'Order', $orderId);
            return ['success' => false, 'message' => $message];
        }

        $currency = new Currency($order->id_currency);
        /** @noinspection PhpDeprecationInspection */
        $currencyPrecision = version_compare(_PS_VERSION_, '1.7.7', '<') ? (int)_PS_PRICE_DISPLAY_PRECISION_ : $currency->precision;
        $amountToRefund = Tools::ps_round($amount, $currencyPrecision);
        if ($amountToRefund <= 0) {
            $message = $this->getPayoutRefundErrorLog($this->module->l('Amount need to be positive, amount:', 'refund') . ' ' . $amountToRefund);
            Payout::addLog($message, 2, null, 'Order', $orderId);
            return ['success' => false, 'message' => $message];
        }

        $refundedSum = Payout::getPayoutRefunds((int)$payoutOrder['id_checkout']);

        $amountToBeRefundedTotal = Tools::ps_round($refundedSum + $amountToRefund, $currencyPrecision);
        $totalPaidAmount = Tools::ps_round((float)$payoutOrder['amount'], $currencyPrecision);
        if ($amountToBeRefundedTotal > $totalPaidAmount) {
            $message = $this->getPayoutRefundErrorLog(
                $this->module->l('Total refunded sum would be higher than payed price, refunded sum:', 'refund') . ' ' . $refundedSum . ', '
                . $this->module->l('additional amount to refund:', 'refund') . ' ' . $amountToRefund . ', '
                . $this->module->l('total refunded amount with this additional amount:', 'refund') . ' ' . $amountToBeRefundedTotal . ', '
                . $this->module->l('total payed amount', 'refund') . ' ' . $totalPaidAmount
            );
            Payout::addLog($message, 2, null, 'Order', $orderId);
            return ['success' => false, 'message' => $message];
        }

        $moduleConfigurations = $this->module->getModuleConfigs($order->id_shop, true);
        if (empty($moduleConfigurations[Payout::PAYOUT_CLIENT_ID]) || empty($moduleConfigurations[Payout::PAYOUT_SECRET])) {
            $message = $this->getPayoutRefundErrorLog($this->module->l("Client id or secret is not filled in payout module configuration for context of order shop", 'refund'));
            Payout::addLog($message, 3, null, 'Order', $orderId);
            return ['success' => false, 'message' => $message];
        }

        $payoutClient = $this->createPayoutClient($moduleConfigurations);
        if (!($payoutClient instanceof PayoutClient)) {
            return ['success' => false, 'message' => $payoutClient];
        }

        $data['checkout_id'] = $payoutOrder['id_checkout'];
        $data['iban'] = '';
        $data['statement_descriptor'] = '';

        $amount_cents = intval(round($amountToRefund * 100));
        $data['amount'] = $amount_cents;
        try {
            $response = $payoutClient->refund($data, $orderId, (new Currency($order->id_currency))->iso_code);
            $responseAmount = (float)($response->amount / 100);
            $fullRefundAchieved = $amountToBeRefundedTotal == $totalPaidAmount;
            if ($amountToBeRefundedTotal == $totalPaidAmount) {
                $history = new OrderHistory();
                $history->id_order = $order->id;
                $history->changeIdOrderState(Configuration::get('PS_OS_REFUND'), $order->id);
                $history->add();
            }
            Db::getInstance()->insert(
                Payout::PAYOUT_REFUND_TABLE, [
                    'id_checkout' => (int)$payoutOrder['id_checkout'],
                    'id_employee' => $employee->id,
                    'employee_info' => $employee->firstname . ' ' . $employee->lastname . '(' . $employee->email . ')',
                    'id_withdrawal' => (int)$response->id,
                    'amount' => $responseAmount,
                    'response' => pSQL(json_encode($response)),
                    'date' => date('Y-m-d H:i:s'),
                ]
            );

            $message = [
                'message' => $this->module->l("Successful refund for order:", 'refund') . ' ' . $orderId . ', '
                    . $this->module->l("amount:", 'refund') . ' ' . $responseAmount . ', '
                    . $this->module->l("currency:", 'refund') . ' ' . $response->currency . ', '
                    . $this->module->l("iban:", 'refund') . ' ' . $response->iban . ', '
                    . $this->module->l("withdrawal id:", 'refund') . ' ' . $response->id . '. '
                    . $this->module->l("Check 'Payout refund' tab down below for more info", 'refund') . '.',
                'fullRefundAchieved' => $fullRefundAchieved
            ];

            return ['success' => true, 'message' => json_encode($message)];
        } catch (Exception $e) {
            $message = $this->getPayoutRefundErrorLog(
                $this->module->l('Problem occurred while refunding', 'refund') . ', '
                . $this->module->l("message:", 'refund') . ' ' . $e->getMessage()
            );
            Payout::addLog($message, 2, null, "Order", $orderId);
            return ['success' => false, 'message' => $message];
        }
    }

    private function getPayoutRefundErrorLog($log): string
    {
        return $this->module->l('Payout refund error', 'refund') . ' - ' . $log;
    }
}
