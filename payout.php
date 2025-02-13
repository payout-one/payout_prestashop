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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/refund/PayoutRefund.php');

class Payout extends PaymentModule
{
    private $moduleConfigs;
    private $moduleHooks;

    /**
     * @var PayoutRefund
     */
    private $payoutRefund;


    public const API_URL = 'https://app.payout.one/api/v1/';
    public const API_URL_SANDBOX = 'https://sandbox.payout.one/api/v1/';

    public const AUTHENTICATE_TIMEOUT = 2;
    public const CREATE_CHECKOUT_TIMEOUT = 5;
    public const RETRIEVE_CHECKOUT_TIMEOUT = 5;
    public const REFUND_CHECKOUT_TIMEOUT = 5;

    public const PAYOUT_SANDBOX_MODE = "PAYOUT_SANDBOX_MODE";
    public const PAYOUT_NOTIFY_URL = "PAYOUT_NOTIFY_URL";
    public const PAYOUT_CLIENT_ID = "PAYOUT_CLIENT_ID";
    public const PAYOUT_SECRET = "PAYOUT_SECRET";
    public const PAYOUT_OS_PENDING = "PAYOUT_OS_PENDING";
    public const PAYOUT_OS_EXPIRED = "PAYOUT_OS_EXPIRED";
    public const PAYOUT_ALLOWED_FOR_CURRENT_CONTEXT_CART = "PAYOUT_ALLOWED_FOR_CURRENT_CONTEXT";

    public const PAYOUT_LOG_TABLE = "payout_log";
    public const PAYOUT_ORDER_TABLE = "payout_order";
    public const PAYOUT_PRE_CHECKOUT_TABLE = "payout_pre_checkout";
    public const PAYOUT_REFUND_TABLE = "payout_refund";


    public const CHECKOUT_STATE_PROCESSING = "processing";
    public const CHECKOUT_STATE_REQUIRES_AUTHORIZATION = "requires_authorization";
    public const CHECKOUT_STATE_SUCCEEDED = "succeeded";
    public const CHECKOUT_STATE_EXPIRED = "expired";

    public $isPrestashop1_6;

    /**
     * @var array|string[]
     */
    private $allowed_countries;

    /**
     * @var array|string[]
     */
    private $allowed_currencies;

    public function __construct()
    {
        $this->name = 'payout';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'Payout';
        $this->need_instance = 0;

        $this->isPrestashop1_6 = version_compare(_PS_VERSION_, "1.7", "<");

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->allowed_countries = ['AT', 'AUT', '040', 'BE', 'BEL', '056', 'BG', 'BGR', '100', 'HR', 'HRV', '191', 'CY', 'CYP', '196', 'CZ', 'CZE', '203', 'DK', 'DNK', '208', 'EE', 'EST', '233', 'FI', 'FIN', '246', 'FR', 'FRA', '250', 'DE', 'DEU', '276', 'GR', 'GRC', '300', 'HU', 'HUN', '348', 'IE', 'IRL', '372', 'IT', 'ITA', '380', 'LV', 'LVA', '428', 'LT', 'LTU', '440', 'LU', 'LUX', '442', 'MT', 'MLT', '470', 'NL', 'NLD', '528', 'PL', 'POL', '616', 'PT', 'PRT', '620', 'RO', 'ROU', '642', 'SK', 'SVK', '703', 'SI', 'SVN', '705', 'ES', 'ESP', '724', 'SE', 'SWE', '752'];

        $this->allowed_currencies = ['EUR', 'CZK', 'HUF', 'PLN', 'RON', 'BGN'];

        $this->moduleConfigs = $this->getModuleConfigs();

        $this->displayName = $this->l('Payout Payment');

        $this->description = $this->l('Pay Via Payout Payment');

        $this->confirmUninstall = $this->l('Are you sure?');

        $this->ps_versions_compliancy = ['min' => '1.6.1', 'max' => _PS_VERSION_];

        $this->moduleHooks = [
            "header",
            "displayTop",
            "payment",
            "paymentOptions",
            "displayOrderConfirmation",
            "displayOrderDetail",
            "displayAdminOrderTabLink",
            "displayAdminOrderTabOrder",
            "displayAdminOrderTabContent",
            "displayAdminOrderContentOrder",
            "displayBackOfficeHeader",
            "displayAdminOrderTop",
            "displayAdminOrder",
            "actionGetAdminOrderButtons",
            "actionOrderSlipAdd",
        ];
        $this->payoutRefund = new PayoutRefund($this);
    }

