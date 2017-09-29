<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RicardoMartins\PagSeguro\Helper;


/**
 * PagSeguro Data helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

	const XML_PATH_PAYMENT_PAGSEGURO_EMAIL              = 'payment/rm_pagseguro/merchant_email';
    const XML_PATH_PAYMENT_PAGSEGURO_TOKEN              = 'payment/rm_pagseguro/token';
    const XML_PATH_PAYMENT_PAGSEGURO_DEBUG              = 'payment/rm_pagseguro/debug';
    const XML_PATH_PAUMENT_PAGSEGURO_SANDBOX            = 'payment/rm_pagseguro/sandbox';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_EMAIL      = 'payment/rm_pagseguro/sandbox_merchant_email';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_TOKEN      = 'payment/rm_pagseguro/sandbox_token';
    const XML_PATH_PAYMENT_PAGSEGURO_WS_URL             = 'payment/rm_pagseguro/ws_url';
    const XML_PATH_PAYMENT_PAGSEGURO_WS_URL_APP         = 'payment/rm_pagseguro/ws_url_app';
    const XML_PATH_PAYMENT_PAGSEGURO_JS_URL             = 'payment/rm_pagseguro/js_url';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_WS_URL     = 'payment/rm_pagseguro/sandbox_ws_url';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_WS_URL_APP = 'payment/rm_pagseguro/sandbox_ws_url_app';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_JS_URL     = 'payment/rm_pagseguro/sandbox_js_url';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_ACTIVE          = 'payment/rm_pagseguro_cc/active';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_FLAG            = 'payment/rm_pagseguro_cc/flag';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_INFO_BRL        = 'payment/rm_pagseguro_cc/info_brl';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_SHOW_TOTAL      = 'payment/rm_pagseguro_cc/show_total';
    const XML_PATH_PAYMENT_PAGSEGUROPRO_TEF_ACTIVE      = 'payment/pagseguropro_tef/active';
    const XML_PATH_PAYMENT_PAGSEGUROPRO_BOLETO_ACTIVE   = 'payment/pagseguropro_boleto/active';
    const XML_PATH_PAYMENT_PAGSEGURO_KEY                = 'payment/rm_pagseguro/key';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_FORCE_INSTALLMENTS = 'payment/rm_pagseguro_cc/force_installments_selection';


     /**
     * Store Manager
     *
     * @var  \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

     /**
     * Quote Session
     *
     * @var  \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
    * @param \Magento\Store\Model\StoreManagerInterface $storeManager
    * @param  \Magento\Checkout\Model\Session $checkoutSession
    */

	public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Helper\Context $context
 
    ) {
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }


    /**
     * Returns session ID from PagSeguro that will be used on JavaScript methods.
     * or FALSE on failure
     * @return bool|string
     */
    public function getSessionId()
    {

        $ch = curl_init('https://ws.ricardomartins.net.br/pspro/v6/wspagseguro/v2/sessions/');
        $params['email'] = $this->getMerchantEmail();
        $params['token'] = $this->getToken();   
        $params['public_key'] = $this->getPagSeguroPubKey();    

        curl_setopt_array(
            $ch,
            array(
                CURLOPT_POSTFIELDS      => http_build_query($params),
                CURLOPT_POST            => count($params),
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_TIMEOUT         => 45,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => false,
            )
        );

        $response = null;

        try{
            $response = curl_exec($ch);
        }catch(Exception $e){
            return $e->getMessage();
        }

        $xml = \SimpleXML_Load_String($response);

        if (false === $xml) {
            if (curl_errno($ch) > 0) {
                $this->writeLog('PagSeguro API communication failure: ' . curl_error($ch));
            } else {
                $this->writeLog(
                    'Authentication failed with PagSeguro API. Check registered email and token.
                    Payback return: ' . $response
                );
            }
            return false;
        }

        return (string)$xml->id;
    }

    /**
     * Return merchant e-mail setup on admin
     * @return string
     */
    public function getMerchantEmail()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_EMAIL);
    }


    /**
     * Check if debug mode is active
     * @return bool
     */
    public function isDebugActive()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_DEBUG);
    }


     /**
     * Get PagSeguro Public key (if exists)
     * @return string
     */
    public function getPagSeguroPubKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_KEY);
    }


   /**
     * Write something to pagseguro.log
     * @param $obj mixed|string
     */
    public function writeLog($obj)
    {
        if ($this->isDebugActive()) {
            if (is_string($obj)) {
            	$this->_logger->debug($obj);
            } else {
                $this->_logger->debug(json_encode($obj));
            }
        }
    }

    /**
     * Get current. Return FALSE if empty.
     * @return string | false
     */
    public function getToken()
    {
        $token = $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_TOKEN);
        if (empty($token)) {
            return false;
        }

        return $token;
    }


	/**
     * Return serialized (json) string with module configuration
     * return string
     */
    public function getConfigJs()
    {
        $config = array(
            'active_methods' => array(
                'cc' => $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_CC_ACTIVE)
            ),
            'flag' => $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_CC_FLAG),
            'debug' => $this->isDebugActive(),
            'PagSeguroSessionId' => $this->getSessionId(),
            'is_admin' => 0,
            'show_total' => $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_CC_SHOW_TOTAL),
            'force_installments_selection' =>
                $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_CC_FORCE_INSTALLMENTS)
        );
        return json_encode($config);
    }


    /**
     * Return store base url
     * return string
     */
    public function getStoreUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }


     /**
     * Return GrandTotal
     * return decimal
     */
    public function getGrandTotal()
    {
        return  $this->checkoutSession->getQuote()->getGrandTotal();
    }

    /**
     * Return Installment Qty
     * return int
     */
    public function getInstallmentQty()
    {
        return  2;
    }
    
}
