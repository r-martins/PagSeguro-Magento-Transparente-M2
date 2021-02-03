<?php


namespace RicardoMartins\PagSeguro\Block\Adminhtml\Payment\Info;


class Actions extends \Magento\Backend\Block\Template
{


    /**
     * @var \Magento\Framework\Authorization\PolicyInterface
     */
    private $policy;
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $authSession;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context, array $data = [],
        \Magento\Framework\Authorization\PolicyInterface $policy,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        parent::__construct($context, $data);
        $this->policy = $policy;
        $this->authSession = $authSession;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getPayment()->getOrder();
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        return $this->getParentBlock()->getPayment();
    }

    /**
     * @return bool
     */
    public function isPagSeguro()
    {
        return strpos($this->getPayment()->getMethod(), 'rm_pagseguro') !== false;
    }

    public function getUpdateOrderUrl()
    {
        $info = $this->getPayment()->getAdditionalInformation();
        if (isset($info['transaction_id'])) {
            return $this->getUrl('pseguroadmin/update/index', ['transactionId' => $info['transaction_id']]);
        }

        return false;
    }

    public function isAllowed($resource)
    {
        $roleId = $this->authSession->getUser()->getAclRole();
        return $this->policy->isAllowed($roleId, $resource);
    }
}
