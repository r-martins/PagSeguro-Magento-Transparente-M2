<?php

namespace RicardoMartins\PagSeguro\Block\Adminhtml\Cache;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Installments cache permissions block
 */
class Permissions implements ArgumentInterface
{
    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * Permissions constructor.
     *
     * @param AuthorizationInterface $authorization
     */
    public function __construct(AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * Checks if the user has access to flush cache action
     * @return bool
     */
    public function hasAccessToFlushPagSeguroInstallments()
    {
        return $this->authorization->isAllowed("RicardoMartins_PagSeguro::flush_pagseguro_installments");
    }
}
