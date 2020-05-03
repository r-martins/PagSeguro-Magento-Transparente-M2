<?php
namespace RicardoMartins\PagSeguro\Controller\Ajax;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class UpdatePaymentHashes
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Controller\Ajax
 */
class UpdatePaymentHashes extends \Magento\Framework\App\Action\Action
{
     /**
     * Checkout Session
     *
     * @var \Magento\Checkout\Model\Session
     */ 
    protected $checkoutSession;

    /** @var \Magento\Framework\Serialize\SerializerInterface  */
    protected $serializer;

    /**
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Framework\App\Action\Context            $context
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
         \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Serialize\SerializerInterface $serializer
 
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->serializer = $serializer;
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
            $this->checkoutSession->setData('PsPayment', $this->serializer->serialize($params));
              
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