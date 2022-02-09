<?php

namespace RicardoMartins\PagSeguro\Block\Payment;

class Info extends \Magento\Payment\Block\Info
{
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_scopeConfig;

    protected $_template = 'RicardoMartins_PagSeguro::info/info.phtml';

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
            $paymentMethod = $payment->getMethod();

            switch ($paymentMethod) {
                case 'rm_pagseguro_boleto':
                    return [
                        'tipo'  => 'Boleto',
                        'url'   => $payment->getAdditionalInformation('boletoUrl'),
                        'texto' => 'Clique aqui para imprimir seu boleto',
                        'is_sandbox' => $payment->getAdditionalInformation('is_sandbox'),
                        'transaction_id' => $payment->getAdditionalInformation('transaction_id'),
                    ];

                case 'rm_pagseguro_tef':
                    return [
                        'tipo'  => 'Débito Online (TEF)',
                        'url'   => $payment->getAdditionalInformation('tefUrl'),
                        'texto' => 'Clique aqui para realizar o pagamento',
                        'is_sandbox' => $payment->getAdditionalInformation('is_sandbox'),
                        'transaction_id' => $payment->getAdditionalInformation('transaction_id'),
                    ];

                case 'rm_pagseguro_pagar_no_pagseguro':
                    return [
                        'tipo'  => 'Redirect',
                        'url'   => $payment->getAdditionalInformation('redirectUrl'),
                        'texto' => 'Clique aqui para pagar no PagSeguro',
                        'is_sandbox' => $payment->getAdditionalInformation('is_sandbox'),
                        'transaction_id' => $payment->getAdditionalInformation('transaction_id'),
                    ];
            }
        }

        return false;
    }

    /**
     * Retrieves the link of the transaction on PagSeguro Panel
     * @return string
     */
    public function getTransactionLink()
    {
        $transactionId = '';
        $info = $this->getPaymentInfo();
        $transactionId = $info['transaction_id'] ?? '';
        
        if (!$transactionId) {
            return '';
        }

        if (isset($info['is_sandbox']) && $info['is_sandbox']) {
            return 'https://sandbox.pagseguro.uol.com.br/aplicacao/transacoes.html';
        }

        return 'https://pagseguro.uol.com.br/transaction/details.jhtml?code=' . $transactionId;
    }
}
