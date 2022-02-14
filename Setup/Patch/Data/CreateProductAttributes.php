<?php
    
namespace RicardoMartins\PagSeguro\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class CreateProductAttributes implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    
    /**
     * @var EavSetup
     */
    private $eavSetup;
    
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup,
     * @param EavSetup $eavSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetup $eavSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetup = $eavSetup;
    }

    /**
     * Creates the PagSeguro product attributes
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->_addProductAttribute(
            'rm_interest_options',
            [
                'type'          => 'text',
                'label'         => 'PagSeguro Installments',
                'input'         => '',
                'backend'       => '',
                'frontend'      => '',
                'class'         => '',
                'source'        => '',
                'global'        => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'       => false,
                'required'      => false,
                'sort_order'    => 50,
                'user_defined'  => false,
                'default'       => '',
                'searchable'    => false,
                'filterable'    => false,
                'comparable'    => false,
                'unique'        => false,
                'apply_to'      => '',
                'visible_on_front' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_html_allowed_on_front' => false,
                'used_in_product_listing' => false,
            ]
        );
        
        $this->_addProductAttribute(
            'rm_pagseguro_last_update',
            [
                'type'          => 'int',
                'label'         => 'PagSeguro Installments last update',
                'input'         => '',
                'backend'       => '',
                'frontend'      => '',
                'class'         => '',
                'source'        => '',
                'global'        => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'       => false,
                'required'      => false,
                'sort_order'    => 50,
                'user_defined'  => false,
                'default'       => '',
                'searchable'    => false,
                'filterable'    => false,
                'comparable'    => false,
                'unique'        => false,
                'apply_to'      => '',
                'visible_on_front' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_html_allowed_on_front' => false,
                'used_in_product_listing' => false,
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Does nothing on revert action
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Lists the patch dependencies
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Lists the patch aliases
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Adds a product attribute to the General group of each attribute set
     * @param string $attrCode
     * @param array $attrData
     */
    protected function _addProductAttribute($attrCode, $attrData)
    {
        $this->eavSetup->addAttribute(
            Product::ENTITY,
            $attrCode,
            array_merge(['group' => 'General'], $attrData)
        );
    }
}
