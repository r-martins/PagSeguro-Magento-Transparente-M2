<?php
/*
 * Update product installments
 *
 * Update per product installments options from PagSeguro API
 *
 * @author Gustavo Ulyssea <gustavo.ulyssea@gmail.com>
 */

namespace RicardoMartins\PagSeguro\Cron;

use Magento\Framework\Phrase;

/**
 * Class UpdateProductInstallmentValues
 *
 * @author    Gustavo Martins / Ricardo Martins
 * @copyright 2020 Magenteiro/Ricardo Martins
 */
class UpdateProductInstallmentValues
{
    protected $_productCollectionFactory;
    protected $_stockFilter;
    protected $pagSeguroHelper;
    protected $_scopeConfig;


    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_stockFilter = $stockFilter;
        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->_scopeConfig = $scopeConfig;
    }
    public function execute()
    {
        if (!$this->_scopeConfig->getValue(
            'payment/rm_pagseguro_cc/show_installments_product_page',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE)) {
            return;
        }

        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('rm_pagseguro_last_update',0);
        $collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $collection->setPageSize(30)->setCurPage(1)->load();
        if (!count($collection)) {
            return;
        }

        $params['sessionId'] = $this->pagSeguroHelper->getSessionId();
        foreach ($collection as $product) {
            $price = $product->getFinalPrice();
            $params['amount'] = number_format($price, 2, ".", "");

            $url = "https://ws.pagseguro.uol.com.br/checkout/v2/installments.json";

            if($this->pagSeguroHelper->isSandbox()) {
                $url = "https://ws.sandbox.pagseguro.uol.com.br/v2/installments.json";
            }
            $url .= "?sessionId=".$params['sessionId']."&creditCardBrand=visa&amount=".$params['amount'];
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

            $product->setRmPagseguroNoInterestInstallments(0);
            $product->setRmInterestOptions($result);
            $product->setRmPagseguroLastUpdate(time());
            $product->setUpgradingInstallments(true);
            $product->save();
        }
    }

    /**
     * @param $json
     *
     * @return int|mixed
     */
    private function getMaximumNoInterestInstallments($json){
        $json_array = json_decode($json,true);
        $maximum = 0;
        if ($json_array['error']) {
            return 1;
        }

        foreach ($json_array['installments']['visa'] as $installment_option) {
            if ($installment_option['interestFree'] && $installment_option['quantity']>$maximum) {
                $maximum = $installment_option['quantity'];
            }
        }

        return $maximum;
    }
}
