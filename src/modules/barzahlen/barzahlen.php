<?php
/**
 * Barzahlen Payment Module (PrestaShop)
 *
 * @copyright   Copyright (c) 2015 Cash Payment Solutions GmbH (https://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL-3.0)
 */

class Barzahlen extends PaymentModule
{
    private $_html = '';
    protected $_sandbox = false;
    protected $_shopid = '';
    protected $_paymentkey = '';
    protected $_notificationkey = '';
    protected $_debug = false;

    /**
     * Constructor is used to load all necessary payment module information.
     */
    public function __construct()
    {
        $this->name = 'barzahlen';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.5';
        $this->author = 'Cash Payment Solutions GmbH';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array($this->name . '_sandbox', $this->name . '_shopid', $this->name . '_paymentkey', $this->name . '_notificationkey', $this->name . '_debug'));
        if (isset($config[$this->name . '_sandbox']))
            $this->_sandbox = $config[$this->name . '_sandbox'] == 'on' ? true : false;
        if (isset($config[$this->name . '_shopid']))
            $this->_shopid = $config[$this->name . '_shopid'];
        if (isset($config[$this->name . '_paymentkey']))
            $this->_paymentkey = $config[$this->name . '_paymentkey'];
        if (isset($config[$this->name . '_notificationkey']))
            $this->_notificationkey = $config[$this->name . '_notificationkey'];
        if (isset($config[$this->name . '_debug']))
            $this->_debug = $config[$this->name . '_debug'] == 'on' ? true : false;

