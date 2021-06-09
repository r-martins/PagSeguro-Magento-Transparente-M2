<?php

namespace RicardoMartins\PagSeguro\Plugin\Block\Cache;

use Magento\Backend\Block\Cache\Permissions as PermissionsBlock;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\AuthorizationInterface;

/**
 * Plugin class to cache permissions block
 */
class Permissions
{
    /**
     * ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * AuthorizationInterface
     */
    protected $authorization;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AuthorizationInterface $authorization
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->authorization = $authorization;
    }

    /**
     * Complements the verification of user's permissions on additional caches
     * @param PermissionsBlock $subject
     * @param bool $result
     * @return bool
     */
    public function afterHasAccessToAdditionalActions(
        PermissionsBlock $subject,
        $result
    ) {
        if ($this->isInstallmentsConfigEnabled()) {
            $result = $result || $this->hasAccessToFlushInstallments();
        }

        return $result;
    }

    /**
     * Verifies if the functionality is enable on admin configuration
     * @return bool
     */
    public function isInstallmentsConfigEnabled()
    {
        return $this->scopeConfig->isSetFlag('payment/rm_pagseguro_cc/show_installments_product_page');
    }

    /**
     * Verifies if user has access to installments cache flush action
     * @return bool
     */
    public function hasAccessToFlushInstallments()
    {
        return $this->authorization->isAllowed("RicardoMartins_PagSeguro::flush_pagseguro_installments");
    }
}
