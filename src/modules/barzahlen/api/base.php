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

abstract class Barzahlen_Base {

  const APIDOMAIN = 'https://api.barzahlen.de/v1/transactions/'; //!< call domain (productive use)
  const APIDOMAINSANDBOX = 'https://api-sandbox.barzahlen.de/v1/transactions/'; //!< sandbox call domain

  const HASHALGO = 'sha512'; //!< hash algorithm
  const SEPARATOR = ';'; //!< separator character
  const MAXATTEMPTS = 2; //!< maximum of allowed connection attempts

  protected $_debug = false; //!< debug mode on / off

  /**
   * Sets debug settings. Adjusted for PrestaShop.
   *
   * @param boolean $debug debug mode on / off
   * @param string $logFile position of log file
   */
  public function setDebug($debug) {
    $this->_debug = $debug;
  }

  /**
   * Write debug message to log file. Adjusted for PrestaShop.
   *
   * @param string $message debug message
   * @param array $data related data (optional)
   */
  protected function _debug($message, $data = array()) {

    if($this->_debug) {
      foreach ($data as $key => $value) {
        $message .= ' - ' . $key . ':' . $value;
      }
      LoggerCore::addLog($message, 1, null, null, null, true);
    }
  }

  /**
   * Generates the hash for the request array.
   *
   * @param array $requestArray array from which hash is requested
   * @param string $key private key depending on hash type
   * @return hash sum
   */
  protected function _createHash(array $hashArray, $key) {

    $hashArray[] = $key;
    $hashString = implode(self::SEPARATOR, $hashArray);
    return hash(self::HASHALGO, $hashString);
  }

  /**
   * Removes empty values from arrays.
   *
   * @param array $array array with (empty) values
   */
  protected function _removeEmptyValues(array &$array) {

    foreach($array as $key => $value) {
      if($value == '') {
        unset($array[$key]);
      }
    }
  }

  /**
   * Converts ISO-8859-1 strings to UTF-8 if necessary.
   *
   * @param string $string text which is to check
   * @return string with utf-8 encoding
   */
  public function isoConvert($string) {

    if(!preg_match('/\S/u', $string)) {
      $string = utf8_encode($string);
    }

    return $string;
  }
}
?>