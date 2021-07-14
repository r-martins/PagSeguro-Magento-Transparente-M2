<?php

namespace RicardoMartins\PagSeguro\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Paymentgroups Source model used for Redirect payment
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Model\System\Config\Source
 */
class Paymentgroups implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = array();
        $options[] = array('value' => 'CREDIT_CARD', 'label' => __('Cartões de Crédito'));
        $options[] = array('value' => 'BOLETO', 'label' => __('Boleto'));

        return $options;
    }
}