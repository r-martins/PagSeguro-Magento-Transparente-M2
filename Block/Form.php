<?php
namespace RicardoMartins\PagSeguro\Block;

class Form extends \Magento\Payment\Block\Form
{
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
