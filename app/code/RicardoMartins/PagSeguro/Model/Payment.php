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

        $this->_minAmount = 1;
        $this->_maxAmount = 999999999;
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
        //throw new \Magento\Framework\Validator\Exception(__('Inside Stripe, throwing donuts :]'));

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();

        try {
            // $requestData = [
            //     'amount'        => $amount * 100,
            //     'currency'      => strtolower($order->getBaseCurrencyCode()),
            //     'description'   => sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail()),
            //     'card'          => [
            //         'number'            => $payment->getCcNumber(),
            //         'exp_month'         => sprintf('%02d',$payment->getCcExpMonth()),
            //         'exp_year'          => $payment->getCcExpYear(),
            //         'cvc'               => $payment->getCcCid(),
            //         'name'              => $billing->getName(),
            //         'address_line1'     => $billing->getStreetLine(1),
            //         'address_line2'     => $billing->getStreetLine(2),
            //         'address_city'      => $billing->getCity(),
            //         'address_zip'       => $billing->getPostcode(),
            //         'address_state'     => $billing->getRegion(),
            //         'address_country'   => $billing->getCountryId(),
            //         // To get full localized country name, use this instead:
            //         // 'address_country'   => $this->_countryFactory->create()->loadByCode($billing->getCountryId())->getName(),
            //     ]
            // ];

            // $charge = \Stripe\Charge::create($requestData);
            // $payment
            //     ->setTransactionId($charge->id)
            //     ->setIsTransactionClosed(0);

        } catch (\Exception $e) {
            $this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment capturing error.'));
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
        }

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
        $transactionId = $payment->getParentTransactionId();

        try {
           // \Stripe\Charge::retrieve($transactionId)->refund(['amount' => $amount * 100]);
        } catch (\Exception $e) {
            $this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment refunding error.'));
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
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $isAvailable =  $this->getConfigData('active', $quote ? $quote->getStoreId() : null);
        $this->_logger->debug('manjutest'.$isAvailable);
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
}
