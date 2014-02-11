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

class BarzahlenPaymentModuleFrontController extends ModuleFrontController {

  public $ssl = true;

  /**
   * Final payment page.
   * @see FrontController::initContent()
   */
  public function initContent() {

    $this->display_column_left = false;
    parent::initContent();

    $cart = $this->context->cart;
    if (!$this->module->checkCurrency($cart)) {
      Tools::redirect('index.php?controller=order');
    }

    $this->context->smarty->assign(array(
      'nbProducts' => $cart->nbProducts(),
      'isoCode' => $this->context->language->iso_code,
      'barzahlen_sandbox' => Configuration::get('barzahlen_sandbox'),
      'this_path' => $this->module->getPathUri(),
      'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
    ));

    $this->setTemplate('payment_execution.tpl');
  }
}
