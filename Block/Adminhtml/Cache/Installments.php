<?php

namespace RicardoMartins\PagSeguro\Block\Adminhtml\Cache;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Permissions
 */
class Installments extends Template
{
    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = [] )
    {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Verifies if the functionality is enable on admin configuration
     */
    public function isInstallmentsConfigEnable()
    {
        return $this->scopeConfig->isSetFlag('payment/rm_pagseguro_cc/show_installments_product_page');
    }

    /**
     * Retrieves the URL to clean the cache
     */
    public function getFlushCacheUrl()
    {
        return $this->getUrl('pseguroadmin/cache/flushProductInstallments');
    }
}
