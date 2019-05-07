<?php

namespace RicardoMartins\PagSeguro\Block\Payment;

class Info extends \Magento\Framework\View\Element\Template
{
	protected $_checkoutSession;
    protected $_orderFactory;
    protected $_scopeConfig;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Context $context
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $context->getScopeConfig();
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
             $order = $this->_orderFactory->create()->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());
        return $order;
        }
        return false;
    }

    public function getShippingInfo()
    {
        $order = $this->getOrder();
        if($order) {
            $address = $order->getShippingAddress();    

            return $address;
        }
        return false;

    }
}
