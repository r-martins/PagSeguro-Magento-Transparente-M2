<?php

namespace RicardoMartins\PagSeguro\Cron;

use Magento\Framework\Phrase;

class UpdateProductInstallmentValues
{
    protected $_productFactory;
    protected $_stockFilter;
    protected $pagSeguroHelper;
    protected $_scopeConfig;


    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_productFactory = $productFactory;
        $this->_stockFilter = $stockFilter;
        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->_scopeConfig = $scopeConfig;
    }
    public function execute()
    {
//        echo "Iniciando...\n";
        if(!$this->_scopeConfig->getValue('payment/rm_pagseguro_cc/show_installments_product_page',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE)) return;

            $collection = $this->_productFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('rm_pagseguro_last_update',0);
        $collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $collection->setPageSize(15)->setCurPage(1)->load();
        if(!count($collection)) return;
        $params['sessionId'] = $this->pagSeguroHelper->getSessionId();
        foreach($collection as $product){
//            echo "Produto: ".$product->getName()."\n";
            $price = $product->getFinalPrice();
//            echo "Preco: ".$product->getFinalPrice()."\n";
//            echo "rm_pagseguro_last_update: '".$product->getRmPagseguroLastUpdate()."'\n";
            $params['amount'] = number_format($price,2,".","");

            if($this->pagSeguroHelper->isSandbox()) {
                $url = "https://ws.sandbox.pagseguro.uol.com.br/v2/installments.json";
            }
            else{
                $url = "https://ws.pagseguro.uol.com.br/checkout/v2/installments";
            }
            $url .= "?sessionId=".$params['sessionId']."&creditCardBrand=visa&amount=".$params['amount'];
//            echo $url."\n";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 45);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->pagSeguroHelper->getHeaders());
            try {
                $result = curl_exec($ch);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Validator\Exception(
                    new Phrase('Communication failure with Pagseguro (' . $e->getMessage() . ')')
                );
            }
//            echo $result;
//            echo "Status: ".curl_getinfo($ch,CURLINFO_HTTP_CODE)."\n";
//            echo "Content: ".$result."\n";
//            echo "==============================\n";
            $product->setRmPagseguroNoInterestInstallments(0);
            $product->setRmPagseguroNoInterestInstallments($this->getMaximumNoInterestInstallments($result));
            $product->setRmInterestOptions($result);
            $product->setRmPagseguroLastUpdate(time());
//            echo "getRmPagseguroNoInterestInstallments: " . $product->getRmPagseguroNoInterestInstallments()."\n\n";
//            echo "getRmInterestOptions: " . $product->getRmInterestOptions()."\n\n";
//            echo "getRmPagseguroLastUpdate: " . $product->getRmPagseguroLastUpdate()."\n\n";
            $product->save();
        }
    }
    private function getMaximumNoInterestInstallments($json){
        $json_array = json_decode($json,true);
        $maximum = 0;
        if($json_array['error']) return 1;
        foreach($json_array['installments']['visa'] as $installment_option){
            if($installment_option['interestFree']==true
                &&$installment_option['quantity']>$maximum) $maximum = $installment_option['quantity'];
        }
        return $maximum;
    }
}
