<?php
namespace RicardoMartins\PagSeguro\Model\Method;

use RicardoMartins\PagSeguro\Model\Exception\WrongInstallmentsException;

/**
 * Credit Card Payment Method for PagSeguro Payment
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2020 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 */
class Cc extends \Magento\Payment\Model\Method\Cc
{
    /**
     * @var string
     */
    protected $_formBlockType = \RicardoMartins\PagSeguro\Block\Form\Cc::class;
    protected $_infoBlockType = \RicardoMartins\PagSeguro\Block\Payment\InfoCc::class;

    const CODE = 'rm_pagseguro_cc';

    protected $_code = self::CODE;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_countryFactory;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['BRL'];

    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
    /**
     * PagSeguro Helper
     *
     * @var RicardoMartins\PagSeguro\Helper\Data;
     */
    protected $pagSeguroHelper;

    /**
     * PagSeguro Abstract Model
     *
     * @var RicardoMartins\PagSeguro\Model\Notifications
     */
    protected $pagSeguroAbModel;

    /**
     * Backend Auth Session
     *
     * @var Magento\Backend\Model\Auth\Session $adminSession
     */
    protected $adminSession;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    protected $request;

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
        \RicardoMartins\PagSeguro\Model\Notifications $pagSeguroAbModel,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
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

        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->pagSeguroAbModel = $pagSeguroAbModel;
        $this->adminSession = $adminSession;
        $this->messageManager = $messageManager;
        $this->request = $request;
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        //@TODO Review. Necessary?
        $this->pagSeguroHelper->writeLog('Inside Auth');
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
        /*@var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        try {
            $returnXml = $this->_createTransaction($payment);

            if (isset($returnXml->errors)) {
                $errMsg = [];
                foreach ($returnXml->errors as $error) {
                    $message = $this->pagSeguroHelper->translateError((string)$error->message);
                    $errMsg[] = $message . '(' . $error->code . ')';
                }
                throw new \Magento\Framework\Validator\Exception(
                    'Um ou mais erros ocorreram no seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg)
                );
            }
            if (isset($returnXml->error)) {
                $error = $returnXml->error;
                $message = $this->pagSeguroHelper->translateError((string)$error->message);
                $errMsg[] = $message . ' (' . $error->code . ')';
                throw new \Magento\Framework\Validator\Exception(__(
                    'Um erro ocorreu em seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg)
                ));
            }
            /* process return result code status*/
            if ((int)$returnXml->status == 6 || (int)$returnXml->status == 7) {
                throw new \Magento\Framework\Validator\Exception('An error occurred in your payment.');
            }

            $payment->setSkipOrderProcessing(true);

            if (isset($returnXml->code)) {

                $additional = ['transaction_id' => (string)$returnXml->code];
                //Sandbox Mode
                if ($this->pagSeguroHelper->isSandbox()) {
                    $additional['is_sandbox'] = '1';
                }

                if ($existing = $payment->getAdditionalInformation()) {
                    if (is_array($existing)) {
                        $additional = array_merge($additional, $existing);
                    }
                }
                $payment->setAdditionalInformation($additional);
                $invoices = $order->getInvoiceCollection();
                foreach ($invoices as $invoice) {
                    $invoice->setTransactionId((string)$returnXml->code);
                    $invoice->save();
                }
            }

