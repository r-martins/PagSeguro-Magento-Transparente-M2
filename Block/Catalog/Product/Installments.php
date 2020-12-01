<?php
/*
 * Installments on product page
 *
 * This block is reponsible for showing maximum installments details on products details page
 *
 * @author Gustavo Ulyssea <gustavo.ulyssea@gmail.com>
 */

namespace RicardoMartins\PagSeguro\Block\Catalog\Product;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Helper\Data;

class Installments extends Template
{
    protected $helper;
    protected $_scopeConfig;

    public function __construct(
        Template\Context $context,
        Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = [])
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
        $this->_scopeConfig = $scopeConfig;
    }
    public function getInstallmentsText()
    {
        if(!$this->_scopeConfig->getValue('payment/rm_pagseguro_cc/show_installments_product_page',
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
            $text = "<div class='ps_installments_external'><div id='ps_installments_max'>Em até ".$maximum."x de "
                . "R$".number_format($value,2,",","") . " com PagSeguro</div>";
            $text .= $this->getInstallmentsList($interest_options);
        }
        else{
            $text = "<div class='ps_installments_external'><div id='ps_installments_max'>Em até ".$maximum."x de "
                . "R$".number_format($value,2,",","") . " sem juros no Cartão</div>";
        }
        $text .= "</div>";
        return $text;
    }
    public function getProduct(){
        return $this->helper->getProduct();
    }
    public function getInstallmentsList($interest_options)
    {
        $interest_array = json_decode($interest_options,true);
        $text = "<div id='installments_list' style='display: none;'>";
        foreach($interest_array['installments']['visa'] as $installment_option){
            if($installment_option['interestFree']==true) {
                $text .= "<div id='installments_option'>".$installment_option['quantity']."x de "
                    . "R$".number_format($installment_option['installmentAmount'],2,",","") . " sem juros no Cartão</div>";
            }
            else{
                $text .= "<div id='installments_option'>".$installment_option['quantity']."x de "
                    . "R$".number_format($installment_option['installmentAmount'],2,",","") . " no Cartão</div>";
            }
        }
        $text .= "</div>";
        return $text;
    }
}