        parent::__construct();

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Barzahlen');
        $this->description = $this->l('Barzahlen let\'s your customers pay cash online. You get a payment confirmation in real-time and you benefit from our payment guarantee and new customer groups. See how Barzahlen works: <a href=http://www.barzahlen.de/partner/funktionsweise target="_blank">http://www.barzahlen.de/partner/funktionsweise</a>');
    }

    /**
     * Core install method.
     *
     * @return boolean
     */
    public function install()
    {
        if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentReturn') || !$this->registerHook('actionOrderStatusUpdate')  || !$this->registerHook('displayBackOfficeFooter')) {
            return false;
        }
        $this->createTables();
        $this->createOrderState();
        return true;
    }

    /**
     * Creates the Barzahlen transaction table.
     */
    protected function createTables()
    {
        Db::getInstance()->Execute("
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "barzahlen_transactions` (
            `transaction_id` int(11) NOT NULL DEFAULT 0,
            `order_id` int(11) NOT NULL,
            `transaction_state` ENUM( 'pending', 'paid', 'expired' ) NOT NULL,
            PRIMARY KEY (`transaction_id`)
          ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8");

        // update 1.0.2
        Db::getInstance()->Execute("
            ALTER TABLE `" . _DB_PREFIX_ . "barzahlen_transactions` CHANGE `transaction_state` `transaction_state` ENUM( 'pending', 'paid', 'expired', 'canceled' ) NOT NULL;");
    }

    /**
     * Creates the new order states for the Barzahlen transaction states.
     */
    protected function createOrderState()
    {
        // pending state
        if (!Configuration::get('BARZAHLEN_PENDING')) {
            $orderState = new OrderState();
            $orderState->name = array();

            foreach (Language::getLanguages() as $language) {
                if (strtolower($language['iso_code']) == 'de') {
                    $orderState->name[$language['id_lang']] = 'Warten auf Zahlungseingang von Barzahlen';
                } else {
                    $orderState->name[$language['id_lang']] = 'Awaiting Barzahlen Payment';
                }
            }

            $orderState->send_email = false;
            $orderState->color = '#4169E1';
            $orderState->hidden = false;
            $orderState->delivery = false;
            $orderState->logable = true;
            $orderState->invoice = false;

            if ($orderState->add()) {
                $source = dirname(__FILE__) . '/../../img/admin/gold.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int) $orderState->id . '.gif';
                copy($source, $destination);
            }
            Configuration::updateValue('BARZAHLEN_PENDING', (int) $orderState->id);
        }

        // paid state
        if (!Configuration::get('BARZAHLEN_PAID')) {
            $orderState = new OrderState();
            $orderState->name = array();

            foreach (Language::getLanguages() as $language) {
                if (strtolower($language['iso_code']) == 'de') {
                    $orderState->name[$language['id_lang']] = 'Zahlungseingang von Barzahlen';
                } else {
                    $orderState->name[$language['id_lang']] = 'Received Barzahlen Payment';
                }
            }

            $orderState->send_email = false;
            $orderState->color = '#32CD32';
            $orderState->hidden = false;
            $orderState->delivery = true;
            $orderState->logable = true;
            $orderState->invoice = true;

            if ($orderState->add()) {
                $source = dirname(__FILE__) . '/../../img/os/2.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int) $orderState->id . '.gif';
                copy($source, $destination);
            }
            Configuration::updateValue('BARZAHLEN_PAID', (int) $orderState->id);
        }

        // expired state
        if (!Configuration::get('BARZAHLEN_EXPIRED')) {
            $orderState = new OrderState();
            $orderState->name = array();

            foreach (Language::getLanguages() as $language) {
                if (strtolower($language['iso_code']) == 'de') {
                    $orderState->name[$language['id_lang']] = 'Barzahlen-Zahlschein abgelaufen';
                } else {
                    $orderState->name[$language['id_lang']] = 'Barzahlen Payment Expired';
                }
            }

            $orderState->send_email = false;
            $orderState->color = '#DC143C';
            $orderState->hidden = false;
            $orderState->delivery = false;
            $orderState->logable = true;
            $orderState->invoice = false;

            if ($orderState->add()) {
                $source = dirname(__FILE__) . '/../../img/os/6.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int) $orderState->id . '.gif';
                copy($source, $destination);
            }
            Configuration::updateValue('BARZAHLEN_EXPIRED', (int) $orderState->id);
        }
    }

    /**
     * Uninstaller. Extends parent and removes Barzahlen settings. Transaction
     * table and order states remain.
     *
     * @return boolean
     */
    public function uninstall()
    {
        if (!Configuration::deleteByName($this->name . '_sandbox')
                || !Configuration::deleteByName($this->name . '_shopid')
                || !Configuration::deleteByName($this->name . '_paymentkey')
                || !Configuration::deleteByName($this->name . '_notificationkey')
                || !Configuration::deleteByName($this->name . '_debug')
                || !Configuration::deleteByName($this->name . '_lastcheck')
                || !parent::uninstall()) {
            return false;
        }
        return true;
    }

    /**
     * Saves new settings and calls html output method.
     *
     * @return string with html code
     */
    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue($this->name . '_sandbox', Tools::getValue($this->name . '_sandbox'));
            Configuration::updateValue($this->name . '_shopid', Tools::getValue($this->name . '_shopid'));
            Configuration::updateValue($this->name . '_paymentkey', Tools::getValue($this->name . '_paymentkey'));
            Configuration::updateValue($this->name . '_notificationkey', Tools::getValue($this->name . '_notificationkey'));
            Configuration::updateValue($this->name . '_debug', Tools::getValue($this->name . '_debug'));
            $this->_sandbox = Tools::getValue($this->name . '_sandbox');
            $this->_debug = Tools::getValue($this->name . '_debug');
        }
        $this->_displayForm();
        return $this->_html;
    }

    /**
     * Prepares the html form for the module configuration.
     */
    private function _displayForm()
    {
        $sandboxChecked = $this->_sandbox ? 'checked="checked"' : '';
        $debugChecked = $this->_debug ? 'checked="checked"' : '';

        $this->_html .=
                '<form action="' . Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']) . '" method="post">
      <fieldset>
      <legend><img src="../img/admin/prefs.gif" />' . $this->l('Barzahlen Settings') . '</legend>
        <table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
          <tr><td width="170" style="height: 35px;">' . $this->l('Sandbox') . '</td><td><input type="checkbox" name="' . $this->name . '_sandbox" ' . $sandboxChecked . '/></td></tr>
          <tr><td width="170" style="height: 35px;">' . $this->l('Shop ID') . '</td><td><input type="text" name="' . $this->name . '_shopid" value="' . htmlentities(Tools::getValue($this->name . '_shopid', $this->_shopid), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
          <tr><td width="170" style="height: 35px;">' . $this->l('Payment Key') . '</td><td><input type="text" name="' . $this->name . '_paymentkey" value="' . htmlentities(Tools::getValue($this->name . '_paymentkey', $this->_paymentkey), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
          <tr><td width="170" style="height: 35px;">' . $this->l('Notification Key') . '</td><td><input type="text" name="' . $this->name . '_notificationkey" value="' . htmlentities(Tools::getValue($this->name . '_notificationkey', $this->_notificationkey), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
          <tr><td width="170" style="height: 35px;">' . $this->l('Extended Logging') . '</td><td><input type="checkbox" name="' . $this->name . '_debug" ' . $debugChecked . '/></td></tr>
          <tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="' . $this->l('Update settings') . '" type="submit" /></td></tr>
        </table>
      </fieldset>
    </form>';
    }

    /**
     * Prepares and returns payment template for payment selection hook.
     *
     * @global smarty object $smarty
     * @param array $params order parameters
     * @return boolean | string rendered template output
     */
    public function hookPayment($params)
    {
        global $smarty;

        if ($params['cart']->getOrderTotal(true, Cart::BOTH) >= 1000) {
            return false;
        }

        $smarty->assign($this->name . '_sandbox', $this->_sandbox);
        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * Prepares and returns payment success template after order completion.
     *
     * @global type $smarty
     * @param type $params
     * @return string rendered template output
     */
    public function hookPaymentReturn($params)
    {
        global $smarty;

        session_start();
        $smarty->assign($this->name . '_infotext', $_SESSION['barzahlen_infotext']);
        unset($_SESSION['barzahlen_infotext']);

        return $this->display(__FILE__, 'confirmation.tpl');
    }

    /**
     * Hook for catch order cancelations to cancel pending payment slips parallel.
     *
     * @param type $params
     */
    public function hookActionOrderStatusUpdate($params)
    {
        if ($params['newOrderStatus']->template != 'order_canceled') {
            return;
        }

        $rs = Db::getInstance()->ExecuteS("SELECT transaction_id, transaction_state FROM `" . _DB_PREFIX_ . "barzahlen_transactions` WHERE order_id = '" . (int) $params['id_order'] . "'");

        if ($rs[0]['transaction_state'] != 'pending') {
            return;
        }

        global $cookie;
        require_once dirname(__FILE__) . '/api/loader.php';

        $lang = new Language((int) $cookie->id_lang);
        $api = new Barzahlen_Api(Configuration::get('barzahlen_shopid'), Configuration::get('barzahlen_paymentkey'), Configuration::get('barzahlen_sandbox'));
        $api->setDebug(Configuration::get('barzahlen_debug'));
        $api->setLanguage($lang->iso_code);
        $api->setUserAgent('PrestaShop v' . Configuration::get('PS_INSTALL_VERSION') . ' / Plugin v' . $this->version);

        $cancel = new Barzahlen_Request_Cancel($rs[0]['transaction_id']);

        try {
            $api->handleRequest($cancel);
        } catch (Exception $e) {
            Logger::addLog('Barzahlen/Cancel: ' . $e, 3, null, null, null, true);
        }

        if ($cancel->isValid()) {
            Db::getInstance()->Execute("UPDATE `" . _DB_PREFIX_ . "barzahlen_transactions` SET transaction_state = 'canceled' WHERE transaction_id = '" . $rs[0]['transaction_id'] . "'");
        }
    }

    /**
     * Automatic plugin version check (once a week).
     *
     * @param type $params
     */
    public function hookDisplayBackOfficeFooter($params)
    {
        $lastCheck = Configuration::get('barzahlen_lastcheck');

        if($lastCheck == null || $lastCheck < strtotime("-1 week")) {

            Configuration::updateValue($this->name . '_lastcheck', time());
            require_once dirname(__FILE__) . '/api/loader.php';

            try {
                $checker = new Barzahlen_Version_Check(Configuration::get('barzahlen_shopid'), Configuration::get('barzahlen_paymentkey'));
                $response = $checker->checkVersion('PrestaShop', Configuration::get('PS_INSTALL_VERSION'), $this->version);
                if ($response != false) {
                    echo '<script type="text/javascript">
                          if (confirm("' . sprintf($this->l('For the Barzahlen plugin is a new version (%s) available. View now?'), (string) $response) . '")) {
                              window.location.href = "https://integration.barzahlen.de/de/shopsysteme/prestashop";
                          }
                          </script>';
                }
            } catch (Exception $e) {
                Logger::addLog('Barzahlen/Check: ' . $e, 3, null, null, null, true);
            }
        }
    }

    /**
     * Checks if selected currency is possible with Barzahlen.
     *
     * @param Cart $cart cart object
     * @return boolean
     */
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }
}
