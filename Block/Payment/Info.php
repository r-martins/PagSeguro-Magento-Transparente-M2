<?php

namespace RicardoMartins\PagSeguro\Block\Payment;

class Info extends \Magento\Framework\View\Element\Template
{
	protected $_checkoutSession;
    protected $_orderFactory;
    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
		parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;     
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
	
	public function getPaymentMethod()
    {
		$payment = $this->_checkoutSession->getLastRealOrder()->getPayment();        
		return $payment->getMethod();
	}
	
    public function getPaymentInfo()
    {
        $order = $this->getOrder();
        if ($order) {
			$payment = $this->_checkoutSession->getLastRealOrder()->getPayment();        
			$paymentMethod = $payment->getMethod();
			
			switch($paymentMethod)
			{
				case 'rm_pagseguro_boleto':
					return array(
						'tipo' => 'Boleto',
						'url' => $payment->getAdditionalInformation('boletoUrl'),
						'texto' => 'Clique aqui para imprimir seu boleto',
					);
					break;
				case 'pagseguropro_tef':
					return array(
						'tipo' => 'Débito Online (TEF)',
						'url' => $payment->getAdditionalInformation('tefUrl'),
						'texto' => 'Clique aqui para realizar o pagamento',
					);
				break;
			}
		}	
        return false;
    }
}
