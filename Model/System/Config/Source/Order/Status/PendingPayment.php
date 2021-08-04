<?php

namespace RicardoMartins\PagSeguro\Model\System\Config\Source\Order\Status;

/**
 * Order Statuses source model
 */
class PendingPayment extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @var string
     */
    protected $_stateStatuses = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
}
