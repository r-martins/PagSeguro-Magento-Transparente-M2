<?php
/*
 * Installments on product page
 *
 * This cache type was created to make it easy to update installments data on product details page
 *
 * @author Gustavo Ulyssea <gustavo.ulyssea@gmail.com>
 */

namespace RicardoMartins\PagSeguro\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * System / Cache Management / Cache type "Your Cache Type Label"
 */
class Interests extends TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    const TYPE_IDENTIFIER = 'rm_pagseguro_interests_cache';

    /**
     * The tag name that limits the cache cleaning scope within a particular tag
     */
    const CACHE_TAG = 'RM_PAGSEGURO_INTERESTS_CACHE';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = [])
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        /** Apply filters here */
        $collection = $productCollection->addAttributeToSelect('*')
            ->load();
        $productIds = [];
        foreach($collection as $product){
            $productIds[] = $product->getId();
        }
        $productActionObject = $objectManager->create('Magento\Catalog\Model\Product\Action');
        $productActionObject->updateAttributes($productIds, array('rm_interest_options' => ""), 0);
        $productActionObject->updateAttributes($productIds,array('rm_pagseguro_last_update' => 0),0);
    }
    public function flush($mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = [])
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        /** Apply filters here */
        $collection = $productCollection->addAttributeToSelect('*')
            ->load();
        $productIds = [];
        foreach($collection as $product){
            $productIds[] = $product->getId();
        }
        $productActionObject = $objectManager->create('Magento\Catalog\Model\Product\Action');
        $productActionObject->updateAttributes($productIds, array('rm_interest_options' => ""), 0);
        $productActionObject->updateAttributes($productIds,array('rm_pagseguro_last_update' => 0),0);
    }

}
