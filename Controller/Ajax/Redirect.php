<?php
namespace RicardoMartins\PagSeguro\Controller\Ajax;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Phrase;

/**
 * Class Redirect
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2021 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Controller\Ajax
 */
class Redirect extends Action implements HttpGetActionInterface
{
     /**
     * Checkout Session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var ResultFactory
     */
    protected $result;

    /** @var \Magento\Framework\Message\Manager  */
    protected $messageManager;

    /** @var \RicardoMartins\PagSeguro\Helper\Data */
    protected $pagSeguroHelper;
    /**
     * @var \RicardoMartins\PagSeguro\Helper\Cookie
     */
    private $cookieHelper;
    /**
     * @var \Magento\Framework\App\Action\Context
     */
    private $context;
    /**
     * @var \Magento\Sales\Api\Data\OrderInterfaceFactory
     */
    private $orderFactory;
    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    private $urlHelper;

    /**
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Framework\App\Action\Context            $context
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param ResultFactory                                    $result
     * @param \Magento\Sales\Api\Data\OrderInterfaceFactory    $orderFactory
     * @param \Magento\Framework\Message\Manager               $messageManager
     * @param \RicardoMartins\PagSeguro\Helper\Data            $pagSeguroHelper
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Framework\Message\Manager $messageManager,
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \RicardoMartins\PagSeguro\Helper\Cookie $cookieHelper,
        \Magento\Framework\UrlInterface $urlHelper
     ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->result = $result;
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->cookieHelper = $cookieHelper;
        $this->context = $context;
        $this->urlHelper = $urlHelper;
    }

    public function execute()
    {
        $result = $this->result->create(ResultFactory::TYPE_REDIRECT);
        $url = $this->cookieHelper->get('redirectURL');
        if (is_null($url)) {
            $lastorderId = $this->checkoutSession->getLastRealOrderId();
            $order = $this->orderFactory->create()->loadByIncrementId($lastorderId);
            if (!$order->getPayment()) {
                $this->messageManager->addErrorMessage(
                    new Phrase('Something went wrong when placing the order with PagSeguro. Please try again.')
                );
                $result = $this->result->create(ResultFactory::TYPE_REDIRECT);
                return $result->setUrl($this->_redirect->getRefererUrl());
            }

            $url = $order->getPayment()->getAdditionalInformation('redirectUrl');
        }

        if ($this->pagSeguroHelper->isRedirectToSuccessPageEnabled()) {
            $url = $this->pagSeguroHelper->getStoreConfigValue('payment/rm_pagseguro_pagar_no_pagseguro/redirectURL');

            if (!$url) {
                $this->pagSeguroHelper->writeLog('Confira a URL de sucesso configurada em "Pagar no PagSeguro". Ela '
                    . 'parece inválida.');
            }

            $url = $this->urlHelper->getUrl($url);
        }

        $result->setUrl($url);

        return $result;
    }
}