            $this->pagSeguroAbModel->proccessNotificatonResult($returnXml, $payment);
        } catch (\Exception $e) {

            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
        return $this;
    }

    /**
     * Sends the request to create the transaction in PagSeguro
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \SimpleXMLElement
     */
    protected function _createTransaction($payment)
    {
        $order = $payment->getOrder();
        $params = $this->pagSeguroHelper->getCreditCardApiCallParams($order, $payment);

        try {
            $returnXml = $this->pagSeguroHelper->callApi($params, $payment);
        } catch (WrongInstallmentsException $e) {
            $returnXml = $this->pagSeguroHelper->recalcInstallmentsAndResendOrder($params, $payment);
        }

        return $returnXml;
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
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }
        // recupera a informação adicional do PagSeguro
        $info           = $this->getInfoInstance();
        $transactionId = $payment->getAdditionalInformation('transaction_id');

        $params = [
            'transactionCode'   => $transactionId,
            'refundValue'       => number_format($amount, 2, '.', '')
        ];

        $params['token'] = $this->pagSeguroHelper->getToken();
        $params['email'] = $this->pagSeguroHelper->getMerchantEmail();

        try {
            // call API - refund
            $returnXml  = $this->pagSeguroHelper->callApi($params, $payment, 'transactions/refunds');

            if ($returnXml === null) {
                $errorMsg = 'Impossível gerar reembolso. Aldo deu errado.';
                throw new \Magento\Framework\Validator\Exception($errorMsg);
            }
        } catch (\Exception $e) {
            $this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
            $this->pagSeguroHelper->writeLog(__('Payment refunding error.'));
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
        $info->setAdditionalInformation('sender_hash', $data['additional_data']['sender_hash'] ?? null)
            ->setAdditionalInformation(
                'credit_card_token',
                $data['additional_data']['credit_card_token'] ?? null
            )
            ->setAdditionalInformation('credit_card_owner', $data['additional_data']['cc_owner_name'] ?? null)
            ->setCcType($data['additional_data']['cc_type'] ?? null)
            ->setCcLast4(substr($data['additional_data']['cc_number'] ?? null, -4))
            ->setCcExpYear($data['additional_data']['cc_exp_year'] ?? null)
            ->setCcExpMonth($data['additional_data']['cc_exp_month'] ?? null);

        // set cpf
        if ($this->pagSeguroHelper->isCpfVisible()) {
            $ccOwnerCpf = $data['additional_data']['cc_owner_cpf'] ?? null;
            $info->setAdditionalInformation($this->getCode() . '_cpf', $ccOwnerCpf);
        }

        //DOB value
        if ($this->pagSeguroHelper->isDobVisible()) {
            $dobDay = isset($data['additional_data']['cc_owner_birthday_day']) ? trim(
                $data['additional_data']['cc_owner_birthday_day']
            ) : '01';
            $dobMonth = isset($data['additional_data']['cc_owner_birthday_month']) ? trim(
                $data['additional_data']['cc_owner_birthday_month']
            ) : '01';
            $dobYear = isset($data['additional_data']['cc_owner_birthday_year']) ? trim(
                $data['additional_data']['cc_owner_birthday_year']
            ) : '1970';
            $info->setAdditionalInformation(
                'credit_card_owner_birthdate',
                date(
                    'd/m/Y',
                    strtotime(
                        $dobMonth . '/' . $dobDay . '/' . $dobYear
                    )
                )
            );
        }

        //Installments value
        if (isset($data['additional_data']['cc_installments'])) {
            $installments = explode('|', $data['additional_data']['cc_installments']);
            if (false !== $installments && count($installments)==2) {
                $info->setAdditionalInformation('installment_quantity', (int)$installments[0]);
                $info->setAdditionalInformation('installment_value', $installments[1]);
            }
        }

        //Sandbox Mode
        if ($this->pagSeguroHelper->isSandbox()) {
            $info->setAdditionalInformation('is_sandbox', '1');
        }

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
        if ($this->adminSession->getUser()) {
            return false;
        }
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
        if (stristr($this->request->getUriString(), "/set-payment-information") ||
            stristr($this->request->getUriString(), "/selected-payment-method")
        ) {
            return $this;
        }

        $info = $this->getInfoInstance();

        $senderHash = $info->getAdditionalInformation('sender_hash');
        $creditCardToken = $info->getAdditionalInformation('credit_card_token');

        if (!$creditCardToken || !$senderHash) {
            $missingInfo = sprintf('Token do cartão: %s', var_export($creditCardToken, true));
            $missingInfo .= sprintf('/ Sender_hash: %s', var_export($senderHash, true));
            $this->pagSeguroHelper->writeLog(
                "Falha ao obter o token do cartao ou sender_hash.
                Ative o modo debug e observe o console de erros do seu navegador.
                Se esta for uma atualização via Ajax, ignore esta mensagem até a finalização do pedido.
                $missingInfo"
            );

            $errorMsg = __('Falha ao processar seu pagamento. Por favor, entre em contato com nossa equipe.');
            throw new \Magento\Framework\Exception\LocalizedException($errorMsg);
        }
        return $this;
    }
}
