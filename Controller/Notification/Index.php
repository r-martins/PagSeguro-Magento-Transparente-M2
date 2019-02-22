<?php
namespace RicardoMartins\PagSeguro\Controller\Notification;

/**
 * Class Index
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Controller\Notification
 */
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