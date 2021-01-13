<?php
namespace RicardoMartins\PagSeguro\Controller\Ajax;
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
 */
class Redirect implements HttpGetActionInterface
{
     /**
     * Checkout Session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /** @var \Magento\Framework\Serialize\SerializerInterface  */
    protected $serializer;

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
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Framework\Message\Manager $messageManager,
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \RicardoMartins\PagSeguro\Helper\Cookie $cookieHelper
     ) {
        $this->checkoutSession = $checkoutSession;
        $this->result = $result;
        $this->serializer = $serializer;
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->cookieHelper = $cookieHelper;
        $this->context = $context;
    }

    public function execute()
    {
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

        $result = $this->result->create(ResultFactory::TYPE_REDIRECT);
        $result->setUrl($url);

        if ($this->pagSeguroHelper->isRedirectToSuccessPageEnabled()) {
            $result = $this->result->create(ResultFactory::TYPE_RAW);
            $result->setHeader('Content-Type', 'text/plain')->setContents('false');
        }

        return $result;
    }
}
