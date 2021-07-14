<?php
/*
 * Installments on product page
 *
 * This block is reponsible for showing maximum installments details on products details page
 *
 * @author Gustavo Ulyssea <gustavo.ulyssea@gmail.com>
 */

namespace RicardoMartins\PagSeguro\Block\Catalog\Product;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Helper\Data;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Installments - Block class displayed on the PDP
 *
 * @author    Gustavo Martins
 * @copyright 2020 Magenteiro/Ricardo Martins
 */
class Installments extends Template
{
    protected $helper;
    protected $_scopeConfig;

    public function __construct(
        Template\Context $context,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @return string|void
     */
    public function getInstallmentsText()
    {
        if (!$this->_scopeConfig->getValue(
            'payment/rm_pagseguro_cc/show_installments_product_page', ScopeInterface::SCOPE_WEBSITE
        )) {
            return "";
        }
        $product = $this->getProduct();
        if (in_array($product->getTypeId(), ['configurable', 'bundle', 'grouped'])) {
            return "";
        }

        if (!$interest_options = $product->getRmInterestOptions()) {
            return "";
        }

        $showOnlyInterestFreeFlag = $this->_scopeConfig->getValue(
            'payment/rm_pagseguro_cc/show_installments_product_page_interest_free_only', ScopeInterface::SCOPE_WEBSITE
        );

        $interest_array = json_decode($interest_options, true);
        
        if(!$interest_array || !isset($interest_array['installments']['visa']))
        {
            return "";
        }
        
        $maximum = 0;
        $value = 0;
        $isInterestFree = false;

        foreach ($interest_array['installments']['visa'] as $installment_option) {
            
            if($showOnlyInterestFreeFlag && !$installment_option['interestFree']) {
                continue;
            }
            
            if ($installment_option['quantity'] <= $maximum) {
                continue;
            }

            $maximum = $installment_option['quantity'];
            $value = $installment_option['installmentAmount'];
            $isInterestFree = $installment_option['interestFree'];
        }

        if (!$maximum || $maximum == 1) {
            return "";
        }

        $text = "<div class='ps_installments_external'><div id='ps_installments_max'>Em até " . $maximum . "x de "
            . "R$" . number_format($value, 2, ",", ".") . ($isInterestFree ? " sem juros" : "") . " no Cartão com PagSeguro</div>";

        $text .= $this->getInstallmentsList($interest_options);

        $text .= "</div>";
        return $text;
    }

    /**
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        return $this->helper->getProduct();
    }

    /**
     * @param $interest_options
     *
     * @return string
     */
    public function getInstallmentsList($interest_options)
    {
        $interest_array = json_decode($interest_options, true);
        $text = "<div id='installments_list' style='display: none;'>";
        foreach ($interest_array['installments']['visa'] as $installment_option) {
            if ($installment_option['interestFree']) {
                $text .= "<div id='installments_option'>" . $installment_option['quantity']. "x de "
                    . "R$".number_format($installment_option['installmentAmount'], 2, ",", ".") . " sem juros no Cartão</div>";
                continue;
            }

            $text .= "<div id='installments_option'>" . $installment_option['quantity'] . "x de "
                    . "R$".number_format($installment_option['installmentAmount'], 2, ",", "") . " no Cartão</div>";
        }
        $text .= "</div>";
        return $text;
    }
}
