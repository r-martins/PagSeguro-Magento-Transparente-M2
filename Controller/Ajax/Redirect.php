<?php
namespace RicardoMartins\PagSeguro\Controller\Ajax;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Phrase;

/**
 * Class Redirect
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Controller\Ajax
 */
class Redirect extends \Magento\Framework\App\Action\Action
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

    /**
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Framework\App\Action\Context            $context
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param ResultFactory                                    $result
     * @param \Magento\Sales\Api\Data\OrderInterfaceFactory    $orderFactory
     * @param \Magento\Framework\Message\Manager               $messageManager
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Framework\Message\Manager $messageManager
 
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->result = $result;
        $this->serializer = $serializer;
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }


    public function execute()
    {
        $lastorderId = $this->checkoutSession->getLastRealOrderId();
        $order = $this->orderFactory->create()->loadByIncrementId($lastorderId);
        if (!$order->getPayment()) {
            $this->messageManager->addErrorMessage(
                new Phrase('Something went wrong when placing the order with PagSeguro. Please try again.')
            );
            $resultRedirect = $this->result->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }
        $url = $order->getPayment()->getAdditionalInformation('redirectUrl');

        $resultRedirect = $this->result->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($url);

        return $resultRedirect;
    }
}