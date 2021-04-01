<?php

namespace RicardoMartins\PagSeguro\Controller\Adminhtml\Cache;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use RicardoMartins\PagSeguro\Model\Cache\Type\Installments as InstallmentsCache;

class FlushProductInstallments extends Action implements HttpGetActionInterface
{
    /** 
     * @var InstallmentsCache
     * */
    protected $installmentsCache;

    /**
     * @param Context $context
     * @param InstallmentsCache $installmentsCache
     */
    public function __construct(
        Context $context,
        InstallmentsCache $installmentsCache
    ) {
        parent::__construct($context);
        $this->installmentsCache = $installmentsCache;
    }

    /**
     * Flushes PagSeguro product installments cache
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        try {
            $this->installmentsCache->flush();
            $this->_eventManager->dispatch('flush_rm_pagseguro_product_installments_after');
            $this->messageManager->addSuccessMessage(__('The product installments cache was flushed.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while clearing the product installments cache.'));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
