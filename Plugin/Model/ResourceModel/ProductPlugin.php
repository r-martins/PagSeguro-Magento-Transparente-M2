<?php

namespace RicardoMartins\PagSeguro\Plugin\Model\ResourceModel;

/**
 * Class ProductPlugin
 *
 * @author    Ricardo Martins
 * @copyright 2020 Magenteiro
 * @package   RicardoMartins\PagSeguro\Plugin\Model\ResourceModel
 */
class ProductPlugin
{
    public function beforeSave(
        \Magento\Catalog\Model\ResourceModel\Product $subject, \Magento\Framework\Model\AbstractModel $object
    ) {
        if ($object->getUpgradingInstallments()) {
            return;
        }
        $object->setCustomAttribute('rm_pagseguro_last_update', 0);
    }
}
