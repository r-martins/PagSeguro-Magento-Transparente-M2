<?php

namespace RicardoMartins\PagSeguro\Block\Adminhtml\Cache;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class Permissions
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
     * @return bool
     */
    public function hasAccessToFlushPagSeguroInstallments()
    {
        return $this->authorization->isAllowed("RicardoMartins_PagSeguro::flush_pagseguro_installments");
    }
}
