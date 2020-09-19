<?php

namespace RicardoMartins\PagSeguro\Model\Method;

use Magento\Directory\Helper\Data as DirectoryHelper;

/**
 * Abstract extension - shared methods for PagSeguro Payment
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2020 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 */

class AbstractMethodExtension extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $pagSeguroHelper;
    protected $logger;
/*
\RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
\RicardoMartins\PagSeguro\Helper\Logger $pagSegurologger
) {
$this->pagSeguroHelper = $pagSeguroHelper;
$this->logger = $pagSegurologger;
  */

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null,
        \RicardoMartins\PagSeguro\Helper\Data $pagSeguroHelper,
        \RicardoMartins\PagSeguro\Helper\Logger $pagSegurologger
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig,
            $logger, $resource, $resourceCollection, $data, $directory);
        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->logger = $pagSegurologger;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }
        // recupera a informação adicional do PagSeguro
        $info           = $this->getInfoInstance();
        $transactionId = $payment->getAdditionalInformation('transaction_id');

        $params = [
            'transactionCode'   => $transactionId,
            'refundValue'       => number_format($amount, 2, '.', '')
        ];

        $params['token'] = $this->pagSeguroHelper->getToken();
        $params['email'] = $this->pagSeguroHelper->getMerchantEmail();

        try {
            // call API - refund
            $returnXml  = $this->pagSeguroHelper->callApi($params, $payment, 'transactions/refunds');

            if ($returnXml === null) {
                $errorMsg = 'Impossível gerar reembolso. Aldo deu errado.';
                throw new \Magento\Framework\Validator\Exception($errorMsg);
            }
        } catch (\Exception $e) {
            $this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
            $this->pagSeguroHelper->writeLog(__('Payment refunding error.'));
            throw new \Magento\Framework\Validator\Exception(__('Payment refunding error.'));
        }

        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);

        return $this;
    }
}
