<?php

namespace RicardoMartins\PagSeguro\Model\Cache\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeInterface;
use Magento\Framework\Exception\LocalizedException;
use RicardoMartins\PagSeguro\Model\ResourceModel\Cache\Type\Installments as CacheResourceModel;

/**
 * PagSeguro installments cache
 */
class Installments
{
    const ATTR_OPTIONS = "rm_interest_options";
    const ATTR_LAST_UPDATE = "rm_pagseguro_last_update";

    /**
     * @var CacheResourceModel
     */
    protected $resource;

    /**
     * @var CacheTypeInterface
     */
    protected $cacheType;

    /**
     * @param CacheResourceModel $resource
     * @param CacheTypeInterface $cacheType
     */
    public function __construct(
        CacheResourceModel $resource,
        CacheTypeInterface $cacheType
    ) {
        $this->resource = $resource;
        $this->cacheType = $cacheType;
    }

    /**
     * Flushes the PagSeguro installments cache on products
     */
    public function flush()
    {
        $this->resource->flushAttribute(self::ATTR_OPTIONS);
        $this->resource->flushAttribute(self::ATTR_LAST_UPDATE);
        $this->cacheType->invalidate("full_page");
        $this->cacheType->invalidate("block_html");
        $this->cacheType->invalidate("eav");
    }
}
