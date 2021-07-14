<?php

namespace RicardoMartins\PagSeguro\Model\CronJob;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoresConfig;

/**
 * Class CleanExpiredOrders - Avoiding automatic order cancellation for PagSeguro
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 */
class CleanExpiredOrders extends \Magento\Sales\Model\CronJob\CleanExpiredOrders
{

    /**
     * @var Resource
     */
    protected $_resource;

    public function __construct(
        StoresConfig $storesConfig,
        CollectionFactory $collectionFactory,
        OrderManagementInterface $orderManagement = null,
        ResourceConnection $resource,
        ProductMetadataInterface $productMetadata
    ) {
        $this->_resource = $resource;
        if (version_compare($productMetadata->getVersion(), '2.3.0', '<=')) {
            return parent::__construct($storesConfig, $collectionFactory);
        }
        return parent::__construct($storesConfig, $collectionFactory, $orderManagement);
    }

    /**
     * @var StoresConfig
     */
    protected $storesConfig;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /** @inheritDoc */
    public function execute()
    {
        $connection  = $this->_resource->getConnection();
        $salesOrderPaymentTableName   = $connection->getTableName('sales_order_payment');

        $lifetimes = $this->storesConfig->getStoresConfigByPath('sales/orders/delete_pending_after');
        foreach ($lifetimes as $storeId => $lifetime) {
            /** @var $orders \Magento\Sales\Model\ResourceModel\Order\Collection */
            $orders = $this->orderCollectionFactory->create();
            $orders->addFieldToFilter('store_id', $storeId);
            $orders->addFieldToFilter('status', Order::STATE_PENDING_PAYMENT);
            $orders->getSelect()->joinLeft(
                ['payment' => $salesOrderPaymentTableName],
                'payment.parent_id = main_table.entity_id',
                ['payment_method' => 'payment.method']
            );

            //avoids automatically order cancellation in orders made with rm_pagseguro*
            $orders->getSelect()->where(
                new \Zend_Db_Expr('TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) >= ' . $lifetime * 60)
            )
                ->where('payment.method NOT LIKE \'rm_pagseguro%\'');

            foreach ($orders->getAllIds() as $entityId) {
                $this->orderManagement->cancel((int)$entityId);
            }
        }
    }
}
