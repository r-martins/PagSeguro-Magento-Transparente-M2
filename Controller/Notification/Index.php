<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RicardoMartins\PagSeguro\Controller\Notification;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{    
     /**
     * PagSeguro Helper
     *
     * @var RicardoMartins\PagSeguro\Helper\Data;
     */ 
    protected $pagSeguroHelper;

    /**
     * PagSeguro Abstract Model
     *
     * @var RicardoMartins\PagSeguro\Model\Notifications
     */ 
    protected $pagSeguroAbModel;


     /**
     * @param \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper
     * @param \RicardoMartins\PagSeguro\Model\Notifications $pagSeguroAbModel
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \RicardoMartins\PagSeguro\Model\Notifications $pagSeguroAbModel,
         \Magento\Framework\App\Action\Context $context
 
    ) {
        
        $this->pagSeguroHelper = $pagSeguroHelper;       
        $this->pagSeguroAbModel = $pagSeguroAbModel;       
        parent::__construct($context);
    }
        

    /**
    * @return json
    */
    public function execute()
    {
        $notificationCode = $this->getRequest()->getPost('notificationCode', false);
        if (false === $notificationCode) {
            //@TODO Implement nice notification page with form and notificationCode
            throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase('Parâmetro notificationCode não recebido.'));
        }
        $response = $this->pagSeguroAbModel->getNotificationStatus($notificationCode);

        if (false === $response) {
            throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase('Failed to process PagSeguro XML return.'));
        }

        $this->pagSeguroAbModel->proccessNotificatonResult($response);
    }
}
