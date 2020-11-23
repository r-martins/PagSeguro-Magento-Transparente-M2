<?php
/*
 * Default upgrade data Magento file
 *
 * Version 2.8.0 adds product attributes to control installments exhibition on product page
 * Version 2.8.1 solves mistakes on product attributes created on 2.8.0
 *
 * @author Gustavo Ulyssea <gustavo.ulyssea@gmail.com>
 */

namespace RicardoMartins\PagSeguro\Setup;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    private $eavSetupFactory;
    private $productActionObject;

    public function __construct(EavSetupFactory $eavSetupFactory,
        \Magento\Catalog\Model\Product\Action $productActionObject
        )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->productActionObject = $productActionObject;
    }
    public function upgrade( ModuleDataSetupInterface $setup, ModuleContextInterface $context ) {
        $installer = $setup;
        $installer->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        // 2.8.0
        if(version_compare($context->getVersion(), '2.8.0', '<')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'rm_interest_options',
                [
                    'group' => 'General',
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'PagSeguro Installments',
                    'input' => '',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => false,
                    'required' => false,
                    'sort_order' => 50,
                    'user_defined' => false,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'rm_pagseguro_last_update',
                [
                    'group' => 'General',
                    'type' => 'datetime',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'PagSeguro Installments lastupdate',
                    'input' => '',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => false,
                    'required' => false,
                    'sort_order' => 50,
                    'user_defined' => false,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );
        }
        // 2.8.1
        if(version_compare($context->getVersion(), '2.8.1', '<')) {
            $eavSetup->updateAttribute(
                $eavSetup->getEntityTypeId('catalog_product'),
                'rm_pagseguro_last_update','backend_type','int');


            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
            $collection = $productCollection->addAttributeToSelect('*')
                ->load();
            $idArray = [];
            foreach ($collection as $product){
                $idArray[] =$product->getId();
            }
            $value = 0;
            $this->productActionObject->updateAttributes($idArray, array('rm_pagseguro_last_update' => $value), 0);
        }
        $eavSetup->updateAttribute(
            $eavSetup->getEntityTypeId('catalog_product'),
            'rm_interest_options','backend_type','text');
        $installer->endSetup();
    }
}
