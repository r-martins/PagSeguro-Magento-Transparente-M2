<?php

namespace RicardoMartins\PagSeguro\Block\Payment;

class InfoCc extends \Magento\Payment\Block\Info
{
	protected $_checkoutSession;
    protected $_orderFactory;
    protected $_scopeConfig;

    protected $_template = 'RicardoMartins_PagSeguro::info/cc.phtml';
    /**
     * @var \Magento\Framework\Url
     */
    private $urlHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Url $urlHelper,
        array $data = []
    ) {
		parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->urlHelper = $urlHelper;
    }


    // Use this method to get ID
    public function getRealOrderId()
    {
        $lastorderId = $this->_checkoutSession->getLastOrderId();
        return $lastorderId;
    }

    public function getOrder()
    {
        if ($this->_checkoutSession->getLastRealOrderId()) {
            return $this->_checkoutSession->getLastRealOrder();
        }
        if ($order = $this->getInfo()->getOrder()) {
            return $order;
        }
        return false;
    }

	public function getPaymentMethod()
    {
		$payment = $this->_checkoutSession->getLastRealOrder()->getPayment();
		return $payment->getMethod();
	}

    public function getPaymentInfo()
    {
        $order = $this->getOrder();
        if ($payment = $order->getPayment()) {
            return $payment->getAdditionalInformation();
        }

        return false;
    }
}
