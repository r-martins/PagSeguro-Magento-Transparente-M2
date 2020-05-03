<?php
namespace RicardoMartins\PagSeguro\Helper;

use Magento\Framework\Phrase;

/**
 * Class Data Helper
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_PAYMENT_PAGSEGURO_EMAIL              = 'payment/rm_pagseguro/merchant_email';
    const XML_PATH_PAYMENT_PAGSEGURO_TOKEN              = 'payment/rm_pagseguro/token';
    const XML_PATH_PAYMENT_PAGSEGURO_DEBUG              = 'payment/rm_pagseguro/debug';
    const XML_PATH_PAUMENT_PAGSEGURO_SANDBOX            = 'payment/rm_pagseguro/sandbox';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_EMAIL      = 'payment/rm_pagseguro/sandbox_merchant_email';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_TOKEN      = 'payment/rm_pagseguro/sandbox_token';
    //@TODO Remove hardcoded value in constant and move to config.xml defaults
    const XML_PATH_PAYMENT_PAGSEGURO_WS_URL             = 'https://ws.ricardomartins.net.br/pspro/v6/wspagseguro/v2/';
    const XML_PATH_PAYMENT_PAGSEGURO_WS_URL_APP         = 'payment/rm_pagseguro/ws_url_app';
    const XML_PATH_PAYMENT_PAGSEGURO_JS_URL             = 'payment/rm_pagseguro/js_url';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_WS_URL     = 'payment/rm_pagseguro/sandbox_ws_url';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_WS_URL_APP = 'payment/rm_pagseguro/sandbox_ws_url_app';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_JS_URL     = 'payment/rm_pagseguro/sandbox_js_url';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_ACTIVE          = 'payment/rm_pagseguro_cc/active';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_FLAG            = 'payment/rm_pagseguro_cc/flag';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_INFO_BRL        = 'payment/rm_pagseguro_cc/info_brl';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_SHOW_TOTAL      = 'payment/rm_pagseguro_cc/show_total';
    const XML_PATH_PAYMENT_PAGSEGURO_TEF_ACTIVE         = 'payment/rm_pagseguro_tef/active';
    const XML_PATH_PAYMENT_PAGSEGURO_BOLETO_ACTIVE      = 'payment/rm_pagseguro_boleto/active';
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
     * Quote Session
     *
     * @var  \Magento\Customer\Model\Customer
     */
    protected $customer;

    protected $authResponse;

    protected $_curl;

    /** @var \Magento\Framework\Serialize\SerializerInterface  */
    protected $serializer;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface       $storeManager
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Customer\Model\Customer                 $customer
     * @param \Magento\Framework\App\Helper\Context            $context
     * @param Logger                                           $loggerHelper
     * @param \Magento\Framework\App\ProductMetadataInterface  $productMetadata
     * @param \Magento\Framework\Module\ModuleListInterface    $moduleList
     * @param \Magento\Framework\HTTP\Client\Curl              $curl
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     */

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Framework\App\Helper\Context $context,
        \RicardoMartins\PagSeguro\Helper\Logger $loggerHelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Serialize\SerializerInterface $serializer
 
    ) {
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->customerRepo = $customer;
        $this->_logHelper  = $loggerHelper;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->_curl = $curl;
        $this->serializer = $serializer;

        parent::__construct($context);
    }

    /**
     * Returns session ID from PagSeguro that will be used on JavaScript methods.
     * or FALSE on failure
     * @return bool|string
     */
    public function getSessionId()
    {
        $url = $this->getWsUrl('sessions');
        //@TODO Replace forbidden curl_*
        $ch = curl_init($url);
        $params['email'] = $this->getMerchantEmail();
        $params['token'] = $this->getToken();   
        $params['public_key'] = $this->getPagSeguroPubKey();    

        //@TODO Replace curl
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
        }catch(\Exception $e){
            return $e->getMessage();
        }

        libxml_use_internal_errors(true);

        $this->authResponse = $response;
        $xml = \simplexml_load_string($response);

        if (false === $xml) {
            //@TODO Remove curl
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

    public function getAuthResponse() {
        return $this->authResponse;
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
            $this->_logHelper->writeLog($obj);
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
                'cc' => $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_CC_ACTIVE),
                'boleto' => $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_BOLETO_ACTIVE),
                'tef' => $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_TEF_ACTIVE)
            ),
            'flag' => $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_PAGSEGURO_CC_FLAG),
            'debug' => $this->isDebugActive(),
            'PagSeguroSessionId' => $this->getSessionId(),
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
       
        $psPayment = $this->serializer->unserialize($psPayment);