    /**
     * calculate and get module configs
     *
     * @param int|null $shopId
     * @param bool $onlyKeyValues map only key and value
     *
     * @return array|array[]
     * @throws PrestaShopException
     */
    public function getModuleConfigs(int $shopId = null, bool $onlyKeyValues = false): array
    {
        if (isset($shopId)) {
            $contextType = Shop::getContext();
            $contextShop = Shop::getContextShopID();
            $contextGroup = Shop::getContextShopGroupID();
            Shop::setContext(Shop::CONTEXT_SHOP, $shopId);
        }

        if ($this->isPrestashop1_6) {
            $sandboxConfigValueIsSet = Configuration::hasKey(self::PAYOUT_SANDBOX_MODE, null, null, Shop::getContextShopID())
                || Configuration::hasKey(self::PAYOUT_SANDBOX_MODE, null, Shop::getContextShopID())
                || Configuration::hasKey(self::PAYOUT_SANDBOX_MODE);
            $sandboxConfigValue = $sandboxConfigValueIsSet ? Configuration::get(self::PAYOUT_SANDBOX_MODE) : true;
        } else {
            $sandboxConfigValue = Configuration::get(self::PAYOUT_SANDBOX_MODE, null, null, null, null) ?? true;
        }

        $webHookUrl = str_replace('http://', 'https://', $this->context->link->getModuleLink('payout', 'webhook'));
        $configs = [
            self::PAYOUT_SANDBOX_MODE => ['form_field' => true, 'label' => 'Enable Sandbox Mode', 'required' => true, 'readonly' => false, 'is_bool' => true, 'value' => $sandboxConfigValue],
            self::PAYOUT_NOTIFY_URL => ['form_field' => true, 'label' => 'Notify Url', 'readonly' => true, 'value' => $webHookUrl],
            self::PAYOUT_CLIENT_ID => ['form_field' => true, 'label' => 'Client Id', 'required' => true, 'readonly' => false, 'value' => (string)Configuration::get(self::PAYOUT_CLIENT_ID)],
            self::PAYOUT_SECRET => ['form_field' => true, 'label' => 'Secret', 'required' => true, 'readonly' => false, 'value' => (string)Configuration::get(self::PAYOUT_SECRET)],
            self::PAYOUT_OS_PENDING => ['form_field' => false, 'value' => (int)Configuration::get(self::PAYOUT_OS_PENDING, null, 0, 0)],
            self::PAYOUT_OS_EXPIRED => ['form_field' => false, 'value' => (int)Configuration::get(self::PAYOUT_OS_EXPIRED, null, 0, 0)],
            self::PAYOUT_ALLOWED_FOR_CURRENT_CONTEXT_CART => ['form_field' => false, 'value' => $this->isPaymentAllowedForCurrentContextCart()],
        ];

        if (isset($shopId)) {
            Shop::setContext($contextType, $contextType == Shop::CONTEXT_SHOP ? $contextShop : $contextGroup);
        }

        return $onlyKeyValues ? $this->getModuleConfigFieldsFromArray($configs) : $configs;
    }

    /**
     * check if payment is allowed for current context cart
     *
     * @return bool
     */
    private function isPaymentAllowedForCurrentContextCart(): bool
    {
        $shopId = Shop::getContextShopID();
        if (!isset($shopId)) {
            return false;
        }

        if (isset($this->context->cart)) {
            $cart = $this->context->cart;
        } else if (!empty((int)$this->context->cookie->id_cart)) {
            $cart = new Cart((int)$this->context->cookie->id_cart);
        } else {
            return false;
        }

        $deliveryAddressCountry = new Country((new Address($cart->id_address_delivery))->id_country);
        $currency = new Currency($cart->id_currency);
        return
            isset($currency->iso_code)
            && isset($deliveryAddressCountry->iso_code)
            && in_array(strtoupper($currency->iso_code), $this->allowed_currencies)
            && in_array(strtoupper($deliveryAddressCountry->iso_code), $this->allowed_countries);
    }

