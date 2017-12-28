<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RicardoMartins\PagSeguro\Model\System\Config\Source\Customer;

/**
 * Source model DOB options
 */
class Dob implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \RicardoMartins\PagSeguro\Helper\Internal
     */
    protected $pagSeguroHelper;

    /**
     * @param \RicardoMartins\PagSeguro\Helper\Internal $pagSeguroHelper
     */
    public function __construct(
            \RicardoMartins\PagSeguro\Helper\Internal $pagSeguroHelper
    ){
        $this->pagSeguroHelper = $pagSeguroHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $fields = $this->pagSeguroHelper->getFields('customer');
        $options = [];
        $options[] = array('value'=>'','label'=> __('Ask for it in the credit card form'));

        foreach ($fields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                $options[] = array(
                    'value' => $value['attribute_code'],
                    'label' => $value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                );
            }
        }

        return $options;
    }
}
