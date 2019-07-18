<?php

namespace RicardoMartins\PagSeguro\Block\Payment;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\State;
use \Magento\Store\Model\StoreManagerInterface;

class Info extends \Magento\Payment\Block\Info
{
	protected $_template = 'RicardoMartins_PagSeguro::info/default.phtml';
	
	protected $_checkoutSession;
    protected $_orderFactory;
    protected $_scopeConfig;
	
	/**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;
    /**
     * Klarna Order Repository
     *
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var Resolver
     */
    private $locale;

    /**
     * @var MerchantPortal
     */
    private $merchantPortal;

    /**
     * @var State
     */
    private $appState;
	
	public function __construct(
        Context $context,
        Resolver $locale,
        DataObjectFactory $dataObjectFactory,
        State $appState,
        StoreManagerInterface $storeManager, 
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->locale = $locale;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->appState = $appState;
        $this->_storeManager = $storeManager;
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
				case 'rm_pagseguro_tef':
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
    
    public function getSpecificInformation()
    {
        $data = parent::getSpecificInformation();
        $transport = $this->dataObjectFactory->create(['data' => $data]);
        $info = $this->getInfo();
        $order = $info->getOrder();
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        $paymentMethod = $order->getPayment()->getMethod();        
        try {
			switch($paymentMethod)
			{
				case 'rm_pagseguro_boleto':
					if(isset($additionalInformation['boletoUrl']) && $additionalInformation['boletoUrl']) {
						 $boletoUrl =  "";
						 $boletoLink =  "";
						 $boletoUrl = $additionalInformation['boletoUrl'];     
						 $boletoLink = "<a target=_blank href=".$boletoUrl.">Realizar Pagamento</a>";              
						 $transport->setData('', $boletoLink);
					}
					break;
				case 'rm_pagseguro_tef':
					if(isset($additionalInformation['tefUrl']) && $additionalInformation['tefUrl']) {
						 $tefUrl =  "";
						 $tefLink =  "";
						 $tefUrl = $additionalInformation['tefUrl'];     
						 $tefLink = "<a target=_blank href=".$tefUrl.">Realizar Pagamento</a>";              
						 $transport->setData('', $tefLink);
					}
				break;
			}
			
        } catch (NoSuchEntityException $e) {
            $transport->setData((string)__('Error'), $e->getMessage());
        }  
        return $transport->getData();
    }
}
