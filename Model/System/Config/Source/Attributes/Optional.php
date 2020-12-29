<?php
namespace RicardoMartins\PagSeguro\Model\System\Config\Source\Attributes;

/**
 * Class Optional
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Model\System\Config\Source\Attributes
 */
class Optional implements \Magento\Framework\Option\ArrayInterface
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
        $fields = $this->pagSeguroHelper->getFields('customer_address');
         $options = [];
         $options[] = array('value'=>'','label'=> __('Do not Report to PagSeguro'));

        foreach ($fields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                //caso esteja sendo usado a propriedade multilinha do endereco, ele aceita indicar o que cada linha faz
                if ($value['attribute_code'] == 'street') {
                    $streetLines = $this->pagSeguroHelper->getStoreConfig('customer/address/street_lines');
                    for ($i = 1; $i <= $streetLines; $i++) {
                        $options[] = array('value' => 'street_'.$i, 'label' => 'Street Line '.$i);
                    }
                } else {
                    $options[] = array(
                        'value' => $value['attribute_code'],
                        'label' => $value['frontend_label']. ' (' . $value['attribute_code'] . ')'
                    );
                }
            }
        }

        return $options;
    }
}