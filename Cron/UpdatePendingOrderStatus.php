<?php

namespace RicardoMartins\PagSeguro\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Psr\Log\LoggerInterface;
use RicardoMartins\PagSeguro\Helper\Data as PsHelper;
use RicardoMartins\PagSeguro\Model\Notifications;

class UpdatePendingOrderStatus
{
    const NEXT_UPDATE_TIME = 6; // expressed in hours
    const FILTER_DAYS_BEFORE = 7;
    const FILTER_ORDER_STATUS = [
        Order::STATE_PENDING_PAYMENT,
        //Order::STATE_NEW,
    ];
    const FILTER_PAYMENT_METHODS = [
        \RicardoMartins\PagSeguro\Model\Method\Cc::CODE,
        \RicardoMartins\PagSeguro\Model\Method\Twocc::CODE,
        \RicardoMartins\PagSeguro\Model\Method\Boleto::CODE,
        //\RicardoMartins\PagSeguro\Model\Method\Redirect::CODE,
        \RicardoMartins\PagSeguro\Model\Method\Tef::CODE,
    ];

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Notifications
     */
    private $notificationModel;

    /**
     * @var PsHelper
     */
    private $psHelper;

    /**
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param LoggerInterface $logger
     * @param Notifications $notificationModel
     * @param PsHelper $psHelper
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        LoggerInterface $logger,
        Notifications $notificationModel,
        PsHelper $psHelper
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
        $this->notificationModel = $notificationModel;
        $this->psHelper = $psHelper;
    }

   /**
    * Iterates throught pending orders and updates its payment status
    * @return void
    */
    public function execute()
    {
        // checks if the feature is enabled on config
        if (!$this->psHelper->isUpdaterEnabled()) {
            return;
        }

        foreach ($this->_getOrderCollection() as $order) {
            // checks if its to update this order
            if (!$this->_canUpdate($order)) {
                continue;
            }

            foreach ($this->_getTransactionsIds($order) as $transactionId) {
                try {
                    $responseXml = $this->psHelper->consultTransactionOnApi($transactionId);
                    $newStateObject = $this->notificationModel->processStatus(
                        (string) $responseXml->status,
                        $order->getPayment()->getMethod()
                    );

                    // checks if the order was already updated
                    if ($newStateObject->getState() == $order->getState()) {
                        continue;
                    }

                    $this->notificationModel->proccessNotificatonResult($responseXml);

                    if ($order->getPayment()->getMethod() == \RicardoMartins\PagSeguro\Model\Method\Twocc::CODE) {
                        // if one transaction was cancelled, we doesnt want to process the other (2cc)
                        // because if the first transaction is cancelled, the processNotificationResult above would
                        // cancel and refund the second one
                        if (in_array((string) $responseXml->status, ['6', '7'])) {
                            break;
                        }

                        // reloads the order to get updated to avoid error loading old order data from memory in the
                        // next loop
                        $order->load($order->getId());
                    }

                } catch (LocalizedException $e) {
                    $this->logger->warning(__(
                        "[PagSeguro Updater] Could not update order %1 | transaction %2: %3",
                        $order->getIncrementId(),
                        $transactionId,
                        $e->getMessage()
                    ));
                }
            }
        }
    }

    /**
     * Retrieves the transaction ID's of an order
     * @param Order $order
     * @return OrderCollection
     */
    protected function _getTransactionsIds($order)
    {
        $payment = $order->getPayment();
        $transactionIds = [
            $payment->getAdditionalInformation('transaction_id')
        ];

        if ($payment->getMethod() == \RicardoMartins\PagSeguro\Model\Method\Twocc::CODE) {
            $transactionIds = [
                $payment->getAdditionalInformation('transaction_id_first'),
                $payment->getAdditionalInformation('transaction_id_second'),
            ];
        }

        return $transactionIds;
    }

    /**
     * Creates and loads the order collection to update
     * @return OrderCollection
     */
    protected function _getOrderCollection()
    {
        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            null,
            null,
            'yyyy-MM-dd 00:00:00'
        );

        $formatter->setTimeZone(new \DateTimeZone('UTC'));

        $now = new \DateTime();
        $now->sub(new \DateInterval('P' . self::FILTER_DAYS_BEFORE . 'D'));
        $formattedDate = $formatter->format($now);

        $collection = $this->orderCollectionFactory->create()
            ->addAttributeToFilter("created_at", ["from" => $formattedDate])
            ->addAttributeToFilter("state", ["in" => self::FILTER_ORDER_STATUS])
            ->addAttributeToSort("created_at", "ASC");

        $collection->getSelect()->join(
            ['payment_t' => $collection->getResource()->getTable('sales_order_payment')],
            'payment_t.parent_id = main_table.entity_id AND payment_t.method in ' .
            '(\'' . implode('\',\'', self::FILTER_PAYMENT_METHODS) . '\')',
            ['payment_method' => 'payment_t.method']
        );

        return $collection;
    }

    /**
     * Checks if order can be updated, based on last update time
     * @param Order $order
     * @return bool
     */
    protected function _canUpdate($order)
    {
        $payment = $order->getPayment();
        $nextUpdate = $payment->getAdditionalInformation('ps_next_update');

        if (!$nextUpdate) {
            $this->_refreshNextUpdateTime($order);
            return true;
        }

        $nextUpdateDate = new \DateTime($nextUpdate);
        $now = new \DateTime();

        if ($nextUpdateDate < $now) {
            $this->_refreshNextUpdateTime($order);
            return true;
        }

        return false;
    }

    /**
     * Sets a new update time to the order
     * @param Order $order
     */
    protected function _refreshNextUpdateTime($order)
    {
        $payment = $order->getPayment();

        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            null,
            null,
            'yyyy-MM-dd HH:mm:ss'
        );

        $formatter->setTimeZone(new \DateTimeZone('UTC'));

        $now = new \DateTime();
        $now->sub(new \DateInterval('PT' . self::NEXT_UPDATE_TIME . 'H'));
        $nextUpdate = $formatter->format($now);

        $payment->setAdditionalInformation('ps_next_update', $nextUpdate);
        $payment->save();
    }
}
