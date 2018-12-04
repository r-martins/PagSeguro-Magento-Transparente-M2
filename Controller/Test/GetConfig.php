<?php


namespace RicardoMartins\PagSeguro\Controller\Test;

use Magento\Framework\Controller\ResultFactory;

class GetConfig extends \Magento\Framework\App\Action\Action
{
    /**
     * GetConfig resultPageFactory
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $resultJsonFactory;

    /**
     * GetConfig constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \RicardoMartins\PagSeguro\Helper\Data $helper
    )
    {
        $this->_helper = $helper;
        return parent::__construct($context);
    }

    /**
     * Function execute
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $info = array(
            'Magento Version' => substr($this->_helper->getMagentoVersion(),0,1),
            'RicardoMartins_PagSeguro' => array(
                'version'   => $this->_helper->getModuleInformation()['setup_version'],
                'debug'     => (boolean)$this->_helper->isDebugActive()
            ),
            'configJs'      => json_decode($this->_helper->getConfigJs()),
            'key_validate'  => $this->_helper->validateKey(),
            'token_consistency' => (strlen($this->_helper->getToken()) == 32) ? "Good" : "Token does not consist 32 characters"
        );


        $resultJson->setData($info);
        return $resultJson;
    }
}