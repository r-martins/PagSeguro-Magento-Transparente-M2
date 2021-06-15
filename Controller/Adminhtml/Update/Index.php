<?php
namespace RicardoMartins\PagSeguro\Controller\Adminhtml\Update;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'RicardoMartins_PagSeguro::pagseguro_manual_update';

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;
    /**
     * @var \RicardoMartins\PagSeguro\Helper\Data
     */
    private $psHelper;
    /**
     * @var \RicardoMartins\PagSeguro\Model\Notifications
     */
    private $notificationModel;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $pageFactory;


    protected function _isAllowed()
    {
        return parent::_isAllowed();
    }

    public function __construct(Context $context, \Magento\Framework\HTTP\Client\Curl $curl, \RicardoMartins\PagSeguro\Helper\Data $psHelper, \RicardoMartins\PagSeguro\Model\Notifications $notificationModel, \Magento\Framework\View\Result\PageFactory $pageFactory)
    {
        $this->curl = $curl;
        $this->psHelper = $psHelper;
        $this->notificationModel = $notificationModel;
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        $url = $this->getUpdateUrl();

        $this->curl->get($url);
        $returnXml = $this->curl->getBody();
        if (!$returnXml) {
            $this->psHelper->writeLog('Tentativa de atualização manual de pedido falhou. Headers de retorno: ' . var_export($this->curl->getHeaders(), true));
            $this->messageManager->addErrorMessage('Tentativa de atualização manual de pedido falhou. Veja o log para mais detalhes.');

            return $resultRedirect;
        }

        libxml_use_internal_errors(true);
        $simpleXml = simplexml_load_string($returnXml);
        if(!$simpleXml) {
            $this->messageManager->addErrorMessage('Tentativa de atualização manual de pedido falhou. Retorno inválido: ' . $returnXml);
            return $resultRedirect;


        }

        try {
            if ($orderNo = (string)$simpleXml->reference) {
                $this->psHelper->writeLog(sprintf('Atualização manual solicitada para o pedido %s.', $orderNo));
            }
            $updated = $this->notificationModel->proccessNotificatonResult($simpleXml);
            if (false === $updated) {
                $this->messageManager->addErrorMessage('Algo deu errado na atualização do pedido. Consulte os logs para mais detalhes.');
            }
        } catch (\Magento\Framework\Validator\Exception $e) {
            $this->messageManager->addErrorMessage('Tentativa de atualização manual de pedido falhou. Erro: ' . $e->getMessage());
        }
        $this->messageManager->addSuccessMessage('Pedido atualizado com sucesso. Veja o último comentário do pedido para maiores detalhes.');
        return $resultRedirect;

    }

    /**
     * Get the correct URL to check the order status
     * @return string
     */
    protected function getUpdateUrl()
    {
        $transactionId = $this->getRequest()->getParam('transactionId');
        $sandbox = ($this->psHelper->isSandbox()) ? 'sandbox.' : '';
        if ($sandbox) {
            $publicKey = $this->psHelper->getPagSeguroPubKey();
            $url = "https://ws.ricardomartins.net.br/pspro/v7/wspagseguro/v3/transactions/$transactionId?public_key=$publicKey&isSandbox=1";
            return $url;
        }

        $email = $this->psHelper->getMerchantEmail();
        $token = $this->psHelper->getToken();
        $url = "https://ws.pagseguro.uol.com.br/v2/transactions/$transactionId?email=$email&token=$token";
        return $url;

    }

}
