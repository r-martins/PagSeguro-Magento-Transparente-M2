<?php
namespace RicardoMartins\PagSeguro\Controller\Test;

/**
 * Class GetConfig
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Controller\Test
 */
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
        \RicardoMartins\PagSeguro\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
    )
    {
        $this->_helper = $helper;
        $this->resultJsonFactory = $jsonFactory;
        return parent::__construct($context);
    }

    /**
     * Function execute
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

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