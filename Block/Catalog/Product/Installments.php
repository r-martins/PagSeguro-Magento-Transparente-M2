<?php

namespace RicardoMartins\PagSeguro\Block\Catalog\Product;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Helper\Data;

class Installments extends Template
{
    protected $helper;

    public function __construct(
        Template\Context $context,
        Data $helper,
        array $data = [])
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }
    public function getInstallmentsText()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
        if(!$_scopeConfig->getValue('payment/rm_pagseguro_cc/show_installments_product_page',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE)) return;
        $product = $this->getProduct();
        if($product->getTypeId()=="configurable") return "";
        if($product->getTypeId()=="bundle") return "";
        if($product->getTypeId()=="grouped") return "";
        if(!$interest_options = $product->getRmInterestOptions()) return "";
        $interest_array = json_decode($interest_options,true);
        $maximum = 0;
        $value = 0;
        foreach($interest_array['installments']['visa'] as $installment_option){
            if($installment_option['interestFree']==true
                &&$installment_option['quantity']>$maximum) {
                $maximum = $installment_option['quantity'];
                $value = $installment_option['installmentAmount'];
            }
        }
        if(!$maximum) return "";
        if($maximum==1){
            foreach($interest_array['installments']['visa'] as $installment_option){
                $maximum = $installment_option['quantity'];
                $value = $installment_option['installmentAmount'];
            }
            if($maximum==1) return "";
            $text = "Em até ".$maximum."x de R$".number_format($value,2,",","")
                . " com PagSeguro";
        }
        else{
            $text = "Em até ".$maximum."x de ".number_format($value,2,",","")
                . " sem juros";
        }
        return $text;
    }
    public function getProduct(){
        return $this->helper->getProduct();
    }
}
