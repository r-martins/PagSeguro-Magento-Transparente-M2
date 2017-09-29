<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RicardoMartins\PagSeguro\Helper;


/**
 * PagSeguro Data helper
 */
class Internal extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    /**
     * Eav config Model
     *
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfigModel;

    /**
     * Eav Resource Model Attribute Collection
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    protected $eavAttributeCollection;

    /**
    * @param \Magento\Eav\Model\Config $eavConfigModel
    * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $eavAttributeCollection
    */

	public function __construct(
        \Magento\Eav\Model\Config $eavConfigModel,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $eavAttributeCollection,
        \Magento\Framework\App\Helper\Context $context
 
    ) {
        $this->eavConfigModel = $eavConfigModel;
        $this->eavAttributeCollection = $eavAttributeCollection;
        parent::__construct($context);
    }


	/**
     * Get EAV attributes collection filter by type param
     *
     * @param string $type type of EAV attributes 
     * @return collection of data
     */
    public function getFields($type = 'customer_address')
    {
        $entityType = $this->eavConfigModel->getEntityType($type);
        $entityTypeId = $entityType->getEntityTypeId();
        $attributes = $this->eavAttributeCollection->setEntityTypeFilter($entityTypeId);

        return $attributes->getData();
        
    }

    /**
     * Get System config values
     *
     * @param string path of system config field 
     * @return string
     */
    public function getStoreConfig($valPath)
    {
        return $this->scopeConfig->getValue($valPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
}
