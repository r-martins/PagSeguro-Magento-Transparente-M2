<?php
 
namespace RicardoMartins\PagSeguro\Model;

use RicardoMartins\PagSeguro\Model\Method\Redirect;
use RicardoMartins\PagSeguro\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;

class AdditionalConfigProvider implements ConfigProviderInterface
{
    const DESCRIPTION = 'payment/rm_pagseguro_pagar_no_pagseguro/description';

    /**
     * PagSeguro Helper
     *
     * @var Data;
     */
    protected $pagSeguroHelper;

    /**
     * @param Data $pagSeguroHelper
     */
    public function __construct(
        Data $pagSeguroHelper
 
    ) {
        $this->pagSeguroHelper = $pagSeguroHelper;
    }

    public function getConfig() {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $config = [
            'payment' => array (
                Redirect::CODE => array (
                    'description' => $this->pagSeguroHelper->getStoreConfigValue(self::DESCRIPTION),
                )
            )
        ];

        return $config;
    }
}