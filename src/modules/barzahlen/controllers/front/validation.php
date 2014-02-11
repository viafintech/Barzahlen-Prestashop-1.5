<?php
/**
 * Barzahlen Payment Module (PrestaShop)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@barzahlen.de so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) 2012 Zerebro Internet GmbH (http://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL-3.0)
 */

require_once dirname(__FILE__) . '/../../api/loader.php';

class BarzahlenValidationModuleFrontController extends ModuleFrontController
{
    /**
     * Payment request and order processing.
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        global $cookie;

        $lang = new Language((int) $cookie->id_lang);
        $api = new Barzahlen_Api(Configuration::get('barzahlen_shopid'), Configuration::get('barzahlen_paymentkey'), Configuration::get('barzahlen_sandbox'));
        $api->setDebug(Configuration::get('barzahlen_debug'));
        $api->setLanguage($lang->iso_code);

        $customer = new Customer((int) $this->context->cart->id_customer);
        $address = new Address($this->context->cart->id_address_invoice);
        $country = new Country($address->id_country);

        $customerEmail = $customer->email;
        $customerStreetNr = $address->address1;
        $customerZipcode = $address->postcode;
        $customerCity = $address->city;
        $customerCountry = $country->iso_code;
        $amount = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        $payment = new Barzahlen_Request_Payment($customerEmail, $customerStreetNr, $customerZipcode, $customerCity, $customerCountry, $amount);

        try {
            $api->handleRequest($payment);
        } catch (Exception $e) {
            LoggerCore::addLog('Barzahlen/Payment: ' . $e, 3, null, null, null, true);
        }

        if (!$payment->isValid()) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $this->module->validateOrder((int) $this->context->cart->id, Configuration::get('BARZAHLEN_PENDING'), $amount, $this->module->displayName, null, array(), null, false, $customer->secure_key);
        session_start();
        $_SESSION['barzahlen_infotext'] = $payment->getInfotext1();
        Db::getInstance()->Execute("INSERT INTO `" . _DB_PREFIX_ . "barzahlen_transactions` (transaction_id, order_id, transaction_state) VALUES ('" . $payment->getTransactionId() . "', '" . (int) $this->module->currentOrder . "', 'pending')");

        $update = new Barzahlen_Request_Update($payment->getTransactionId(), (int) $this->module->currentOrder);

        try {
            $api->handleRequest($update);
        } catch (Exception $e) {
            LoggerCore::addLog('Barzahlen/Payment: ' . $e, 3, null, null, null, true);
        }

        Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?key=' . $customer->secure_key . '&id_cart=' . (int) $this->context->cart->id . '&id_module=' . (int) $this->module->id . '&id_order=' . (int) $this->module->currentOrder);
    }
}
