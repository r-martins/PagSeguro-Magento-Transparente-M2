<?php

namespace RicardoMartins\PagSeguro\Model\Method;

/**
 * Boleto Payment Method for PagSeguro Payment
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2020 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 */
class Boleto extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * @var string
     */
    const CODE = 'rm_pagseguro_boleto';

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_countryFactory;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['BRL'];
    protected $_infoBlockType = \RicardoMartins\PagSeguro\Block\Payment\Info::class;
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

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \RicardoMartins\PagSeguro\Model\Notifications $pagSeguroAbModel,
        \Magento\Backend\Model\Auth\Session $adminSession,
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
            null,
            null,
            $data
        );

        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->pagSeguroAbModel = $pagSeguroAbModel;
        $this->adminSession = $adminSession;
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
        $info->setAdditionalInformation('sender_hash', $this->pagSeguroHelper->getPaymentHash('sender_hash'));

        //$this->pagSeguroHelper->writeLog('getData Order'. print_r($data->getData()));
        // set cpf
        if ($this->pagSeguroHelper->isCpfVisible()) {

            $this->pagSeguroHelper->writeLog('boletocpf_cpf' . $data['additional_data']['boleto_cpf']);

            $info->setAdditionalInformation($this->getCode() . '_cpf', $data['additional_data']['boleto_cpf']);
        }
        
        //Sandbox Mode
        if ($this->pagSeguroHelper->isSandbox()) {
            $info->setAdditionalInformation('is_sandbox', '1');
        }

        return $this;
    }

    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        //@TODO Review. Necessary?
        /* @var \Magento\Sales\Model\Order $order */
        $this->pagSeguroHelper->writeLog('Inside Order');

        /* @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        try {
            //will grab data to be send via POST to API inside $params
            $params = $this->pagSeguroHelper->getBoletoApiCallParams($order, $payment);
            $this->pagSeguroHelper->writeLog($params);

            //call API
            $returnXml = $this->pagSeguroHelper->callApi($params, $payment);

            if (isset($returnXml->errors)) {
                $errMsg = [];
                foreach ($returnXml->errors as $error) {
                    $message = $this->pagSeguroHelper->translateError((string) $error->message);
                    $errMsg[] = $message . '(' . $error->code . ')';
                }
                throw new \Magento\Framework\Validator\Exception(
                    'Um ou mais erros ocorreram no seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg)
                );
            }
            if (isset($returnXml->error)) {
                $error = $returnXml->error;
                $message = $this->pagSeguroHelper->translateError((string) $error->message);
                $errMsg[] = $message . ' (' . $error->code . ')';
                throw new \Magento\Framework\Validator\Exception(
                    'Um erro ocorreu em seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg)
                );
            }
            /* process return result code status */
            if ((int) $returnXml->status == 6 || (int) $returnXml->status == 7) {
                throw new \Magento\Framework\Validator\Exception('An error occurred in your payment.');
            }

            $payment->setSkipOrderProcessing(true);

            if (isset($returnXml->code)) {

                $additional = ['transaction_id' => (string) $returnXml->code];
                if ($existing = $payment->getAdditionalInformation()) {
                    if (is_array($existing)) {
                        $additional = array_merge($additional, $existing);
                    }
                }
                $payment->setAdditionalInformation($additional);

                if (isset($returnXml->paymentMethod->type) && (int) $returnXml->paymentMethod->type == 2) {
                    $payment->setAdditionalInformation('boletoUrl', (string) $returnXml->paymentLink);
                }
            }

            $this->pagSeguroAbModel->proccessNotificatonResult($returnXml, $payment);
        } catch (\Exception $e) {

            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
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
        $isAvailable = $this->getConfigData('active', $quote ? $quote->getStoreId() : null);
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
}