    /**
     * create pending order status
     *
     * @return int|bool
     */
    public function createPendingStatus()
    {
        $orderState = new OrderState();
        $orderState->send_email = 0;
        $orderState->module_name = $this->name;
        $orderState->invoice = 0;
        $orderState->color = '#4169E1';
        $orderState->logable = 0;
        $orderState->shipped = 0;
        $orderState->unremovable = 1;
        $orderState->delivery = 0;
        $orderState->hidden = 0;
        $orderState->paid = 0;
        $orderState->pdf_delivery = 0;
        $orderState->pdf_invoice = 0;
        $orderState->deleted = 0;
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            if (strtolower($language['iso_code']) == 'sk') {
                $orderState->name[$language['id_lang']] = 'Čakanie na platbu Payout';
            } elseif (strtolower($language['iso_code']) == 'cs') {
                $orderState->name[$language['id_lang']] = 'Čekání na platbu Payout';
            } elseif (strtolower($language['iso_code']) == 'en') {
                $orderState->name[$language['id_lang']] = 'Awaiting Payout payment';
            } else {
                $orderState->name[$language['id_lang']] = 'Awaiting Payout payment';
            }
        }
        try {
            if ($orderState->add()) {
                return $orderState->id;
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * create expired order status
     *
     * @return int|bool
     */
    public function createExpiredStatus()
    {
        $orderState = new OrderState();
        $orderState->send_email = 0;
        $orderState->module_name = $this->name;
        $orderState->invoice = 0;
        $orderState->color = '#ff0000';
        $orderState->logable = 0;
        $orderState->shipped = 0;
        $orderState->unremovable = 1;
        $orderState->delivery = 0;
        $orderState->hidden = 0;
        $orderState->paid = 0;
        $orderState->pdf_delivery = 0;
        $orderState->pdf_invoice = 0;
        $orderState->deleted = 0;
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            if (strtolower($language['iso_code']) == 'sk') {
                $orderState->name[$language['id_lang']] = 'Expirovaná Payout platba';
            } elseif (strtolower($language['iso_code']) == 'cs') {
                $orderState->name[$language['id_lang']] = 'Expirovaná Payout platba';
            } elseif (strtolower($language['iso_code']) == 'en') {
                $orderState->name[$language['id_lang']] = 'Expired Payout payment';
            } else {
                $orderState->name[$language['id_lang']] = 'Expired Payout payment';
            }
        }
        try {
            if ($orderState->add()) {
                return $orderState->id;
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * get module configuration fields
     *
     * @param bool $onlyForm
     *
     * @return array
     */
    public function getModuleConfigFields(bool $onlyForm = false): array
    {
        return $this->getModuleConfigFieldsFromArray($this->moduleConfigs, $onlyForm);
    }

    /**
     * get module configuration fields
     *
     * @param array $fields
     * @param bool $onlyForm
     *
     * @return array
     */
    private function getModuleConfigFieldsFromArray(array $fields, bool $onlyForm = false): array
    {
        return array_map(
            function ($field) {
                return $field['value'];
            },
            array_filter($fields, function ($field) use ($onlyForm) {
                return !$onlyForm || $field['form_field'] === true;
            }));
    }

    /**
     * install tab for className
     *
     * @param string $className
     *
     * @return bool
     */
    public function installTab(string $className): bool
    {
        $found = $this->findTabIdByName($className);
        if ($found) {
            return true;
        }
        $tab = new Tab();
        $tab->class_name = $className;
        $tab->name = [];
        $languages = Language::getLanguages();
        foreach ($languages as $lang)
            $tab->name[$lang['id_lang']] = $className;

        $tab->id_parent = -1;
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * uninstall tab by className
     *
     * @param string $className
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallTab(string $className): bool
    {
        $tab = new Tab($this->findTabIdByName($className));
        if (Validate::isLoadedObject($tab)) {
            try {
                return $tab->delete();
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * find tab by name
     *
     * @param string $name
     *
     * @return int|null
     */
    public function findTabIdByName(string $name): ?int
    {
        if (version_compare(_PS_VERSION_, "1.7.1", "<")) {
            /** @noinspection PhpDeprecationInspection */
            $idTab = (int)Tab::getIdFromClassName($name);
        } else {
            $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
            $idTab = $tabRepository->findOneIdByClassName($name);
        }

        return $idTab;
    }

    /**
     * install module
     *
     * @return bool
     */
    public function install(): bool
    {
        if (!extension_loaded('curl')) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        if (
            !$this->moduleConfigs[self::PAYOUT_OS_PENDING]['value'] &&
            (!($expiredStatusId = $this->createPendingStatus())
                || !$this->updateModuleConfiguration(self::PAYOUT_OS_PENDING, $expiredStatusId, true))
        ) {
            return false;
        }

        if (
            !$this->moduleConfigs[self::PAYOUT_OS_EXPIRED]['value'] &&
            (!($expiredStatusId = $this->createExpiredStatus())
                || !$this->updateModuleConfiguration(self::PAYOUT_OS_EXPIRED, $expiredStatusId, true))
        ) {
            return false;
        }

        return parent::install() &&
            $this->createDbTables() &&
            $this->registerHooks() &&
            $this->installTab('AdminPayoutConfiguration') &&
            $this->installTab('AdminPayoutRefund');
    }

    /**
     * update module configuration in db and in object field
     *
     * @param string $key
     * @param mixed $value
     * @param bool $allShopsContext
     *
     * @return bool
     */
    private function updateModuleConfiguration(string $key, $value, bool $allShopsContext = false): bool
    {
        $id_shop = $allShopsContext ? 0 : null;
        $id_shop_group = $allShopsContext ? 0 : null;
        if (array_key_exists($key, $this->moduleConfigs) && Configuration::updateValue($key, $value, false, $id_shop_group, $id_shop)) {
            $this->moduleConfigs[$key]['value'] = $value;
            return true;
        }

        return false;
    }

    /**
     * register hooks needed for module
     *
     * @return bool
     */
    public function registerHooks(): bool
    {
        foreach ($this->getHooksUnregistered() as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }
        return true;
    }

    /**
     * find all unregistered hooks for module
     *
     * @return array return the unregistered hooks
     */
    private function getHooksUnregistered(): array
    {
        if (!isset($this->id)) {
            return $this->moduleHooks;
        }

        $hooksUnregistered = [];

        if ($this->isPrestashop1_6) {
            $hooks = Hook::getHookModuleList();

            foreach ($this->moduleHooks as $hookName) {
                try {
                    $hookId = Hook::getIdByName($hookName);
                } catch (PrestaShopDatabaseException $e) {
                }
                if (!isset($hookId) || !isset($hooks[$hookId]) || !isset($hooks[$hookId][$this->id])) {
                    $hooksUnregistered[] = $hookName;
                }
            }
        } else {
            foreach ($this->moduleHooks as $hookName) {
                $alias = '';

                try {
                    $alias = Hook::getNameById(Hook::getIdByName($hookName));
                } catch (Exception $e) {
                }

                $hookName = empty($alias) ? $hookName : $alias;
                if (Hook::isModuleRegisteredOnHook($this, $hookName, $this->context->shop->id)) {
                    continue;
                }

                $hooksUnregistered[] = $hookName;
            }
        }

        return $hooksUnregistered;
    }

    /**
     * uninstall module
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        if (
            !$this->softDeleteOrderState($this->moduleConfigs[self::PAYOUT_OS_PENDING]['value']) ||
            !$this->softDeleteOrderState($this->moduleConfigs[self::PAYOUT_OS_EXPIRED]['value'])
        ) {
            return false;
        }

        foreach ($this->moduleConfigs as $name => $config) {
            if (!Configuration::deleteByName($name)) {
                return false;
            }
        }

        return $this->uninstallTab('AdminPayoutConfiguration')
            && $this->uninstallTab('AdminPayoutRefund')
            && parent::uninstall();
    }

    /**
     * soft delete order state - deleted column = 1
     *
     * @param int $orderStateId
     *
     * @return bool
     */
    private function softDeleteOrderState(int $orderStateId): bool
    {
        try {
            $foundOrderState = new OrderState($orderStateId);
        } catch (Exception $e) {
            return false;
        }

        if ($orderStateId && $orderStateId == $foundOrderState->id) {
            if ($this->isPrestashop1_6) {
                $foundOrderState->deleted = 1;
                try {
                    return $foundOrderState->save();
                } catch (PrestaShopException $e) {
                    return false;
                }
            } else {
                try {
                    return $foundOrderState->softDelete();
                } catch (Exception $e) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * create payout db tables
     *
     * @return bool
     */
    private function createDbTables(): bool
    {
        return Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::PAYOUT_LOG_TABLE . '` (
              `id_payout_log` int(20) NOT NULL AUTO_INCREMENT,
              `id_order` int(10) unsigned NOT NULL,
              `id_checkout` int(20) unsigned NOT NULL,
              `data` text NOT NULL,
              `data_type` text NOT NULL,
              `type` varchar(30) NOT NULL,
              `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_payout_log`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
            ) &&
            Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::PAYOUT_ORDER_TABLE . '` (
              `id_checkout` int(20) unsigned NOT NULL,
              `id_order` int(10) unsigned NOT NULL,
              `amount` decimal(20,6) NOT NULL,
              `checkout_data` TEXT NOT NULL,
              `checkout_status` TEXT NOT NULL,
              `checkout_url` TEXT NOT NULL,
              `payment_status` TEXT DEFAULT NULL,
              `external_id` TEXT NOT NULL,
              `idempotency_key` varchar(50) NOT NULL,
              PRIMARY KEY (`id_checkout`),
              UNIQUE (`id_order`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
            ) &&
            Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::PAYOUT_PRE_CHECKOUT_TABLE . '` (
              `idempotency_key` varchar(50) NOT NULL, 
              `id_checkout` int(20) unsigned NULL,
              `id_order` int(10) unsigned NOT NULL,
              PRIMARY KEY (`idempotency_key`),
              UNIQUE (`id_checkout`), 
              UNIQUE (`id_order`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
            ) && Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::PAYOUT_REFUND_TABLE . '` (
              `id_refund` int(20) NOT NULL AUTO_INCREMENT,
              `id_checkout` int(20) unsigned NOT NULL,            
              `id_employee` int(10) unsigned NOT NULL,
              `employee_info` text NOT NULL,
              `id_withdrawal` int(20) unsigned NOT NULL,                            
              `amount` decimal(20,6) NOT NULL,
              `response` text NOT NULL,              
              `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_refund`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
            );
    }

    /**
     * find payout order by order id
     *
     * @param int $orderId
     *
     * @return array|bool
     * @throws PrestaShopDatabaseException
     */
    public static function getPayoutOrder(int $orderId)
    {
        $payoutOrder = DB::getInstance()->executeS(
            'SELECT id_checkout, id_order, amount, checkout_data, checkout_status, checkout_url, payment_status, external_id, idempotency_key FROM `' . _DB_PREFIX_ . self::PAYOUT_ORDER_TABLE . '` WHERE id_order = ' . $orderId
        );
        if (!empty($payoutOrder)) {
            return $payoutOrder[0];
        }
        return false;
    }

    /**
     * get payout refunds sum by checkout id
     *
     * @param int $checkoutId
     *
     * @return float
     */
    public static function getPayoutRefunds(int $checkoutId): float
    {
        return (float)DB::getInstance()->getValue(
            'SELECT IFNULL(sum(amount), 0) FROM `' . _DB_PREFIX_ . self::PAYOUT_REFUND_TABLE . '` WHERE id_checkout = ' . $checkoutId
        );
    }

    /**
     * find payout order logs by order id
     *
     * @param int $orderId
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    private function getPayoutOrderLogs(int $orderId): array
    {
        $payoutOrderLogs = DB::getInstance()->executeS(
            'SELECT id_payout_log, id_order, id_checkout, `data`, data_type, `type`, date_added FROM `' . _DB_PREFIX_ . self::PAYOUT_LOG_TABLE . '` WHERE id_order = ' . $orderId . ' ORDER BY id_payout_log ASC'
        );
        if (!empty($payoutOrderLogs)) {
            return $payoutOrderLogs;
        }
        return [];
    }

    /**
     * find payout order refund records by order id
     *
     * @param int $orderId
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function getPayoutOrderRefundRecords(int $orderId): array
    {
        $payoutOrderRefundRecords = DB::getInstance()->executeS(
            'SELECT pr.id_refund, pr.id_checkout, pr.id_employee, pr.employee_info, pr.id_withdrawal, pr.amount, pr.response, `date` FROM `' . _DB_PREFIX_ . self::PAYOUT_REFUND_TABLE
            . '` pr JOIN `' . _DB_PREFIX_ . self::PAYOUT_ORDER_TABLE . '` po ON (pr.id_checkout = po.id_checkout) WHERE po.id_order = ' . $orderId . ' ORDER BY pr.id_refund ASC'
        );
        if (!empty($payoutOrderRefundRecords)) {
            return $payoutOrderRefundRecords;
        }
        return [];
    }

    /**
     * Load the configuration form / process submitted fields
     *
     * @return string
     */
    public function getContent(): string
    {
        $html = '';
        if (Tools::isSubmit('submitPayoutModule')) {
            $errors = '';
            $validatedFields = [];
            foreach (array_filter(
                         $this->moduleConfigs,
                         function ($field) {
                             return !$this->isBoolAttributeTrue($field, 'readonly') && $this->isBoolAttributeTrue($field, 'form_field');
                         }) as $key => $field) {
                $value = $this->isBoolAttributeTrue($field, 'is_bool') ? (int)Tools::getValue($key) : Tools::getValue($key);
                if ($this->isBoolAttributeTrue($field, 'required') && (!Tools::getIsset($key) || (!$this->isBoolAttributeTrue($field, 'is_bool') && empty($value)))) {
                    $errors .= $this->displayError($this->l('Required field') . ' ' . $key . ' ' . $this->l('is not filled'));
                } elseif (Tools::getIsset($key)) {
                    $validatedFields[$key] = $value;
                }
            }

            $html .= $errors;
            if (empty($errors)) {
                foreach ($validatedFields as $key => $value) {
                    $this->updateModuleConfiguration($key, $value);
                }
                $html .= $this->displayConfirmation($this->l('Settings were saved successfully'));
            }
        }

        return $html . $this->renderForm();
    }

    /**
     * check if field's attribute is true
     *
     * @param array $field
     * @param string $attribute
     *
     * @return bool
     */
    private function isBoolAttributeTrue(array $field, string $attribute): bool
    {
        return isset($field[$attribute]) && $field[$attribute];
    }

    /**
     * Back office headers
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        if ($this->context->controller->controller_name == "AdminOrders") {
            Media::addJsDef(['payoutRefundControllerUrl' => $this->context->link->getAdminLink("AdminPayoutRefund")]);
            $this->context->controller->addJS($this->_path . 'views/js/admin_order.js');
            $this->context->controller->addCSS($this->_path . 'views/css/admin_order.css');
        }
    }

    /**
     * Front office headers
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array $params
     *
     * @return array|null
     */
    public function hookPaymentOptions(array $params): ?array
    {
        if (
            !$this->active
            || empty($this->moduleConfigs[self::PAYOUT_CLIENT_ID]['value'])
            || empty($this->moduleConfigs[self::PAYOUT_SECRET]['value'])
            || !$this->moduleConfigs[self::PAYOUT_ALLOWED_FOR_CURRENT_CONTEXT_CART]['value']
        ) {
            return null;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return null;
        }
        $option = new PaymentOption();
        $option->setCallToActionText($this->l('Pay via Payout'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true));
        return [
            $option
        ];
    }

    //compatibility for prestashop 1.6
    public function hookPayment($params)
    {
        if (
            !$this->active
            || empty($this->moduleConfigs[self::PAYOUT_CLIENT_ID]['value'])
            || empty($this->moduleConfigs[self::PAYOUT_SECRET]['value'])
            || !$this->moduleConfigs[self::PAYOUT_ALLOWED_FOR_CURRENT_CONTEXT_CART]['value']
        ) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign([
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => $this->context->link->getModuleLink($this->name, 'validation', [], true)
        ]);
        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * check currency with module currencies
     *
     * @param Cart $cart
     *
     * @return bool
     */
    public function checkCurrency(Cart $cart): bool
    {
        $orderCurrency = new Currency($cart->id_currency);
        $moduleCurrency = $this->getCurrency($cart->id_currency);
        if (is_array($moduleCurrency)) {
            foreach ($moduleCurrency as $currency_module) {
                if ($orderCurrency->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * hook to display in order confirmation
     *
     * @param array $params
     *
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function hookDisplayOrderConfirmation(array $params)
    {
        return $this->getPayoutOrderStatusHook($this->isPrestashop1_6 ? $params['objOrder'] : $params['order'], true);
    }

    /**
     * hook to display in order detail
     *
     * @param array $params
     *
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function hookDisplayOrderDetail(array $params)
    {
        return $this->getPayoutOrderStatusHook($params['order']);
    }

    /**
     * get content for order confirmation / detail pages - payout payment status / repay option / reorder option
     *
     * @param Order $order
     * @param bool $confirmationPage
     *
     * @return string
     * @throws PrestaShopDatabaseException
     */
    private function getPayoutOrderStatusHook(Order $order, bool $confirmationPage = false)
    {
        if ($order->module != $this->name) {
            return false;
        }

        // default state
        $payoutOrderStatus = "not_paid_yet";
        $payoutOrder = self::getPayoutOrder($order->id);

        // if it is confirmation page and processing state -> start countdown to redirection to payout gateway
        if (
            $confirmationPage
            && $payoutOrder
            && $payoutOrder['checkout_status'] == self::CHECKOUT_STATE_PROCESSING
        ) {
            $payoutOrderStatus = "confirmation_to_redirect";
            // set redirection gateway url
            $this->smarty->assign('payout_checkout_url', $payoutOrder['checkout_url']);
        } else if ($payoutOrder) { // if payout order is set and it has order state not processing - set it
            if ($payoutOrder['checkout_status'] == self::CHECKOUT_STATE_SUCCEEDED) {
                $payoutOrderStatus = "success";
            } elseif ($payoutOrder['checkout_status'] == self::CHECKOUT_STATE_EXPIRED) {
                $payoutOrderStatus = "expired";
            }
        }

        $this->smarty->assign('payout_order_status', $payoutOrderStatus);

        // set url for payment repeat
        $this->smarty->assign(
            'payout_repay_url',
            $this->context->link->getModuleLink(
                $this->name,
                'repeat',
                ['id_order' => $order->id, 'key' => $order->secure_key]
            )
        );

        return $this->display(__FILE__, 'views/templates/hook/checkout_state.tpl');
    }

    /**
     * get tab link for payout part of backoffice order detail page - link is connected to its content down below
     *
     * @param array $params
     *
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function hookDisplayAdminOrderTabLink(array $params): string
    {
        return $this->hookDisplayAdminOrderTabOrder($params);
    }

    /**
     * prestashop 1.6 hook
     * get tab link for payout part of backoffice order detail page - link is connected to its content down below
     *
     * @param array $params
     *
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function hookDisplayAdminOrderTabOrder(array $params)
    {
        $orderId = $this->isPrestashop1_6 ? $params['order']->id : $params['id_order'];
        $payoutOrder = self::getPayoutOrder($orderId);
        if (!$payoutOrder) {
            return '';
        }
        $payoutOrderLogs = $this->getPayoutOrderLogs($orderId);
        $payoutOrderRefundRecords = $this->getPayoutOrderRefundRecords($orderId);
        $this->smarty->assign('payout_order_logs', $payoutOrderLogs);
        $this->smarty->assign('payout_order_refund_records', $payoutOrderRefundRecords);
        $this->smarty->assign('ps_version', _PS_VERSION_);
        return $this->display(__FILE__, 'views/templates/hook/payout_order_log_link.tpl');
    }

    /**
     * get content for payout part of backoffice order detail page - link is connected to this content
     *
     * @param array $params
     *
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function hookDisplayAdminOrderTabContent(array $params): string
    {
        return $this->hookDisplayAdminOrderContentOrder($params);
    }

    /**
     * prestashop 1.6 hook
     * get content for payout part of backoffice order detail page - link is connected to this content
     *
     * @param array $params
     *
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function hookDisplayAdminOrderContentOrder(array $params): string
    {
        $orderId = $this->isPrestashop1_6 ? $params['order']->id : $params['id_order'];
        $payoutOrder = self::getPayoutOrder($orderId);
        if (!$payoutOrder) {
            return '';
        }

        $payoutOrderLogs = $this->getPayoutOrderLogs($orderId);

        $this->smarty->assign([
            'payout_order_logs' => $payoutOrderLogs,
            'payout_order_refund_records' => $this->getPayoutOrderRefundRecordsForTemplate($orderId),
            'prestashop16' => $this->isPrestashop1_6,
            'checkoutSuccess' => $payoutOrder['checkout_status'] == Payout::CHECKOUT_STATE_SUCCEEDED,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/payout_order_log.tpl');
    }

    /**
     * get payout refund records template output
     *
     * @param int $orderId
     * @param bool $checkoutSuccess
     *
     * @return false|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws \PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function getPayoutOrderRefundRecordsTemplate(int $orderId, bool $checkoutSuccess)
    {
        $this->smarty->assign('payout_order_refund_records', $this->getPayoutOrderRefundRecordsForTemplate($orderId));
        $this->smarty->assign('prestashop16', $this->isPrestashop1_6);
        $this->smarty->assign('checkoutSuccess', $checkoutSuccess);
        return $this->display(__FILE__, 'views/templates/hook/payout_order_log_refund.tpl');
    }

    /**
     * get payout refund records for template
     *
     * @param int $orderId
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws \PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    private function getPayoutOrderRefundRecordsForTemplate(int $orderId): array
    {
        $payoutOrderRefundRecords = $this->getPayoutOrderRefundRecords($orderId);
        foreach ($payoutOrderRefundRecords as &$payoutOrderRefundRecord) {
            if (version_compare(_PS_VERSION_, '1.7.7', '<')) {
                /** @noinspection PhpDeprecationInspection */
                $payoutOrderRefundRecord['amount_text'] =
                    Tools::displayPrice($payoutOrderRefundRecord['amount'], Currency::getCurrencyInstance((new Order($orderId))->id_currency));
            } else {
                $payoutOrderRefundRecord['amount_text'] =
                    $this->context->getCurrentLocale()->formatPrice($payoutOrderRefundRecord['amount'], Currency::getIsoCodeById((new Order($orderId))->id_currency));
            }
        }

        return $payoutOrderRefundRecords;
    }

    /**
     * prestashop 1.6 hook
     * show custom notifications - errors and infos on top of page if it is set
     *
     * @return string|bool
     */
    public function hookDisplayTop()
    {
        if ($this->isPrestashop1_6) {
            return $this->displayNotifications();
        }
        return false;
    }

    /**
     * get refund content for admin order page
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayAdminOrderTop(array $params): string
    {
        return $this->getRefund($params['id_order']);
    }

    /**
     * get refund content for admin order page
     *
     * @param array $params
     * @return false|string
     */
    public function hookDisplayAdminOrder(array $params)
    {
        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            return false;
        }

        return $this->getRefund($params['id_order']);
    }

    /**
     * get refund content
     *
     * @param int $orderId
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    private function getRefund(int $orderId): string
    {
        $payoutOrder = self::getPayoutOrder($orderId);
        if (!$payoutOrder || $payoutOrder['checkout_status'] != self::CHECKOUT_STATE_SUCCEEDED) {
            return '';
        }
        $currency = new Currency((new Order($orderId))->id_currency);
        $prestashopLessThan177 = version_compare(_PS_VERSION_, '1.7.7', '<');
        /** @noinspection PhpDeprecationInspection */
        $currencyPrecision = $prestashopLessThan177 ? (int)_PS_PRICE_DISPLAY_PRECISION_ : $currency->precision;
        $currencyPrecisionUnits = pow(10, $currencyPrecision);
        $step = 1 / $currencyPrecisionUnits;


        /** @noinspection PhpDeprecationInspection */
        $currencySign = $prestashopLessThan177 ? $currency->sign : $currency->symbol;

        $this->context->smarty->assign([
            'orderId' => $orderId,
            'currencyPrecision' => $currencyPrecision,
            'currencyPrecisionUnits' => $currencyPrecisionUnits,
            'currencySign' => $currencySign,
            'step' => $step,
            'orderRefundText' => $this->l('Refund on Payout', 'refund'),
            'refundConfirmText' => $this->l('Are you sure to process refund via Payout?', 'refund'),
            $this->smarty->assign('prestashop16', $this->isPrestashop1_6),
        ]);

        return $this->displayNotifications(true)
            . $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/partial_refund.tpl')
            . $this->display(__FILE__, 'views/templates/hook/refund_modal.tpl')
            . $this->display(__FILE__, 'views/templates/hook/refund.tpl');
    }

    /**
     * Add buttons to main buttons bar
     *
     * @param array $params
     *
     * @return void
     * @throws \PrestaShop\PrestaShop\Core\Exception\TypeException
     */
    public function hookActionGetAdminOrderButtons(array $params): void
    {
        $payoutOrder = self::getPayoutOrder((int)$params['id_order']);
        if (!$payoutOrder || $payoutOrder['checkout_status'] != self::CHECKOUT_STATE_SUCCEEDED) {
            return;
        }

        /** @var \PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButtonsCollection $bar */
        $bar = $params['actions_bar_buttons_collection'];

        $bar->add(
            new \PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButton(
                'btn btn-action', ['onclick' => 'updateRefundableAmount()', 'id' => 'openRefundModalBtn', 'data-toggle' => 'modal', 'data-placement' => 'top', 'data-target' => '#refundModal'], $this->l('Refund on Payout', 'refund')
            )
        );
    }

    /**
     * hook to process possible partial refund
     *
     * @param array $params
     *
     * @return void
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function hookActionOrderSlipAdd(array $params): void
    {
        if (Tools::isSubmit('payout_partial_refund')) {
            $errors = [];
            $info = [];
            $success = [];
            $params = array_merge(Tools::getAllValues(), $params);
            $amount = self::calculateOrderSlipAmount($params);
            $refundResult = $this->refundOrder((int)$params['order']->id, $amount);

            if ($refundResult['success']) {
                $messageObject = json_decode($refundResult['message']);
                $success[] = $messageObject->message;
            } else {
                $errors[] = $refundResult['message'];
                $info[] = $this->l('You can also try custom refund by clicking \'Refund on Payout\' button in upper action bar', 'refund');
            }

            self::setPayoutNotifications($errors, $info, $success, true);
        }
    }

    /**
     * Calculate amount to be refunded
     *
     * @param array $params
     *
     * @return float
     */
    private static function calculateOrderSlipAmount(array $params): float
    {
        $amount = 0;

        if (!empty($params['productList'])) {
            foreach ($params['productList'] as $product) {
                if (isset($product['total_refunded_tax_incl'])) {
                    $amount += (float)$product['total_refunded_tax_incl'];
                } else {
                    $amount += $product['amount'];
                }
            }
        }

        if (!empty($params['partialRefundShippingCost'])) {
            $amount += (float)$params['partialRefundShippingCost'];
        }

        // For prestashop version > 1.7.7
        if (!empty($params['cancel_product'])) {
            $refundData = $params['cancel_product'];
            $amount += (float)str_replace(',', '.', $refundData['shipping_amount']);
            if (isset($refundData['shipping']) && $refundData['shipping'] == "1") {
                $amount += (float)$params['order']->total_shipping_tax_incl;
            }
        }

        $amount -= self::calculateDiscount($params);

        return $amount;
    }

    /**
     * Calculate discount for refund
     *
     * @param array $params
     *
     * @return float
     */
    private static function calculateDiscount(array $params): float
    {
        // $params differs according PS version
        $amount = 0;

        if (!empty($params['refund_voucher_off'])) {
            if ($params['refund_voucher_off'] == "1" && !empty($params['order_discount_price'])) {
                return (float)$params['order_discount_price'];
            } else if ($params['refund_voucher_off'] == "2" && !empty($params['refund_voucher_choose'])) {
                return (float)$params['refund_voucher_choose'];
            }
        }

        if (!empty($params['cancel_product']['voucher_refund_type'])) {
            if ($params['cancel_product']['voucher_refund_type'] == 1) {
                if ($params['order'] instanceof Order) {
                    return (float)$params['order']->total_discounts_tax_incl;
                }
            }
        }

        return $amount;
    }

    /**
     * render module configuration form
     *
     * @return string
     */
    protected function renderForm(): string
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPayoutModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
            . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getModuleConfigFields(true), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $helper->tpl_vars['fields_value']['check_filled_credentials'] =
            '<span class="btn btn-default" onclick="checkCredentials()">' . $this->l('Check filled credentials') . '</span>
            <div class="alert alert-dismissible" id="check-filled-credentials-message" role="alert" style="display: none"></div>';

        Media::addJsDef(['payoutConfigurationControllerUrl' => $this->context->link->getAdminLink("AdminPayoutConfiguration")]);
        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * build module configuration form
     *
     * @return array
     */
    protected function getConfigForm(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    $this->setModuleConfigAttributes(
                        [
                            'type' => 'text',
                            'name' => self::PAYOUT_NOTIFY_URL,
                            'desc' => $this->l('Notify url need to be filled in payout administration when you are creating new api key'),
                        ]
                    ),
                    $this->setModuleConfigAttributes(
                        [
                            'type' => 'switch',
                            'name' => self::PAYOUT_SANDBOX_MODE,
                            'desc' => $this->l('Enable Sandbox Mode'),
                            'values' => [
                                [
                                    'id' => 'enabled',
                                    'value' => true,
                                    'label' => $this->l('Enabled'),
                                ],
                                [
                                    'id' => 'disabled',
                                    'value' => false,
                                    'label' => $this->l('Disabled'),
                                ]
                            ],
                        ]
                    ),
                    $this->setModuleConfigAttributes(
                        [
                            'col' => 3,
                            'type' => 'text',
                            'desc' => $this->l('Client Id'),
                            'name' => self::PAYOUT_CLIENT_ID,
                        ]
                    ),
                    $this->setModuleConfigAttributes(
                        [
                            'type' => 'password',
                            'name' => self::PAYOUT_SECRET,
                        ]
                    ),
                    [
                        'type' => 'free',
                        'name' => 'check_filled_credentials',
                        'label' => $this->l('Connection check'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * decorate form fields with module config attributes
     *
     * @param array $formField
     *
     * @return array
     */
    private function setModuleConfigAttributes(array $formField): array
    {
        if (isset ($formField["name"]) && array_key_exists($formField["name"], $this->moduleConfigs)) {
            if (isset($this->moduleConfigs[$formField["name"]]['required']) && $this->moduleConfigs[$formField["name"]]['required']) {
                $formField['required'] = true;
            }
            if (isset($this->moduleConfigs[$formField["name"]]['readonly']) && $this->moduleConfigs[$formField["name"]]['readonly']) {
                $formField['readonly'] = true;
            }
            if (isset($this->moduleConfigs[$formField["name"]]['is_bool']) && $this->moduleConfigs[$formField["name"]]['is_bool']) {
                $formField['is_bool'] = true;
            }
            $formField['label'] = $this->l($this->moduleConfigs[$formField["name"]]['label']);
        }
        return $formField;
    }

    /**
     * Process order refund with passed amount
     *
     * @param int $orderId
     * @param float $amount
     *
     * @return array
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function refundOrder(int $orderId, float $amount): array
    {
        return $this->payoutRefund->refund($orderId, $amount);
    }

    /**
     * log with [Payout] prefix
     *
     * @param string $message the log message
     * @param int $severity
     * @param int|null $errorCode
     * @param string|null $objectType
     * @param int|null $objectId
     *
     * @return bool
     */
    public static function addLog(string $message, int $severity = 1, int $errorCode = null, string $objectType = null, int $objectId = null): bool
    {
        return PrestaShopLogger::addLog('[Payout] ' . $message, $severity, $errorCode, $objectType, $objectId);
    }

    /**
     * set payout notifications to session / cookies
     *
     * @param array $errors
     * @param array $info
     * @param array $success
     * @param bool $admin
     *
     * @return void
     */
    public static function setPayoutNotifications(array $errors, array $info, array $success, bool $admin = false): void
    {
        if (empty($errors) && empty($info) && empty($success)) {
            return;
        }

        $notifications = json_encode([
            'errors' => $errors,
            'info' => $info,
            'success' => $success,
        ]);
        $sessionKey = 'payout_notifications' . ($admin ? '_admin' : '');

        // save notifications to session or cookies like it is in prestashop 1.7
        if (session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION[$sessionKey] = $notifications;
        } elseif (session_status() == PHP_SESSION_NONE) {
            session_start();
            $_SESSION[$sessionKey] = $notifications;
        } else {
            setcookie($sessionKey, $notifications);
        }
    }

    /**
     * get payout notifications from session / cookies
     *
     * @param bool $admin
     * @return array
     */
    public static function getPayoutNotifications(bool $admin = false): array
    {
        $sessionKey = 'payout_notifications' . ($admin ? '_admin' : '');
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $notifications = ['errors' => [], 'info' => [], 'success' => []];
        if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION[$sessionKey])) {
            $notifications = json_decode($_SESSION[$sessionKey], true);
            unset($_SESSION[$sessionKey]);
        } elseif (isset($_COOKIE[$sessionKey])) {
            $notifications = json_decode($_COOKIE[$sessionKey], true);
            unset($_COOKIE[$sessionKey]);
        }

        return $notifications;
    }

    /**
     * display payout notifications from session / cookies
     *
     * @param bool $admin
     * @return false|string
     */
    public function displayNotifications(bool $admin = false)
    {
        $this->smarty->assign('notifications', self::getPayoutNotifications($admin));
        $this->smarty->assign('admin', $admin);
        return $this->display(__FILE__, 'views/templates/hook/notifications.tpl');
    }
}
