<?php
namespace RicardoMartins\PagSeguro\Controller\Notification;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Controller responsible for receiving PagSeguro notifications (update orders statuses)
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 */
class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
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

    /** @var CacheInterface */
    protected $cache;

     /**
      * @param \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper
      * @param \RicardoMartins\PagSeguro\Model\Notifications $pagSeguroAbModel
      * @param \Magento\Framework\App\Action\Context $context
      */
    public function __construct(
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \RicardoMartins\PagSeguro\Model\Notifications $pagSeguroAbModel,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->pagSeguroAbModel = $pagSeguroAbModel;
        $this->cache = $cache;
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
            throw new \Magento\Framework\Validator\Exception(
                new \Magento\Framework\Phrase('Parâmetro notificationCode não recebido.')
            );
        }
        $response = $this->pagSeguroAbModel->getNotificationStatus($notificationCode);

        if (false === $response) {
            throw new \Magento\Framework\Validator\Exception(
                new \Magento\Framework\Phrase('Failed to process PagSeguro XML return.')
            );
        }

        //checks if it's a duplicated notification
        $contentCacheId = hash('sha256', $response->asXML());
        if ($this->cache->load($contentCacheId)) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            return $result->setData(
                ['success' => false,
                 'message' => 'Duplicated Transaction. This transaction was already received in less than 60 seconds.']
            );
        }

        $processReturn = $this->pagSeguroAbModel->proccessNotificatonResult($response);
        if (false === $processReturn) {
            throw new \Magento\Framework\Validator\Exception(
                new \Magento\Framework\Phrase(
                    'Failed to process PagSeguro response. A new attempt may happen in a few minutes.'
                )
            );
        }

        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return $result->setData(['success'=>true]);
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
