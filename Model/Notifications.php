<?php
namespace RicardoMartins\PagSeguro\Model;

/**
 * Processes notifications from PagSeguro
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 */
class Notifications extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * PagSeguro Helper
     *
     * @var RicardoMartins\PagSeguro\Helper\Data;
     */
    protected $pagSeguroHelper;

    /**
     * Magento Sales Order Model
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $orderModel;

    protected $orderRepository;

    /**
     * Magento Invoice Service
     *
     * @var \Magento\Sales\Model\Service\InvoiceService
     */

    /** @var \Magento\Sales\Model\Service\InvoiceService  */
    protected $invoiceService;

    /** @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender  */
    protected $invoiceSender;

    /** @var \Magento\Framework\App\CacheInterface */
    protected $cache;

    /**
     * Magento transaction Factory
     *
     * @var \Magento\Framework\DB\Transaction
     */
    protected $transactionFactory;

    /** @var \Magento\Sales\Model\Order */
    protected $orderData;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \Magento\Sales\Api\Data\OrderInterface $orderModel,
        \Magento\Framework\DB\Transaction $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $commentSender,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\App\CacheInterface $cache,        
        \Magento\Sales\Model\Order $orderData,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );

        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->orderModel = $orderModel;
        $this->transactionFactory = $transactionFactory;
        $this->_commentSender = $commentSender;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->cache = $cache;
        $this->orderData = $orderData;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Processes notification XML data. XML is sent right after order is sent to PagSeguro, and on order updates.
     * @param SimpleXMLElement $resultXML
     */
    public function proccessNotificatonResult($resultXML, $_payment = false) 
    {
        if (isset($resultXML->error)) {
            $errMsg = __((string)$resultXML->error->message);
            throw new \Magento\Framework\Validator\Exception(
                __(
                    'Problemas ao processar seu pagamento. %s(%s)',
                    $errMsg,
                    (string)$resultXML->error->code
                )
            );
        }

        if (isset($resultXML->reference)) {
            if (is_object($_payment) && $_payment instanceof \Magento\Payment\Model\InfoInterface) {
                $order = $_payment->getOrder();
                $payment = $_payment;
            } else {
                $orderNo = (string)$resultXML->reference;
                if (false !== $twoCard = strpos ( $orderNo , "-cc" )) {
                    $orderNo = substr($orderNo,0, $twoCard);
                }
//                $order = $this->orderModel->loadByIncrementId($orderNo);
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('increment_id', $orderNo, 'eq')->create();
                $orderList = $this->orderRepository->getList($searchCriteria)->getItems();

                /** @var \Magento\Sales\Model\Order $order */
                $order = reset($orderList) ?? false;
                if (!$order) {
                    $this->pagSeguroHelper->writeLog(
                        new \Magento\Framework\Phrase(
                            'Order %1 not found on system. Unable to process return. '
                                . 'A new attempt may happen in a few minutes.',
                            [$orderNo]
                        )
                    );
                    return false;
                }
                $payment = $order->getPayment();
            }

            //PagSeguro sends the same notification multiple times in the same minute.
            //Let's cache it and check if it's a duplicated.
            $contentCacheId = hash('sha256', $resultXML->asXML());
            $this->cache->save('1', $contentCacheId, ['RM_PAGSEGURO_NOTIFICATION'], 60);

            $this->_code = $payment->getMethod();
            $processedState = $this->processStatus((int)$resultXML->status);

            $isFirst = false;
            $isSecond = false;

            $transactionIdFirst = null;
            $transactionIdSecond = null;

            if ($this->_code == \RicardoMartins\PagSeguro\Model\Method\Twocc::CODE) {
                $transaction_id = (string)$resultXML->code;
                $transaction_id_first = (string)$payment->getAdditionalInformation('transaction_id_first');
                $transaction_id_second = (string)$payment->getAdditionalInformation('transaction_id_second');
                $transactionIdFirst = $this->pagSeguroHelper->getTransaction($transaction_id_first, $payment);
                $transactionIdSecond = $this->pagSeguroHelper->getTransaction($transaction_id_second, $payment);

                $isFirst = ($transaction_id === $transaction_id_first);
                $isSecond = ($transaction_id === $transaction_id_second);
            }

            $message = $processedState->getMessage();

            if ($isFirst) {
                $message = '1º ' . __('Credit Card') .' '. $message;
            }
            if ($isSecond) {
                $message = '2º ' . __('Credit Card') .' '. $message;
            }

            if ((int)$resultXML->status == 6) { //valor devolvido (gera credit memo e tenta cancelar o pedido)

                if ($order->canUnhold()) {
                    $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED);
                }

                if ($order->canCancel()) {
                    $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
                } else {
                    $this->pagSeguroHelper->writeLog("can't hold and can't cancel");
                    $payment->registerRefundNotification(floatval($resultXML->grossAmount));
                    $order->addStatusHistoryComment(
                        'Returned: Amount returned to buyer.'
                    );
                }
                if ($this->_code == \RicardoMartins\PagSeguro\Model\Method\Twocc::CODE) {
                    $this->pagSeguroHelper->TwoCardCancel($payment);
                }
            }

            if ((int)$resultXML->status == 7 && isset($resultXML->cancellationSource)) {
                //Especificamos a fonte do cancelamento do pedido
                switch ((string)$resultXML->cancellationSource) {
                    case 'INTERNAL':
                        $message .= __('PagSeguro itself denied or canceled the transaction.');
                        break;
                    case 'EXTERNAL':
                        $message .= __('The transaction was denied or canceled by the bank.');
                        break;
                }

                $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
                $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);

                $cancelOrder = $this->orderData->load($order->getId());
                $cancelOrder->cancel()->save();
                if ($this->_code == \RicardoMartins\PagSeguro\Model\Method\Twocc::CODE) {
                    $this->pagSeguroHelper->TwoCardCancel($payment);
                }
            }

            if ($processedState->getStateChanged()) {

                // somente para o status 6 que edita o status do pedido - Weber
                if ((int)$resultXML->status != 6) {

                    $this->pagSeguroHelper->writeLog("State: ". $processedState->getState());

                    $order->setState($processedState->getState());
                    $order->setStatus($processedState->getState());

                    if ($this->_code === \RicardoMartins\PagSeguro\Model\Method\Twocc::CODE && (int)$resultXML->status == 3) {
                        $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                        $order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                        if (is_object($_payment)) {
                            $_payment->setIsTransactionPending(true);
                        }
                    }

                    $order->addStatusHistoryComment($message);

                    if ((int)$resultXML->status == 1 && is_object($_payment)) {
                        $_payment->setIsTransactionPending(true);
                    }

                }

            } else {
                $order->addStatusHistoryComment($message);
            }

            if (in_array((int)$resultXML->status, array(3, 4))) { //Pago ou Disponivel

                if ($this->_code === \RicardoMartins\PagSeguro\Model\Method\Twocc::CODE) {
                    $transaction_id = (string)$resultXML->code;
                    $payment->setTransactionId($transaction_id);
                    
                    $transaction = $payment->addTransaction(
                        \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
                        null,
                        true
                    );

                    $createInvoices = false;

                    if ( ($isFirst && $transactionIdSecond) || ($isSecond && $transactionIdFirst) )
                    {
                            $createInvoices = true;
                            $order->setState($processedState->getState());
                            $order->setStatus($processedState->getState());
                    }

                    if ( $createInvoices && !$order->hasInvoices()) {
                        $invoice = $this->invoiceService->prepareInvoice($order);
                        $msg = sprintf('Captured payment. Transaction Identifier: %s', (string)$resultXML->code);
                        $invoice->addComment($msg);
                        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                        $invoice->register();
    
                        if ($this->pagSeguroHelper->getStoreConfigValue('payment/rm_pagseguro/send_invoice_email')) {
                            try {
                                $this->invoiceSender->send($invoice);
                            } catch (\Exception $e) {
                                $this->logger->debug(__('We can\'t send the invoice email right now.'));
                            }
                        }
    
                        // salva o transaction id na invoice
                        if (isset($resultXML->code)) {
                            $invoice->setTransactionId((string)$resultXML->code)->save();
                            $paymentAdditionalInformation = $payment->getAdditionalInformation();
                            $paymentAdditionalInformation['transaction_id'] = (string)$resultXML->code;
                            $payment->setAdditionalInformation($paymentAdditionalInformation)->save();
                        }
    
                        $this->transactionFactory->addObject($invoice)
                            ->addObject($invoice->getOrder())
                            ->save();
                        $order->addStatusHistoryComment(
                            sprintf('Invoice #%s successfully created.', $invoice->getIncrementId())
                        );
    
                    }

                } else {
                    if (!$order->hasInvoices()) {
                        $invoice = $this->invoiceService->prepareInvoice($order);
                        $msg = sprintf('Captured payment. Transaction Identifier: %s', (string)$resultXML->code);
                        $invoice->addComment($msg);
                        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                        $invoice->register();
    
                        if ($this->pagSeguroHelper->getStoreConfigValue('payment/rm_pagseguro/send_invoice_email')) {
                            try {
                                $this->invoiceSender->send($invoice);
                            } catch (\Exception $e) {
                                $this->logger->debug(__('We can\'t send the invoice email right now.'));
                            }
                        }
    
                        // salva o transaction id na invoice
                        if (isset($resultXML->code)) {
                            $invoice->setTransactionId((string)$resultXML->code)->save();
                            $paymentAdditionalInformation = $payment->getAdditionalInformation();
                            $paymentAdditionalInformation['transaction_id'] = (string)$resultXML->code;
                            $payment->setAdditionalInformation($paymentAdditionalInformation)->save();
                        }
    
                        $this->transactionFactory->addObject($invoice)
                            ->addObject($invoice->getOrder())
                            ->save();
                        $order->addStatusHistoryComment(
                            sprintf('Invoice #%s successfully created.', $invoice->getIncrementId())
                        );
    
                    }
                }
            }

            if (!is_object($_payment)) {
                try {
                    $payment->save();
                    $order->save();

                    if ($processedState->getIsCustomerNotified()) {
                        $this->_commentSender->send($order, true, $message);
                    }
                } catch (\Exception $e) {
                    $this->pagSeguroHelper->writeLog($e->getMessage());
                }
            }

        } else {
            throw new \Magento\Framework\Validator\Exception(__('Invalid return. Order reference not found.'));
        }
    }

    /**
     * @param $notificationCode
     * @return SimpleXMLElement
     */
    public function getNotificationStatus($notificationCode)
    {
        //@TODO Remove hard coded URL
        $url = "https://ws.pagseguro.uol.com.br/v3/transactions/notifications/" . $notificationCode;
        if($this->pagSeguroHelper->isSandbox()) {
            $url = "https://ws.ricardomartins.net.br/pspro/v7/wspagseguro/v3/transactions/notifications/" . $notificationCode;
        }

        $params = ['token' => $this->pagSeguroHelper->getToken(),
                   'email' => $this->pagSeguroHelper->getMerchantEmail()];

        if($this->pagSeguroHelper->isSandbox()) { //Sandbox mode
            $params = ['public_key' => $this->pagSeguroHelper->getPagSeguroPubKey(),
                        'isSandbox' => 1];
        }

        $url .= '?' . http_build_query($params);

        //@TODO Add ext-curl to composer
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        try {
            $return = curl_exec($ch);
        } catch (\Exception $e) {
            $this->pagSeguroHelper->writeLog(
                sprintf(
                    'Failed to catch return for notificationCode %s: %s(%d)',
                    $notificationCode,
                    curl_error($ch),
                    curl_errno($ch)
                )
            );
        }

        $this->pagSeguroHelper->writeLog(
            sprintf('Return of the Pagseguro to notificationCode %s: %s', $notificationCode, $return)
        );

        libxml_use_internal_errors(true);
        $xml = \simplexml_load_string(trim($return));
        if (false === $xml) {
            $this->pagSeguroHelper->writeLog('Return XML notification PagSeguro in unexpected format. Return: '
                . $return);
        }

        $this->_eventManager->dispatch('rm_pagseguro_status_notification_received', [
            'responseText' => $return,
            'responseXml'  => $xml,
        ]);

        curl_close($ch);
        return $xml;
    }

    /**
     * Processes order status and return information about order status
     * @param $statusCode
     * @return Object
     */
    public function processStatus($statusCode)
    {
        $return = new \Magento\Framework\DataObject();
        $return->setStateChanged(true);
        $return->setIsTransactionPending(true); //payment is pending?

        switch ($statusCode) {
            case '1':
                $return->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                $return->setIsCustomerNotified(($this->getCode()!='pagseguro_cc')&&($this->getCode()!='pagseguro_twocc'));

                $return->setMessage(
                    __('Awaiting payment: the buyer initiated the transaction, but so far PagSeguro has not '
                    . 'received any payment information.')
                );
                break;
            case '2':
                $return->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
                $return->setIsCustomerNotified(true);
                $return->setMessage(
                    __('Under review: the buyer chose to pay with a credit card and PagSeguro '
                    . 'is analyzing the risk of the transaction.')
                );
                break;
            case '3':
                $return->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $return->setIsCustomerNotified(true);
                $return->setMessage(
                    __('Pay: the transaction was paid by the buyer and PagSeguro has already received '
                    . 'a confirmation of the financial institution responsible for processing.')
                );
                $return->setIsTransactionPending(false);
                break;
            case '4':
                $return->setMessage(
                    __('Available: The transaction has been paid and has reached the end of its has been '
                    . 'returned and there is no open dispute')
                );
                $return->setIsCustomerNotified(false);
                $return->setStateChanged(false);
                $return->setIsTransactionPending(false);
                break;
            case '5':
                $return->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $return->setIsCustomerNotified(false);
                $return->setIsTransactionPending(false);
                $return->setMessage(
                    __('In dispute: the buyer, within the term of release of the transaction, opened a dispute.')
                );
                break;
            case '6':
                $return->setData('state', \Magento\Sales\Model\Order::STATE_CLOSED);
                $return->setIsCustomerNotified(false);
                $return->setIsTransactionPending(false);
                $return->setMessage(__('Returned: The transaction amount was returned to the buyer.'));
                break;
            case '7':
                $return->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
                $return->setIsCustomerNotified(true);
                $return->setMessage(__('Canceled: The transaction was canceled without being finalized.'));
                break;
            case '8':
                $return->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
                $return->setIsCustomerNotified(true);
                $return->setMessage(__('Debited: The transaction amount was returned to the buyer.'));
                break;
            case '9':
                $return->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
                $return->setIsCustomerNotified(true);
                $return->setMessage(__('Temporary Retention: The buyer has opened a dispute with the credit card operator and the transaction is now being analysed.'));
                break;
            default:
                $return->setIsCustomerNotified(false);
                $return->setStateChanged(false);
                $return->setMessage(__('Invalid status code returned by PagSeguro. (%s)', $statusCode));
        }
        return $return;
    }

}
