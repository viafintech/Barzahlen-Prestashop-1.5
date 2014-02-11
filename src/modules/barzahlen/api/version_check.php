<?php
/**
 * Barzahlen Payment Module SDK
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 of the License
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/
 *
 * @copyright   Copyright (c) 2013 Zerebro Internet GmbH (http://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
 */

class Barzahlen_Version_Check extends Barzahlen_Base
{
    /**
     * Barzahlen Shop ID
     *
     * @var string
     */
    protected $_shopId;

    /**
     * Barzahlen Payment Key
     *
     * @var string
     */
    protected $_paymentKey;

    /**
     * Constructor. Sets basic settings.
     *
     * @param string $shopId merchants shop id
     * @param string $paymentKey merchants payment key
     */
    public function __construct($shopId, $paymentKey)
    {
        $this->_shopId = $shopId;
        $this->_paymentKey = $paymentKey;
    }

    /**
     * Kicks off the plugin check.
     *
     * @param string $shopsystem used shop system
     * @param string $shopsystemVersion used shop system version
     * @param string $pluginVersion current plugin version
     * @return boolean | string
     */
    public function checkVersion($shopsystem, $shopsystemVersion, $pluginVersion)
    {
        $transArray['shop_id'] = $this->_shopId;
        $transArray['shopsystem'] = $shopsystem;
        $transArray['shopsystem_version'] = $shopsystemVersion;
        $transArray['plugin_version'] = $pluginVersion;
        $transArray['hash'] = $this->_createHash($transArray, $this->_paymentKey);

        $latestVersion = $this->_requestVersion($transArray);
        if ($latestVersion != false && $latestVersion != $transArray['plugin_version']) {
            return $latestVersion;
        }

        return false;
    }

    /**
     * Requests the current version and parses the xml.
     *
     * @param array $transArray
     * @return boolean | string
     */
    protected function _requestVersion(array $transArray)
    {
        $curl = $this->_prepareRequest($transArray);
        $xmlResponse = $this->_sendRequest($curl);

        if (!is_string($xmlResponse) || $xmlResponse == '') {
            throw new Barzahlen_Exception('PluginCheck: No valid xml response received.');
        }

        try {
            $xmlObj = new SimpleXMLElement($xmlResponse);
        } catch (Exception $e) {
            throw new Barzahlen_Exception('PluginCheck: ' . $e);
        }

        if ($xmlObj->{'result'} != 0) {
            throw new Barzahlen_Exception('PluginCheck: XML response contains an error: ' . $xmlObj->{'error-message'});
        }

        return $xmlObj->{'plugin-version'};
    }

    /**
     * Prepares the curl request.
     *
     * @param array $requestArray array with the information which shall be send via POST
     * @return cURL handle object
     */
    protected function _prepareRequest(array $requestArray)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://plugincheck.barzahlen.de/check');
        curl_setopt($curl, CURLOPT_POST, count($requestArray));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestArray);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/certs/ca-bundle.crt');
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, 1.1);
        return $curl;
    }

    /**
     * Send the information via HTTP POST to the given domain. A xml as anwser is expected.
     * SSL is required for a connection to Barzahlen.
     *
     * @return cURL handle object
     * @return xml response from Barzahlen
     */
    protected function _sendRequest($curl)
    {
        $return = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error != '') {
            throw new Barzahlen_Exception('PluginCheck: Error during cURL - ' . $error);
        }

        return $return;
    }
}
