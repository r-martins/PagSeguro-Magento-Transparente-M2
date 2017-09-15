<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RicardoMartins\PagSeguro\Model\System\Config\Source;

/**
 * Source model for CC flags
 */
class Ccbrand implements \Magento\Framework\Option\ArrayInterface
{
    
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = array();
        $options[] = array('value'=>'42x20','label'=> __('42x20 px'));
        $options[] = array('value'=>'68x30','label'=> __('68x30 px'));
        $options[] = array('value'=>'','label'=> __('Show text only'));

        return $options;
    }
}
