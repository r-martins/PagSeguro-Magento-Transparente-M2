<?php
namespace RicardoMartins\PagSeguro\Controller\Ajax;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class GetSessionId
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Controller\Ajax
 */
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