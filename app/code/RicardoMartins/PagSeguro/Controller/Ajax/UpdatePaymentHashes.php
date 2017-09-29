<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RicardoMartins\PagSeguro\Controller\Ajax;

use Magento\Framework\Controller\ResultFactory;

class UpdatePaymentHashes extends \Magento\Framework\App\Action\Action
{
   
 
     /**
     * Checkout Session
     *
     * @var \Magento\Checkout\Model\Session
     */ 
    protected $checkoutSession;


     /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
         \Magento\Framework\App\Action\Context $context
 
    ) {
        
        $this->checkoutSession = $checkoutSession;       
        parent::__construct($context);
    }
        

    /**
    * @return json
    */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);     
        try{
            $params = $this->getRequest()->getPost('payment');
             $this->checkoutSession->setData('PsPayment', serialize($params));
             $result = array(
                'status'=> 'success',
                'message' => __('Updated Payment Hashes')
            );
         }catch (\Exception $e) {
            $result = array('status'=> 'error','message' => $e->getMessage());
        }

        $resultJson->setData($result);         
        return $resultJson;
        
        
    }
}