//         $this->writeLog('getPaymentHash'.json_encode($psPayment));
        if (is_null($param)) {
            return $psPayment;
        }

        if (isset($psPayment[$param])) {
            return $psPayment[$param];
        }

        return false;
    }

    /**
     * Get cc installment from session
     * @param string 
     * @return bool|string
     */
    public function getInstallments($param)
    {
        $ccinstallment = $this->checkoutSession->getData('installment');
        $ccinstallment = $this->serializer->unserialize($ccinstallment);

        if (isset($ccinstallment[$param])) {
            return $ccinstallment[$param];
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
        $customerDobAttribute = $this->scopeConfig->getValue('payment/rm_pagseguro_cc/owner_dob_attribute');
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

        $this->writeLog('Parameters being sent to API (/v2/'.$type.'): '. var_export($params, true));


        //@TODO Remove curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getWsUrl($type));
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());

        try{
            $response = curl_exec($ch);
        }catch(\Exception $e){
            throw new \Magento\Framework\Validator\Exception('Communication failure with Pagseguro (' . $e->getMessage() . ')');
        }

        //@TODO Remove curl
        if (curl_error($ch)) {
            //@TODO Remove curl
            $this->writeLog('-----Curl error response----: ' . var_export(curl_error($ch), true));
            throw new \Magento\Framework\Validator\Exception(curl_error($ch));
        }
        //@TODO Remove curl
        curl_close($ch);

        $this->writeLog('Retorno PagSeguro (/'.$type.'): ' . var_export($response, true));
        libxml_use_internal_errors(true);
        $xml = \simplexml_load_string(trim($response));

        if ($xml->error->code) {
                $errArray = array();
            $xmlError = json_decode(json_encode($xml), true);
            $xmlError = $xmlError['error'];
            
            if(isset($xmlError['code'])) {
                $errArray[] = $this->translateError($xmlError['message']);
            } else {
                foreach ($xmlError as $xmlErr) {
                    $errArray[] = $this->translateError($xmlErr['message']);
                }
            }
            
            $errArray = implode(" / ", $errArray);
            if($errArray) {
                throw new \Magento\Framework\Validator\Exception(new Phrase($errArray));
            }

            $this->setSessionVl($errArray);
        }

        if (false === $xml) {
            $errMsg = 'There was a problem processing your request / payment. Please contact us.';
            switch($response){
                case 'Unauthorized':
                    $this->writeLog(
                        'Token / email não autorizado no PagSeguro. Verifique as configurações do módulo.'
                    );
                    break;
                case 'Forbidden':
                    $this->writeLog('Acesso não autorizado à API do PagSeguro. Veja se você tem permissão e se a chave usada pertence à esta conta. Retorno do PagSeguro: ' . var_export($response, true)
                    );
                    break;
                default:
                    $this->writeLog('Retorno inesperado do PagSeguro: ' . $response);
                    $errMsg = 'There was a problem with PagSeguro communication. Could you try again?';
            }
            throw new \Magento\Framework\Validator\Exception(
                new Phrase($errMsg)
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
            'email'             => $this->getMerchantEmail(),
            'token'             => $this->getToken(),
            'paymentMode'       => 'default',
            'paymentMethod'     =>  'creditCard',
            'receiverEmail'     =>  $this->getMerchantEmail(),
            'currency'          => 'BRL',
            'creditCardToken'   => $payment->getAdditionalInformation('credit_card_token'),
            'reference'         => $order->getIncrementId(),
            'extraAmount'       => $this->getExtraAmount($order),
            'notificationURL'   => $this->getStoreUrl().'pseguro/notification/index',
        );
        $params = array_merge($params, $this->getItemsParams($order));
        $params = array_merge($params, $this->getSenderParams($order, $payment));
        $params = array_merge($params, $this->getAddressParams($order, 'shipping'));
        $params = array_merge($params, $this->getAddressParams($order, 'billing'));
        $params = array_merge($params, $this->getCreditCardHolderParams($order, $payment));
        $params = array_merge($params, $this->getCreditCardInstallmentsParams($order, $payment));

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
        $orderItems = $this->getAllVisibleItems($order);
        foreach ($orderItems as $item) {
            if ($item->getPrice() == 0) {
                $extra -= 0.01 * $item->getQtyOrdered();
            }
        }
        return number_format($extra, 2, '.', '');
    }

     /**
     * Return items information, to be send to API
     * @param Magento\Sales\Model\Order $order
     * @return array
     */
    public function getItemsParams(\Magento\Sales\Model\Order $order)
    {
        $return = array();
        $items = $this->getAllVisibleItems($order);
        if ($items) {
            $itemsCount = count($items);
            for ($x=1, $y=0; $x <= $itemsCount; $x++, $y++) {
                $itemPrice = $items[$y]->getPrice();
                $qtyOrdered = $items[$y]->getQtyOrdered();
                $return['itemId'.$x] = $items[$y]->getId()? $items[$y]->getId() : $items[$y]->getData('quote_item_id');
                $return['itemDescription'.$x] = substr($items[$y]->getName(), 0, 100);
                $return['itemAmount'.$x] = number_format($itemPrice, 2, '.', '');
                $return['itemQuantity'.$x] = (int)$qtyOrdered;

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
    public function getSenderParams(\Magento\Sales\Model\Order $order, $payment)
    {
        $digits = new \Zend\Filter\Digits();
        $cpf = $this->getCustomerCpfValue($order, $payment);

        $phone = $this->extractPhone($order->getBillingAddress()->getTelephone());

        $return = array(
            'senderName'    => $this->getSenderName($order),
            'senderEmail'   => trim($order->getCustomerEmail()),
            'senderHash'    => $this->getPaymentHash('sender_hash'),
            'senderCPF'     => $digits->filter($cpf),
            'senderAreaCode'=> $phone['area'],
            'senderPhone'   => $phone['number'],
        );
        if (strlen($return['senderCPF']) > 11) {
            $return['senderCNPJ'] = $return['senderCPF'];
            unset($return['senderCPF']);
        }

        return $return;
    }

    /**
     * Returns an array with credit card's owner (Customer) to be used on API
     * @param Magento\Sales\Model\Order $order
     * @param $payment
     * @return array
     */
    public function getCreditCardHolderParams(\Magento\Sales\Model\Order $order, $payment)
    {
        $digits = new \Zend\Filter\Digits();
        $cpf = $this->getCustomerCpfValue($order, $payment);

        //data
        $customer = $this->customerRepo->load($order->getCustomerId());
        $creditCardHolderBirthDate = $this->getCustomerCcDobValue($customer, $payment);
        $phone = $this->extractPhone($order->getBillingAddress()->getTelephone());


        $holderName = $this->removeDuplicatedSpaces($payment['additional_information']['credit_card_owner']);
        $return = array(
            'creditCardHolderName'      => $holderName,
            'creditCardHolderBirthDate' => $creditCardHolderBirthDate,
            'creditCardHolderCPF'       => $digits->filter($cpf),
            'creditCardHolderAreaCode'  => $phone['area'],
            'creditCardHolderPhone'     => $phone['number'],
        );

        return $return;
    }

    /**
     * Return an array with installment information to be used with API
     * @param Magento\Sales\Model\Order $order
     * @param $payment Magento\Sales\Model\Order\Payment
     * @return array
     */
    public function getCreditCardInstallmentsParams(\Magento\Sales\Model\Order $order, $payment)
    {
        $return = array();
        if ($payment->getAdditionalInformation('installment_quantity')
            && $payment->getAdditionalInformation('installment_value')) {
            $return = array(
                'installmentQuantity'   => $payment->getAdditionalInformation('installment_quantity'),
                'installmentValue'      => number_format(
                    $payment->getAdditionalInformation('installment_value'), 2, '.', ''
                ),
            );
        } else {
            $return = array(
                'installmentQuantity'   => '1',
                'installmentValue'      => number_format($order->getGrandTotal(), 2, '.', ''),
            );
        }
        return $return;
    }

    /**
     * Return an array with address (shipping/billing) information to be used on API
     * @param Magento\Sales\Model\Order $order
     * @param string (billing|shipping) $type
     * @return array
     */
    public function getAddressParams(\Magento\Sales\Model\Order $order, $type)
    {
        $digits = new \Zend\Filter\Digits();

        //address attributes
        /** @var Mage_Sales_Model_Order_Address $address */
        $address = ($type=='shipping' && !$order->getIsVirtual()) ?
        $order->getShippingAddress() : $order->getBillingAddress();
        $addressStreetAttribute = $this->scopeConfig->getValue('payment/rm_pagseguro/address_street_attribute');
        $addressNumberAttribute = $this->scopeConfig->getValue('payment/rm_pagseguro/address_number_attribute');
        $addressComplementAttribute = $this->scopeConfig->getValue('payment/rm_pagseguro/address_complement_attribute');
        $addressNeighborhoodAttribute = $this->scopeConfig->getValue('payment/rm_pagseguro/address_neighborhood_attribute');

        //gathering address data
        $addressStreet = $this->getAddressAttributeValue($address, $addressStreetAttribute);
        $addressNumber = $this->getAddressAttributeValue($address, $addressNumberAttribute);
        $addressComplement = $this->getAddressAttributeValue($address, $addressComplementAttribute);
        $addressDistrict = $this->getAddressAttributeValue($address, $addressNeighborhoodAttribute);
        $addressPostalCode = $digits->filter($address->getPostcode());
        $addressCity = $address->getCity();
        $addressState = $this->getStateCode($address->getRegion());

        $return = array(
            $type.'AddressStreet'     => substr($addressStreet, 0, 80),
            $type.'AddressNumber'     => substr($addressNumber, 0, 20),
            $type.'AddressComplement' => substr($addressComplement, 0, 40),
            $type.'AddressDistrict'   => substr($addressDistrict, 0, 60),
            $type.'AddressPostalCode' => $addressPostalCode,
            $type.'AddressCity'       => substr($addressCity, 0, 60),
            $type.'AddressState'      => $addressState,
            $type.'AddressCountry'    => 'BRA',
        );

        //shipping specific
        if ($type == 'shipping') {
            $shippingType = $this->getShippingType($order);
            $shippingCost = $order->getShippingAmount();
            $return['shippingType'] = $shippingType;
            if ($shippingCost > 0) {
                if ($this->shouldSplit($order)) {
                    $shippingCost -= 0.01;
                }
                $return['shippingCost'] = number_format($shippingCost, 2, '.', '');
            }else {
                $return['shippingCost'] = '0.00';
            }
        }
        return $return;
    }

    /**
     * Returns customer's CPF based on your module configuration
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Payment_Model_Method_Abstract $payment
     *
     * @return mixed
     */
    private function getCustomerCpfValue(\Magento\Sales\Model\Order $order, $payment)
    {
        $customerCpfAttribute = $this->scopeConfig->getValue('payment/rm_pagseguro/customer_cpf_attribute');

        if (empty($customerCpfAttribute)) { //Asked with payment data
            if (isset($payment['additional_information'][$payment->getMethod() . '_cpf'])) {
                return $payment['additional_information'][$payment->getMethod() . '_cpf'];
            }
        }
        $entity = explode('|', $customerCpfAttribute);
        $cpf = '';
        if (count($entity) == 1 || $entity[0] == 'customer') {
            if (count($entity) == 2) {
                $customerCpfAttribute = $entity[1];
            }
            $customer = $order->getCustomer();

            $cpf = $order->getData('customer_' . $customerCpfAttribute);
        } else if (count($entity) == 2 && $entity[0] == 'billing' ) { //billing
            $cpf = $order->getShippingAddress()->getData($entity[1]);
        }

        if ($order->getCustomerIsGuest() && empty($cpf)) {
            $cpf = $order->getData('customer_' . $customerCpfAttribute);
        }

        $cpfObj = new \Magento\Framework\DataObject(array('cpf'=>$cpf));

        return $cpfObj->getCpf();
    }

     /**
     * Extracts phone area code and returns phone number, with area code as key of the returned array
     * @author Ricardo Martins <ricardo@ricardomartins.net.br>
     * @param string $phone
     * @return array
     */
    private function extractPhone($phone)
    {
        $digits = new \Zend\Filter\Digits();
        $phone = $digits->filter($phone);
        //se começar com zero, pula o primeiro digito
        if (substr($phone, 0, 1) == '0') {
            $phone = substr($phone, 1, strlen($phone));
        }
        $originalPhone = $phone;

        $phone = preg_replace('/^(\d{2})(\d{7,9})$/', '$1-$2', $phone);

        if (is_array($phone) && count($phone) == 2) {
            list($area, $number) = explode('-', $phone);
            return array(
                'area' => $area,
                'number'=>$number
            );
        }

        return array(
            'area' => (string)substr($originalPhone, 0, 2),
            'number'=> (string)substr($originalPhone, 2, 9),
        );
    }

    /**
     * Remove duplicated spaces from string
     * @param $string
     * @return string
     */
    public function removeDuplicatedSpaces($string)
    {
        $string = static::normalizeChars($string);

        return preg_replace('/\s+/', ' ', trim($string));
    }

     /**
     * Replace language-specific characters by ASCII-equivalents.
     * @see http://stackoverflow.com/a/16427125/529403
     * @param string $s
     * @return string
     */
    public static function normalizeChars($s)
    {
        $replace = array(
            'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'È' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ñ' => 'N', 'Ò' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
            'ä' => 'a', 'ã' => 'a', 'á' => 'a', 'à' => 'a', 'å' => 'a', 'æ' => 'ae', 'è' => 'e', 'ë' => 'e', 'ì' => 'i',
            'í' => 'i', 'î' => 'i', 'ï' => 'i', 'Ã' => 'A', 'Õ' => 'O',
            'ñ' => 'n', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'ú', 'û' => 'u', 'ü' => 'ý', 'ÿ' => 'y',
            'Œ' => 'OE', 'œ' => 'oe', 'Š' => 'š', 'Ÿ' => 'Y', 'ƒ' => 'f', 'Ğ'=>'G', 'ğ'=>'g', 'Š'=>'S',
            'š'=>'s', 'Ş'=>'S', 'ș'=>'s', 'Ș'=>'S', 'ş'=>'s', 'ț'=>'t', 'Ț'=>'T', 'ÿ'=>'y', 'Ž'=>'Z', 'ž'=>'z'
        );
        return preg_replace('/[^0-9A-Za-zÃÁÀÂÇÉÊÍÕÓÔÚÜãáàâçéêíõóôúü.\-\/ ]/u', '', strtr($s, $replace));
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
        foreach ($this->getAllVisibleItems($order) as $item) {
            $totalAmount += $item->getRowTotal();
        }
        return (abs($extraAmount) == $totalAmount);
    }

     /**
     * Return shipping code based on PagSeguro Documentation
     * 1 – PAC, 2 – SEDEX, 3 - Desconhecido
     * @param \Magento\Sales\Model\Order $order
     *
     * @return string
     */
    private function getShippingType(\Magento\Sales\Model\Order $order)
    {
        $method =  strtolower($order->getShippingMethod());
        if (strstr($method, 'pac') !== false) {
            return '1';
        } else if (strstr($method, 'sedex') !== false) {
            return '2';
        }
        return '3';
    }

     /**
     * Gets the shipping attribute based on one of the id's from
     * RicardoMartins_PagSeguro_Model_Source_Customer_Address_*
     *
     * @param \Magento\Sales\Model\Order\Address $address
     * @param string $attributeId
     *
     * @return string
     */
    private function getAddressAttributeValue($address, $attributeId)
    {
        $isStreetline = preg_match('/^street_(\d{1})$/', $attributeId, $matches);

        if ($isStreetline !== false && isset($matches[1])) { //uses streetlines
             $street[1] = $address->getStreetLine(1); //street
             $street[2] = $address->getStreetLine(2); //number
             $street[3] = !$address->getStreetLine(4) ? '' : $address->getStreetLine(3); // complement
             $street[4] = !$address->getStreetLine(4) ? $address->getStreetLine(3) : $address->getStreetLine(4); // neighborhood
             $lineNum = (int)$matches[1];
             return $street[$lineNum];
        } else if ($attributeId == '') { //do not tell pagseguro
            return '';
        }
        return (string)$address->getData($attributeId);
    }

    /**
     * Returns customer's date of birthday, based on your module configuration or return a default date
     * @param Magento\Customer\Model\Customer $customer
     * @param                              $payment
     *
     * @return mixed
     */
    private function getCustomerCcDobValue(\Magento\Customer\Model\Customer $customer, $payment)
    {
        $ccDobAttribute = $this->scopeConfig->getValue('payment/rm_pagseguro_cc/owner_dob_attribute');

        if (empty($ccDobAttribute)) { //when asked with payment data
            if (isset($payment['additional_information']['credit_card_owner_birthdate'])) {
                return $payment['additional_information']['credit_card_owner_birthdate'];
            }
        }

        //try to get from payment info
        $dob = $payment->getOrder()->getData('customer_' . $ccDobAttribute);
        if (!empty($dob)) {
            return date('d/m/Y', strtotime($dob));
        }

        //try to get from customer
        $attribute = $customer->getResource()->getAttribute($ccDobAttribute);
        if (!$attribute) {
            return '01/01/1970';
        }
        $dob = $attribute->getFrontend()->getValue($customer);


        return date('d/m/Y', strtotime($dob));
    }

    /**
     * Get BR State code even if it was typed manually
     * @param $state
     *
     * @return string
     */
    public function getStateCode($state)
    {
        if(strlen($state) == 2 && is_string($state))
        {
            return mb_convert_case($state,MB_CASE_UPPER);
        }
        else if(strlen($state) > 2 && is_string($state))
        {
            $state = static::normalizeChars($state);
            $state = trim($state);
            $state = $this->stripAccents($state);
            $state = mb_convert_case($state, MB_CASE_UPPER);
            $codes = array(
                'AC'=>'ACRE',
                'AL'=>'ALAGOAS',
                'AM'=>'AMAZONAS',
                'AP'=>'AMAPA',
                'BA'=>'BAHIA',
                'CE'=>'CEARA',
                'DF'=>'DISTRITO FEDERAL',
                'ES'=>'ESPIRITO SANTO',
                'GO'=>'GOIAS',
                'MA'=>'MARANHAO',
                'MT'=>'MATO GROSSO',
                'MS'=>'MATO GROSSO DO SUL',
                'MG'=>'MINAS GERAIS',
                'PA'=>'PARA',
                'PB'=>'PARAIBA',
                'PR'=>'PARANA',
                'PE'=>'PERNAMBUCO',
                'PI'=>'PIAUI',
                'RJ'=>'RIO DE JANEIRO',
                'RN'=>'RIO GRANDE DO NORTE',
                'RO'=>'RONDONIA',
                'RS'=>'RIO GRANDE DO SUL',
                'RR'=>'RORAIMA',
                'SC'=>'SANTA CATARINA',
                'SE'=>'SERGIPE',
                'SP'=>'SAO PAULO',
                'TO'=>'TOCANTINS'
            );
            $code = array_search($state, $codes);
            if (false !== $code) {
                return $code;
            }
        }
        return $state;
    }

    /**
     * Replace accented characters
     * @param $string
     *
     * @return string
     */
    public function stripAccents($string)
    {
        return preg_replace('/[`^~\'"]/', null, iconv('UTF-8', 'ASCII//TRANSLIT', $string));
    }

    /**
     * Returns Webservice URL based on selected environment (prod or sandbox)
     *
     * @param string $amend suffix
     * @param bool $useApp uses app?
     *
     * @return string
     */
    public function getWsUrl($amend ='', $useApp = false)
    {
        return self::XML_PATH_PAYMENT_PAGSEGURO_WS_URL.$amend;
    }

    /**
     * Returns Store config value
     *
     * @param string
     * @return string/bool
     */
    public function getStoreConfigValue($scopeConfigPath)
    {
        return  $this->scopeConfig->getValue($scopeConfigPath);
    }

   
    public function setSessionVl($value)
    {
        return $this->checkoutSession->setCustomparam($value);
    }

    public function getSessionVl()
    {
        return $this->checkoutSession->getCustomparam();
    }

    public function getModuleInformation()
    {
        return $this->moduleList->getOne('RicardoMartins_PagSeguro');
    }

    public function getMagentoVersion() {
        return $this->productMetadata->getVersion();
    }

    /**
     * Validate public key
     */
    public function validateKey() {


        //@TODO Remove hardcoded url
        $pubKey = $this->getPagSeguroPubKey();
        if(empty($pubKey)){
            return 'Public Key is empty.';
        }

        $url = 'http://ws.ricardomartins.net.br/pspro/v6/auth/' . $pubKey;
        $this->_curl->get($url);

        return $this->_curl->getBody();
    }

    
    public function getBoletoApiCallParams($order, $payment)
    {
        $params = array(
            'email' => $this->getMerchantEmail(),
            'token' => $this->getToken(),
            'paymentMode'   => 'default',
            'paymentMethod' =>  'boleto',
            'receiverEmail' =>  $this->getMerchantEmail(),
            'currency'  => 'BRL',
            'reference'     => $order->getIncrementId(),
            'extraAmount'=> $this->getExtraAmount($order),
            'notificationURL' => $this->getStoreUrl().'pseguro/notification/index',
        );
        
        $params = array_merge($params, $this->getItemsParams($order));
        $params = array_merge($params, $this->getSenderParams($order, $payment));
        $params = array_merge($params, $this->getAddressParams($order, 'shipping'));
        $params = array_merge($params, $this->getAddressParams($order, 'billing'));
        
        return $params;

    }
    
    public function getTefApiCallParams($order, $payment)
    {
        $params = $this->getBoletoApiCallParams($order, $payment);
        $params['paymentMethod'] = 'eft';
        $params['bankName'] = $payment['additional_information']['tef_bank'];
        return $params;
    }

    /**
     * Translate dynamic words from PagSeguro errors and messages
     * @author Ricardo Martins
     * @return string
     */
    public function translateError()
    {
        $args = func_get_args();
        $text = $args[0];
        preg_match('/(.*)\:(.*)/', $text, $matches);
        if ($matches!==false && isset($matches[1])) {
            array_shift($matches);
            $matches[0] .= ': %1';
            $args = $matches;
        }
        return call_user_func_array('__', $args);
    }
  
    /**
     * Sends the header details.
     * @author Ricardo Martins
     * @return Array
     */
    public function getHeaders()
    {
        $moduleVersion = $this->moduleList->getOne('RicardoMartins_PagSeguro')['setup_version'];
        $headers = array('Platform: Magento', 'Platform-Version: ' . $this->getMagentoVersion(), 'Module-Version: ' . $moduleVersion);

        return $headers;
    }

    /**
     *  Returns associative array with required parameters to API, used on in redirect payment method
     * @param $order
     * @param $payment
     *
     * @return array
     */
    public function getRedirectParams($order, $payment)
    {
        $phone = $this->extractPhone($order->getBillingAddress()->getTelephone());

        $enableRecover = $this->scopeConfig->getValue('payment/rm_pagseguro_pagar_no_pagseguro/enable_recovery') ? 'true' : 'false';
        $paymentAcceptedGroups = $this->scopeConfig->getValue('payment/rm_pagseguro_pagar_no_pagseguro/accepted_groups');

        $params = array(
            'email' => $this->getMerchantEmail(),
            'paymentMethod' => 'redirect',
            'currency' => 'BRL',
            'reference' => $order->getIncrementId(),
            'extraAmount' => $this->getExtraAmount($order),
            'senderName' => $this->getSenderName($order),
            'senderAreaCode' => $phone['area'],
            'senderPhone' => $phone['number'],
            'senderEmail' => trim($order->getCustomerEmail()),
            'enableRecover' => $enableRecover,
            'shippingAddressRequired' => '',
            'acceptPaymentMethodGroup' => $paymentAcceptedGroups,
            'notificationURL'   => $this->getStoreUrl().'pseguro/notification/index',
        );

        $redirectURL = $this->scopeConfig->getValue('payment/rm_pagseguro_pagar_no_pagseguro/redirectURL');
        if ($redirectURL) {
            $params['redirectURL'] = $this->_urlBuilder->getUrl($redirectURL);
        }

        $params = array_merge($params, $this->getItemsParams($order));
        $params = array_merge($params, $this->getAddressParams($order, 'shipping'));
        $params = array_merge($params, $this->getAddressParams($order, 'billing'));

        return $params;
    }

    /**
     *  Returns Sender Name
     * @param $order
     *
     * @return string
     */
    public function getSenderName($order)
    {
        if ($order->getCustomerIsGuest()) {
            $senderName = $this->removeDuplicatedSpaces(
            sprintf('%s %s', $order->getBillingAddress()->getFirstname(), $order->getBillingAddress()->getLastname())
            );
        } else {
            $senderName = $this->removeDuplicatedSpaces(
                sprintf('%s %s', $order->getCustomerFirstname(), $order->getCustomerLastname())
            );
        }

        $senderName = substr($senderName, 0, 50);

        return $senderName;
    }

    /**
     * Retrieves visible products of the order, omitting its children (yes, this is different than Magento's method)
     * @param Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function getAllVisibleItems($order)
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            if (!$item->isDeleted() && !$item->getParentItem()) {
                $items[] = $item;
            }
        }
        return $items;
    }
}
