<?php

namespace RicardoMartins\PagSeguro\Model\ResourceModel\Cache\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\AbstractResource;

/**
 * PagSeguro installments database resource
 */
class Installments extends AbstractResource
{
    /**
     * Initializes the connection
     *
     * @return void
     */
    protected function _construct()
    {
        $resource = $this->_resource;
        $this->setType(
            Product::ENTITY
        )->setConnection(
            $resource->getConnection('catalog')
        );
    }

    /**
     * Removes all entries of a given attribute on EAV tables
     * @param String $attrCode
     */
    public function flushAttribute($attrCode)
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $attribute = $this->getAttribute($attrCode);
            if (!$attribute->getAttributeId()) {
                return;
            }
            
            $table = $attribute->getBackend()->getTable();

            $connection->delete(
                $table,
                ['attribute_id = ?' => $attribute->getAttributeId()]
            );

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
