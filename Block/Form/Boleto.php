<?php
namespace RicardoMartins\PagSeguro\Block\Form;

class Boleto extends \Magento\Payment\Block\Form
{
    /**
     * @var string
     */
    protected $_template = 'RicardoMartins_PagSeguro::form/boleto.phtml';

    /**
     * @return bool
     */
    public function isCpfWithPaymentData()
    {
        $customerCpfAttribute = $this->_scopeConfig->getValue(
            'payment/rm_pagseguro/customer_cpf_attribute',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );

        return empty($customerCpfAttribute);
    }
}
