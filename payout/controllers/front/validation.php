<?php
/**
* 2007-2021 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

ini_set('display_errors',1);
class PayoutValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        /*
         * If the module is not active anymore, no need to process anything.
         */
		
		$cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
		/*
         * Get Order data .
         */
		$context = Context::getContext();
		$cust_id = $cart->id_customer;
		
		$customer = $context->customer;
		$cart_id = $cart->id;
		$customer_id = $customer->id;
		
		$currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		

        /*
         * Restore the context from the $cart_id & the $customer_id to process the validation properly.
         */
        Context::getContext()->cart = new Cart((int) $cart_id);
        Context::getContext()->customer = new Customer((int) $customer_id);
        Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language((int) Context::getContext()->customer->id_lang);

        $secure_key = Context::getContext()->customer->secure_key;

        if ($this->isValidOrder() === true) {
            $payment_status = Configuration::get('PS_OS_PAYMENT');
            $message = null;
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');

            /**
             * Add a message to explain why the order has not been validated
             */
            $message = $this->module->l('An error occurred while processing payment');
        }

        $module_name = $this->module->displayName;
        $currency_id = (int) Context::getContext()->currency->id;

        //$validateOrder = $this->module->validateOrder($cart_id, $payment_status, $total, $module_name, $message, array(), $currency_id, false, $secure_key);
		
		$this->getStandardCheckoutFormFields($context);
    }

    protected function isValidOrder()
    {
        /*
         * Add your checks right there
         */
        return true;
    }
	
	/**
     * This is where we compile data posted by the form to Payout
     * @return array
     */
    public function getStandardCheckoutFormFields($context)
    {
		// https://www.kiddzworld.com/en/module/payout/confirmation
		$notifyUrl = Configuration::get('PAYOUT_NOTIFY_URL');
		$clientId = Configuration::get('PAYOUT_CLIENT_ID');
		$secret = Configuration::get('PAYOUT_SECRET');
		$sandbox = Configuration::get('PAYOUT_MODE');
		//$clientId = 'a53f5407-d43c-4e59-846d-b7c21489a41a';//$this->getConfigData( 'payout_id' );
		//$secret = 'y47H9GpVFu9rJexEkD5wU3osE6n07yaNXXJE2QVJ9z1RBFwHh8sR-65C3-0-dLUi';//$this->getConfigData( 'encryption_key' );
		
		$config = array(
			'client_id' => $clientId,
			'client_secret' => $secret,
			'sandbox' => $sandbox
		);
		
		//echo '<pre>';print_r($config);die;
		require_once(dirname(__FILE__) . '/../../classes/init.php');
		
		$payout = new Client($config);
		
		$customer = $context->customer;	
		$cart = $context->cart;
		$currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		
		$url = $context->shop->getBaseURL(true).'module/payout/confirmation?cart_id='.$cart->id;
		
		/********** format billing and shipping Address **********/
		$delivery_address = $cart->id_address_delivery;
		$invoice_address = $cart->id_address_invoice;
		$dAddress = new Address(intval($delivery_address));
		$iAddress = new Address(intval($invoice_address));
		$billing_country_code = new Country(intval($iAddress->id_country));
		$bcc = $billing_country_code->iso_code;
		$shipping_country_code = new Country(intval($dAddress->id_country));
		$scc = $shipping_country_code->iso_code;
		$billing_address = array(
			'name' => $iAddress->firstname .' ' .$iAddress->lastname,
			'address_line_1' => $iAddress->address1,
			'address_line_2' => $iAddress->address2,
			'postal_code' => $iAddress->postcode,
			'country_code' => $bcc,
			'city' => $iAddress->city			
		);
		
		$shipping_address = array(
			'name' => $dAddress->firstname .' ' .$dAddress->lastname,
			'address_line_1' => $dAddress->address1,
			'address_line_2' => $dAddress->address2,
			'postal_code' => $dAddress->postcode,
			'country_code' => $scc,
			'city' => $dAddress->city			
		);		
		
		$products = $cart->getProducts(true);
		foreach($products as $product){
			$productAttributes[] = array(
				'name' => $product['name'],
				'unit_price' => round($product['price_with_reduction'],2),
				'quantity' => $product['cart_quantity'],
			
			);
		}
		//echo '<pre>';print_r($currency);die;
		$checkout_data = array(
			'amount' => $total,
			'currency' => $currency->iso_code,
			'customer' => [
				'first_name' => $customer->firstname,
				'last_name' => $customer->lastname,
				'email' =>  $customer->email
			],
			'billing_address' => json_encode($billing_address),
			'shipping_address' => json_encode($shipping_address),
			'products' => json_encode($productAttributes),
			'external_id' => $cart->id,
			'redirect_url' => $url
			
		);
		
		$response = $payout->createCheckout($checkout_data);
		
		$checkoutUrl = $response->checkout_url;
		header("Location: $checkoutUrl");
		exit(0);
		//Tools::redirect('checkoutUrl');
    }
}
