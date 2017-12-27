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
        /** @var RicardoMartins_PagSeguro_Model_Abstract $model */
       $this->pagSeguroHelper->writeLog(
                'Notification received from the payment with the parameters:'
                . var_export($this->getRequest()->getParams(), true)
            );
        $response = $this->pagSeguroAbModel->getNotificationStatus($this->getRequest()->getPost('notificationCode'));
        if (false === $response) {
            throw new \Magento\Framework\Validator\Exception('Failed to process PagSeguro XML return.');
        }
        //$xml = \SimpleXML_Load_String(trim($response));
        $this->pagSeguroAbModel->proccessNotificatonResult($xml);
    }
}
