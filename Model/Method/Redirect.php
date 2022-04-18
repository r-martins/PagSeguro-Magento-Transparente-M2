<?php
namespace RicardoMartins\PagSeguro\Model\Method;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;

/**
 * Class Redirect
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Model\Method
 */

class Redirect extends \RicardoMartins\PagSeguro\Model\Method\AbstractMethodExtension
{

    /**
     * Payment code
     *
     * @var string
     */
    const CODE = 'rm_pagseguro_pagar_no_pagseguro';
    protected $_code                    = self::CODE;
    protected $_isGateway               = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canAuthorize            = true;
    protected $_countryFactory;
    protected $_minAmount               = null;
    protected $_maxAmount               = null;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;
    protected $_supportedCurrencyCodes  = array('BRL');
    protected $_infoBlockType           = \RicardoMartins\PagSeguro\Block\Payment\Info::class;
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
    /**
     * @var \RicardoMartins\PagSeguro\Helper\Cookie
     */
    private $cookieHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \RicardoMartins\PagSeguro\Helper\Logger $pagSegurologger,
        \RicardoMartins\PagSeguro\Model\Notifications $pagSeguroAbModel,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \RicardoMartins\PagSeguro\Helper\Cookie $cookieHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null,
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig,
            $logger, $pagSeguroHelper, $pagSegurologger, $resource, $resourceCollection, $data, $directory);
        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->pagSeguroAbModel = $pagSeguroAbModel;
        $this->adminSession = $adminSession;
        $this->cookieHelper = $cookieHelper;
    }

    public function order(InfoInterface $payment, $amount)
    {
        /*@var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        try {

            //will grab data to be send via POST to API inside $params
            $params = $this->pagSeguroHelper->getRedirectParams($order, $payment);

            $this->pagSeguroHelper->writeLog($params);

            //call API
            $returnXml = $this->pagSeguroHelper->callApi($params, $payment , 'checkout');

            if (isset($returnXml->errors)) {
                $errMsg = array();
                foreach ($returnXml->errors as $error) {
                    $message = $this->pagSeguroHelper->translateError((string)$error->message);
                    $errMsg[] = $message . '(' . $error->code . ')';
                }
                throw new \Magento\Framework\Validator\Exception('Um ou mais erros ocorreram no seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg));
            }
            if (isset($returnXml->error)) {
                $error = $returnXml->error;
                $message = $this->pagSeguroHelper->translateError((string)$error->message);
                $errMsg[] = $message . ' (' . $error->code . ')';
                throw new \Magento\Framework\Validator\Exception('Um erro ocorreu em seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg));
            }

            $payment->setSkipOrderProcessing(true);

            if (isset($returnXml->code)) {
                $code = (string)$returnXml->code;
                if($this->pagSeguroHelper->isSandbox()) {
                    $redirectUrl = 'https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html?code=' . $code;
                } else {
                    $redirectUrl = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=' . $code;
                }

                $additionalData = ['redirectUrl' => $redirectUrl];
                $additionalData['transaction_id'] = $code;
                if($this->pagSeguroHelper->isSandbox()) {
                    $additionalData['is_sandbox'] = 1;
                }
                $payment->setAdditionalInformation($additionalData);
                $invoices = $order->getInvoiceCollection();
                foreach($invoices as $invoice){
                    $invoice->setTransactionId((string)$returnXml->code);
                    $invoice->save();
                }
                //$order->queueNewOrderEmail();
                if(!$this->pagSeguroHelper->isRedirectToSuccessPageEnabled()) {
                    $this->setRedirectUrl($redirectUrl);
                }
                $this->cookieHelper->set('redirectURL', $redirectUrl, 3600);
                $order->setStatus($this->getConfigData('order_status'));
                $order->setState(Order::STATE_NEW);
            }

        } catch (\Exception $e) {

            throw new LocalizedException(__($e->getMessage()));
        }
        return $this;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $isAvailable = $this->getConfigData('active', $quote ? $quote->getStoreId() : null);

        if (empty($quote)) {
            return $isAvailable;
        }

        if ($this->getConfigData("disable_frontend") == true && !$this->adminSession->getUser()) {
            return false;
        }

        return $isAvailable;
    }

    /**
     * Assign data to info model instance
     *
     * @param mixed $data
     *
     * @return  object
     * @throws \Magento\Framework\Validator\Exception
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $info = $this->getInfoInstance();

        //Sandbox Mode
        if ($this->pagSeguroHelper->isSandbox()) {
            $info->setAdditionalInformation('is_sandbox', '1');
        }

        return $this;
    }

}
