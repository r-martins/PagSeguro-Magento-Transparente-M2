<?php

namespace RicardoMartins\PagSeguro\Model\Cache\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;

/**
 * PagSeguro installments cache
 */
class Installments
{
    const ATTR_OPTIONS = "rm_interest_options";
    const ATTR_LAST_UPDATE = "rm_pagseguro_last_update";

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ProductAction
     */
    protected $productAction;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductAction $productAction
     * @param EavConfig $eavConfig
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ProductAction $productAction,
        EavConfig $eavConfig
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productAction = $productAction;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Flushes the PagSeguro installments cache on products
     */
    public function flush()
    {
        if( !$this->_productAttributeExists(self::ATTR_OPTIONS) || 
            !$this->_productAttributeExists(self::ATTR_LAST_UPDATE)) {
            return;
        }

        $productCollection = $this->productCollectionFactory->create();
        
        if($productCollection->getSize() <= 0)
        {
            return;
        }

        $productIds = [];

        foreach($productCollection as $product) {
            $productIds[] = $product->getId();
        }

        $this->productAction->updateAttributes($productIds, array(self::ATTR_OPTIONS => ""), 0);
        $this->productAction->updateAttributes($productIds, array(self::ATTR_LAST_UPDATE => 0), 0);
    }

    /**
     * Checks if the product attribute exists on Magento
     * @param String $attributeCode
     * @return bool
     */
    protected function _productAttributeExists($attributeCode)
    {
        try {
            $attr = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
        } catch (LocalizedException $e) {
            return false;
        }
        return ($attr && $attr->getId());
    }
}
   