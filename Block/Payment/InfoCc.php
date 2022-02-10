<?php

namespace RicardoMartins\PagSeguro\Block\Payment;

class InfoCc extends \Magento\Payment\Block\Info
{
	protected $_checkoutSession;
    protected $_orderFactory;
    protected $_scopeConfig;

    /**
     * PagSeguro Helper
     *
     * @var RicardoMartins\PagSeguro\Helper\Data;
     */
    protected $pagSeguroHelper;

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
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        array $data = []
    ) {
		parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->urlHelper = $urlHelper;
        $this->pagSeguroHelper = $pagSeguroHelper;
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

    public function getStatus($transactionId) {
        $order = $this->getOrder();
        $isRefund = $isCancel = $isPaid = false;
        $isRefund = $this->pagSeguroHelper->getTransaction($transactionId. '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND, $order->getPayment());
        if (false !== $isRefund) return 'Devolvido';
        $isCancel = $this->pagSeguroHelper->getTransaction($transactionId. '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID, $order->getPayment());
        if (false !== $isCancel) return 'Cancelado';
        $isPaid = $this->pagSeguroHelper->getTransaction($transactionId, $order->getPayment());
        if (false !== $isPaid) return 'Pago';
        return 'Pendente';
    }

    /**
     * Retrieves the link of the transaction on PagSeguro Panel
     * @return string
     */
    public function getTransactionLink($cardIndex = '')
    {
        $transactionId = '';
        $info = $this->getPaymentInfo();

        switch ($cardIndex) {
            case 'first':
                $transactionId = $info['transaction_id_first'] ?? '';
                break;
            case 'second':
                $transactionId = $info['transaction_id_second'] ?? '';
                break;
            default:
                $transactionId = $info['transaction_id'] ?? '';
        }

        if (!$transactionId) {
            return '';
        }

        if (isset($info['is_sandbox']) && $info['is_sandbox']) {
            return 'https://sandbox.pagseguro.uol.com.br/aplicacao/transacoes.html';
        }

        return 'https://pagseguro.uol.com.br/transaction/details.jhtml?code=' . $transactionId;
    }
}
