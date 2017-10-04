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
     * Get payment hashes (sender_hash & credit_card_token) from session
     * @param string 
     * @return bool|string
     */
    public function getPaymentHash($param = null)
    {
        $psPayment = $this->checkoutSession->getData('PsPayment');
        //$this->writeLog('manjuHelper'.json_encode($psPayment));
        $psPayment = unserialize($psPayment);

        if (is_null($param)) {
            return $psPayment;
        }

        if (isset($psPayment[$param])) {
            return $psPayment[$param];
        }

        return false;
    }

     /**
     * Check if CPF should be visible with other payment fields
     * @return bool
     */
    public function isCpfVisible()
    {
        $customerCpfAttribute = $this->scopeConfig->getValue('payment/rm_pagseguro/customer_cpf_attribute');
        return empty($customerCpfAttribute);
    }


     /**
     * Check if DOB should be visible with other payment fields
     * @return bool
     */
    public function isDobVisible()
    {
        $customerDobAttribute = $this->scopeConfig->getValue('payment/rm_pagseguro/owner_dob_attribute');
        return empty($customerDobAttribute);
    }


    /**
     * Return Installment Qty
     * return int
     */
    public function getInstallmentQty()
    {
        return  2;
    }


    /**
     * Call PagSeguro API to place an order (/transactions)
     * @param $params
     * @param $payment
     * @param $type
     *
     * @return SimpleXMLElement
     */
    public function callApi($params, $payment, $type='transactions')
    {
        $params['public_key'] = $this->getPagSeguroPubKey();
        $params = $this->convertEncoding($params);
        $paramsObj = new \Magento\Framework\DataObject(array('params'=>$params));

        $params = $paramsObj->getParams();
        $paramsString = $this->convertToCURLString($params);

        $this->writeLog('Parametros sendo enviados para API (/'.$type.'): '. var_export($params, true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $helper->getWsUrl($type, $useApp));
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        try{
            $response = curl_exec($ch);
        }catch(Exception $e){
            throw new \Magento\Framework\Validator\Exception('Falha na comunicação com Pagseguro (' . $e->getMessage() . ')');
        }

        if (curl_error($ch)) {
            throw new \Magento\Framework\Validator\Exception(curl_error($ch));
        }
        curl_close($ch);

        $this->writeLog('Retorno PagSeguro (/'.$type.'): ' . var_export($response, true));

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(trim($response));

        if (false === $xml) {
            switch($response){
                case 'Unauthorized':
                    $this->writeLog(
                        'Token/email não autorizado pelo PagSeguro. Verifique suas configurações no painel.'
                    );
                    break;
                case 'Forbidden':
                    $this->writeLog(
                        'Acesso não autorizado à Api Pagseguro. Verifique se você tem permissão para
                         usar este serviço. Retorno: ' . var_export($response, true)
                    );
                    break;
                default:
                    $this->writeLog('Retorno inesperado do PagSeguro. Retorno: ' . $response);
            }
            throw new \Magento\Framework\Validator\Exception(
                'Houve uma falha ao processar seu pedido/pagamento. Por favor entre em contato conosco.'
            );
        }

        return $xml;
    }


    /**
     * Convert array values to utf-8
     * @param array $params
     *
     * @return array
     */
    protected function convertEncoding(array $params)
    {
        foreach ($params as $k => $v) {
            $params[$k] = utf8_decode($v);
        }
        return $params;
    }


    /**
     * Convert API params (already ISO-8859-1) to url format (curl string)
     * @param array $params
     *
     * @return string
     */
    protected function convertToCURLString(array $params)
    {
        $fieldsString = '';
        foreach ($params as $k => $v) {
            $fieldsString .= $k.'='.urlencode($v).'&';
        }
        return rtrim($fieldsString, '&');
    }


    /**
     * Returns associative array with required parameters to API, used on CC method calls
     * @return array
     */
    public function getCreditCardApiCallParams(\Magento\Sales\Model\Order $order, $payment)
    {
        
        $params = array(
        'email'                 => $this->getMerchantEmail(),
            'token'             => $this->getToken(),
            'paymentMode'       => 'default',
            'paymentMethod'     =>  'creditCard',
            'receiverEmail'     =>  $this->getMerchantEmail(),
            'currency'          => 'BRL',
            'creditCardToken'   => $this->getPaymentHash('credit_card_token'),
            'reference'         => $order->getIncrementId(),
            'extraAmount'       => $this->getExtraAmount($order),
            'notificationURL'   => $this->getStoreUrl().'ricardomartins_pagseguro/notification',
        );
        $params = array_merge($params, $this->getItemsParams($order));
        // $params = array_merge($params, $this->getSenderParams($order, $payment));
        // $params = array_merge($params, $this->getAddressParams($order, 'shipping'));
        // $params = array_merge($params, $this->getAddressParams($order, 'billing'));
        // $params = array_merge($params, $this->getCreditCardHolderParams($order, $payment));
        // $params = array_merge($params, $this->getCreditCardInstallmentsParams($order, $payment));

        return $params;
    }


    /**
     * Calculates the "Exta" value that corresponds to Tax values minus Discount given
     * It makes the correct discount to be shown correctly on PagSeguro
     * @param Mage_Sales_Model_Order $order
     *
     * @return float
     */
    public function getExtraAmount($order)
    {
        $discount = $order->getDiscountAmount();
        $taxAmount = $order->getTaxAmount();
        $extra = $discount + $taxAmount;

        if ($this->shouldSplit($order)) {
            $extra += 0.01;
        }

        //Discounting gift products
        $orderItems = $order->getAllVisibleItems();
        foreach ($orderItems as $item) {
            if ($item->getPrice() == 0) {
                $extra -= 0.01 * $item->getQtyOrdered();
            }
        }
        return number_format($extra, 2, '.', '');
    }


    /**
     * Should split shipping? If grand total is equal to discount total.
     * PagSeguro needs to receive product values > R$0,00, even if you need to invoice only shipping
     * and would like to give producs for free.
     * In these cases, splitting will add R$0,01 for each product, reducing R$0,01 from shipping total.
     *
     * @param $order
     *
     * @return bool
     */
    private function shouldSplit($order)
    {
        $discount = $order->getDiscountAmount();
        $taxAmount = $order->getTaxAmount();
        $extraAmount = $discount + $taxAmount;

        $totalAmount = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $totalAmount += $item->getRowTotal();
        }
        return (abs($extraAmount) == $totalAmount);
    }


     /**
     * Return items information, to be send to API
     * @param Magento\Sales\Model\Order $order
     * @return array
     */
    public function getItemsParams(\Magento\Sales\Model\Order $order)
    {
        $return = array();
        $items = $order->getAllVisibleItems();
        if ($items) {
            $itemsCount = count($items);
            for ($x=1, $y=0; $x <= $itemsCount; $x++, $y++) {
                $itemPrice = $items[$y]->getPrice();
                $qtyOrdered = $items[$y]->getQtyOrdered();
                $return['itemId'.$x] = $items[$y]->getId();
                $return['itemDescription'.$x] = substr($items[$y]->getName(), 0, 100);
                $return['itemAmount'.$x] = number_format($itemPrice, 2, '.', '');
                $return['itemQuantity'.$x] = $qtyOrdered;

                //We can't send 0.00 as value to PagSeguro. Will be discounted on extraAmount.
                if ($itemPrice == 0) {
                    $return['itemAmount'.$x] = 0.01;
                }
            }
        }
        return $return;
    }

    /**
     * Return an array with Sender(Customer) information to be used on API call
     *
     * @param Magento\Sales\Model\Order $order
     * @param $payment
     * @return array
     */
    // public function getSenderParams(\Magento\Sales\Model\Order $order, $payment)
    // {
    //     $digits = new Zend_Filter_Digits();
    //     $cpf = $this->_getCustomerCpfValue($order, $payment);

    //     $phone = $this->_extractPhone($order->getBillingAddress()->getTelephone());

    //     $senderName = $this->removeDuplicatedSpaces(
    //         sprintf('%s %s', $order->getCustomerFirstname(), $order->getCustomerLastname())
    //     );

    //     $senderName = substr($senderName, 0, 50);

    //     $return = array(
    //         'senderName'    => $senderName,
    //         'senderEmail'   => trim($order->getCustomerEmail()),
    //         'senderHash'    => $this->getPaymentHash('sender_hash'),
    //         'senderCPF'     => $digits->filter($cpf),
    //         'senderAreaCode'=> $phone['area'],
    //         'senderPhone'   => $phone['number'],
    //     );
    //     if (strlen($return['senderCPF']) > 11) {
    //         $return['senderCNPJ'] = $return['senderCPF'];
    //         unset($return['senderCPF']);
    //     }

    //     return $return;
    // }

    // /**
    //  * Returns an array with credit card's owner (Customer) to be used on API
    //  * @param Magento\Sales\Model\Order $order
    //  * @param $payment
    //  * @return array
    //  */
    // public function getCreditCardHolderParams(\Magento\Sales\Model\Order $order, $payment)
    // {
    //     $digits = new Zend_Filter_Digits();

    //     $cpf = $this->_getCustomerCpfValue($order, $payment);

    //     //data
    //     $creditCardHolderBirthDate = $this->_getCustomerCcDobValue($order->getCustomer(), $payment);
    //     $phone = $this->_extractPhone($order->getBillingAddress()->getTelephone());


    //     $holderName = $this->removeDuplicatedSpaces($payment['additional_information']['credit_card_owner']);
    //     $return = array(
    //         'creditCardHolderName'      => $holderName,
    //         'creditCardHolderBirthDate' => $creditCardHolderBirthDate,
    //         'creditCardHolderCPF'       => $digits->filter($cpf),
    //         'creditCardHolderAreaCode'  => $phone['area'],
    //         'creditCardHolderPhone'     => $phone['number'],
    //     );

    //     return $return;
    // }

    // /**
    //  * Return an array with installment information to be used with API
    //  * @param Magento\Sales\Model\Order $order
    //  * @param $payment Magento\Sales\Model\Order\Payment
    //  * @return array
    //  */
    // public function getCreditCardInstallmentsParams(\Magento\Sales\Model\Order $order, $payment)
    // {
    //     $return = array();
    //     if ($payment->getAdditionalInformation('installment_quantity')
    //         && $payment->getAdditionalInformation('installment_value')) {
    //         $return = array(
    //             'installmentQuantity'   => $payment->getAdditionalInformation('installment_quantity'),
    //             'installmentValue'      => number_format(
    //                 $payment->getAdditionalInformation('installment_value'), 2, '.', ''
    //             ),
    //         );
    //     } else {
    //         $return = array(
    //             'installmentQuantity'   => '1',
    //             'installmentValue'      => number_format($order->getGrandTotal(), 2, '.', ''),
    //         );
    //     }
    //     return $return;
    // }


    // /**
    //  * Return an array with address (shipping/billing) information to be used on API
    //  * @param Magento\Sales\Model\Order $order
    //  * @param string (billing|shipping) $type
    //  * @return array
    //  */
    // public function getAddressParams(\Magento\Sales\Model\Order $order, $type)
    // {
    //     $digits = new Zend_Filter_Digits();

    //     //address attributes
    //     /** @var Mage_Sales_Model_Order_Address $address */
    //     $address = ($type=='shipping' && !$order->getIsVirtual()) ?
    //         $order->getShippingAddress() : $order->getBillingAddress();
    //     $addressStreetAttribute = Mage::getStoreConfig('payment/rm_pagseguro/address_street_attribute');
    //     $addressNumberAttribute = Mage::getStoreConfig('payment/rm_pagseguro/address_number_attribute');
    //     $addressComplementAttribute = Mage::getStoreConfig('payment/rm_pagseguro/address_complement_attribute');
    //     $addressNeighborhoodAttribute = Mage::getStoreConfig('payment/rm_pagseguro/address_neighborhood_attribute');

    //     //gathering address data
    //     $addressStreet = $this->_getAddressAttributeValue($address, $addressStreetAttribute);
    //     $addressNumber = $this->_getAddressAttributeValue($address, $addressNumberAttribute);
    //     $addressComplement = $this->_getAddressAttributeValue($address, $addressComplementAttribute);
    //     $addressDistrict = $this->_getAddressAttributeValue($address, $addressNeighborhoodAttribute);
    //     $addressPostalCode = $digits->filter($address->getPostcode());
    //     $addressCity = $address->getCity();
    //     $addressState = $this->getStateCode($address->getRegion());


    //     $return = array(
    //         $type.'AddressStreet'     => substr($addressStreet, 0, 80),
    //         $type.'AddressNumber'     => substr($addressNumber, 0, 20),
    //         $type.'AddressComplement' => substr($addressComplement, 0, 40),
    //         $type.'AddressDistrict'   => substr($addressDistrict, 0, 60),
    //         $type.'AddressPostalCode' => $addressPostalCode,
    //         $type.'AddressCity'       => substr($addressCity, 0, 60),
    //         $type.'AddressState'      => $addressState,
    //         $type.'AddressCountry'    => 'BRA',
    //     );

    //     //shipping specific
    //     if ($type == 'shipping') {
    //         $shippingType = $this->_getShippingType($order);
    //         $shippingCost = $order->getShippingAmount();
    //         $return['shippingType'] = $shippingType;
    //         if ($shippingCost > 0) {
    //             if ($this->_shouldSplit($order)) {
    //                 $shippingCost -= 0.01;
    //             }
    //             $return['shippingCost'] = number_format($shippingCost, 2, '.', '');
    //         }
    //     }
    //     return $return;
    // }
    
}
