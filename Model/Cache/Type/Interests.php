<?php
/*
 * Installments on product page
 *
 * This cache type was created to make it easy to update installments data on product details page
 *
 * @author Gustavo Ulyssea <gustavo.ulyssea@gmail.com>
 */

namespace RicardoMartins\PagSeguro\Model\Cache\Type;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\Exception\LocalizedException;

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
     * @var Config
     */
    private $eavConfig;

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool, Config $eavConfig)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
        $this->eavConfig = $eavConfig;
    }

    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = [])
    {
        // checks if interest attributes exist
        if (!$this->productAttributeExists('rm_interest_options') ||
            !$this->productAttributeExists('rm_pagseguro_last_update'))
        {
            return;
        }

        $this->_resetInterestProductAttributes();
    }

    //Nunca utilizado?
    /*public function flush($mode = \Zend_Cache::CLEANING_MODE_ALL, array $tags = [])
    {
        $this->clean();
    }*/

    /**
     * @param $attributeId
     *
     * @return bool
     */
    public function productAttributeExists($attributeId) {
        try {
            $attr = $this->eavConfig->getAttribute(Product::ENTITY, $attributeId);
        } catch (LocalizedException $e) {
            return false;
        }
        return ($attr && $attr->getId());
    }

    /**
     * Reset interest product
     * attributes values
     */
    private function _resetInterestProductAttributes()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');

        // only update attributes if
        // there are products on catalog
        if($productCollection->getSize() > 0)
        {
            $productIds = [];

            foreach($productCollection as $product)
            {
                $productIds[] = $product->getId();
            }

            $productActionObject = $objectManager->create('Magento\Catalog\Model\Product\Action');
            $productActionObject->updateAttributes($productIds, array('rm_interest_options' => ""), 0);
            $productActionObject->updateAttributes($productIds,array('rm_pagseguro_last_update' => 0), 0);
        }
    }
}
