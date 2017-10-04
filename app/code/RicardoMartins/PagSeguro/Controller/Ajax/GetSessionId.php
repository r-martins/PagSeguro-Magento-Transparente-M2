<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RicardoMartins\PagSeguro\Controller\Ajax;

use Magento\Framework\Controller\ResultFactory;

class GetSessionId extends \Magento\Framework\App\Action\Action
{
   
 
     /**
     * PagSeguro Helper
     *
     * @var RicardoMartins\PagSeguro\Helper\Data;
     */ 
    protected $pagSeguroHelper;


     /**
     * @param \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
         \Magento\Framework\App\Action\Context $context
 
    ) {
        
        $this->pagSeguroHelper = $pagSeguroHelper;       
        parent::__construct($context);
    }
        

    /**
    * @return json
    */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);     
        try{
             $session_id = $this->pagSeguroHelper->getSessionId();
             $result = array(
                'status'=> 'success',
                'session_id' => $session_id
            );
         }catch (\Exception $e) {
            $result = array('status'=> 'error','message' => $e->getMessage());
        }

        $resultJson->setData($result);         
        return $resultJson;
        
        
    }
}
