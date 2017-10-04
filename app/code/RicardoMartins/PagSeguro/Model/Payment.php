<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RicardoMartins\PagSeguro\Model;

class Payment extends \Magento\Payment\Model\Method\Cc
{
    /**
     * @var string
     */
    protected $_formBlockType = \RicardoMartins\PagSeguro\Block\Form\Cc::class;

    const CODE = 'rm_pagseguro_cc';

    protected $_code = self::CODE;

    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;

    protected $_stripeApi = false;

    protected $_countryFactory;

    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = array('USD');

    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
    /**
     * PagSeguro Helper
     *
     * @var RicardoMartins\PagSeguro\Helper\Data;
     */ 
    protected $pagSeguroHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );

        $this->_countryFactory = $countryFactory;

        // $this->_minAmount = 1;
        // $this->_maxAmount = 999999999;
        $this->pagSeguroHelper = $pagSeguroHelper;  
    }

     /**
     * Payment capturing
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
         $order = $payment->getOrder();
         $this->pagSeguroHelper->writeLog('Inside order...');
         $this->pagSeguroHelper->writeLog(json_encode($order->getData()));
    }

    /**
     * Payment capturing
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /*@var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
         $this->pagSeguroHelper->writeLog('Inside capture...');
        $this->pagSeguroHelper->writeLog(json_encode($order->getData()));

        return $this;
    }

    /**
     * Payment refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // recupera a informação adicional do PagSeguro
        $info           = $this->getInfoInstance();
        $transactionId = $info->getAdditionalInformation('transaction_id');

        $params = array(
            'transactionCode'   => $transactionId,
            'refundValue'       => number_format($amount, 2, '.', ''),
        );
    
        $params['token'] = $this->pagSeguroHelper->getToken();
        $params['email'] = $this->pagSeguroHelper->getMerchantEmail();

        try {
           // call API - refund
            $returnXml  = $this->pagSeguroHelper->callApi($params, $payment, 'transactions/refunds');

            if ($returnXml === null) {
                $errorMsg = $this->_getHelper()->__('Erro ao solicitar o reembolso.\n');
                throw new \Magento\Framework\Validator\Exception($errorMsg);
            }
        } catch (\Exception $e) {
            $this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
            $this->logger->error(__('Payment refunding error.'));
            throw new \Magento\Framework\Validator\Exception(__('Payment refunding error.'));
        }

        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);

        return $this;
    }


    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  object
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
       
        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('sender_hash', $this->pagSeguroHelper->getPaymentHash('sender_hash'))
            ->setAdditionalInformation('credit_card_token', $this->pagSeguroHelper->getPaymentHash('credit_card_token'))
            ->setAdditionalInformation('credit_card_owner', $data->getPsCcOwner())
            ->setCcType($this->pagSeguroHelper->getPaymentHash('cc_type'))
            ->setCcLast4(substr($data->getPsCcNumber(), -4))
            ->setCcExpYear($data['additional_data']['cc_exp_year'])
            ->setCcExpMonth($data['additional_data']['cc_exp_month']);
         // $this->pagSeguroHelper->writeLog('manjutest'.json_encode($info->getData()));
         //  $this->pagSeguroHelper->writeLog('manjuData'.json_encode($data->getData()));

        //cpf
        // if ($this->pagSeguroHelper->isCpfVisible()) {
        //     $info->setAdditionalInformation($this->getCode() . '_cpf', $data->getData($this->getCode() . '_cpf'));
        // }

        //DOB
        // if ($this->pagSeguroHelper->isDobVisible()) {
        //     $info->setAdditionalInformation(
        //         'credit_card_owner_birthdate',
        //         date(
        //             'd/m/Y',
        //             strtotime(
        //                 $data->getPsCcOwnerBirthdayYear().
        //                 '/'.
        //                 $data->getPsCcOwnerBirthdayMonth().
        //                 '/'.$data->getPsCcOwnerBirthdayDay()
        //             )
        //         )
        //     );
        // }

        //Installments
        // if ($data->getPsCcInstallments()) {
        //     $installments = explode('|', $data->getPsCcInstallments());
        //     if (false !== $installments && count($installments)==2) {
        //         $info->setAdditionalInformation('installment_quantity', (int)$installments[0]);
        //         $info->setAdditionalInformation('installment_value', $installments[1]);
        //     }
        // }

        return $this;
    }


    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $isAvailable =  $this->getConfigData('active', $quote ? $quote->getStoreId() : null);
        if (empty($quote)) {
            return $isAvailable;
        }
        if ($this->getConfigData("group_restriction") == false) {
            return $isAvailable;
        }

        $currentGroupId = $quote->getCustomerGroupId();
        $customerGroups = explode(',', $this->getConfigData("customer_groups"));

        if ($isAvailable && in_array($currentGroupId, $customerGroups)) {
            return true;
        }

        return false;
    }

    /**
     * Availability for currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }


    /**
     * Validate payment method information object
     *
     * @return Payment Model
     */
    public function validate()
    {
        //parent::validate();
        $missingInfo = $this->getInfoInstance();


        $senderHash = $this->pagSeguroHelper->getPaymentHash('sender_hash');
        $creditCardToken = $this->pagSeguroHelper->getPaymentHash('credit_card_token');

        if (!$creditCardToken || !$senderHash) {
            $missingInfo = sprintf('Token do cartão: %s', var_export($creditCardToken, true));
            $missingInfo .= sprintf('/ Sender_hash: %s', var_export($senderHash, true));
            $this->pagSeguroHelper->writeLog(
                    "Falha ao obter o token do cartao ou sender_hash.
                    Ative o modo debug e observe o console de erros do seu navegador.
                    Se esta for uma atualização via Ajax, ignore esta mensagem até a finalização do pedido.
                    $missingInfo"
                );
            throw new \Magento\Framework\Validator\Exception(
                'Falha ao processar seu pagamento. Por favor, entre em contato com nossa equipe.'
            );
        }
        return $this;
    }

}
