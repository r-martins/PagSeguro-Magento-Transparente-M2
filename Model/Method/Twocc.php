<?php
namespace RicardoMartins\PagSeguro\Model\Method;

use RicardoMartins\PagSeguro\Model\Exception\WrongInstallmentsException;

/**
 * Credit Card Payment Method for PagSeguro Payment
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2020 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 */
class Twocc extends \Magento\Payment\Model\Method\Cc
{
    /**
     * @var string
     */
    protected $_formBlockType = \RicardoMartins\PagSeguro\Block\Form\Cc::class;
    protected $_infoBlockType = \RicardoMartins\PagSeguro\Block\Payment\InfoCc::class;

    const CODE = 'rm_pagseguro_twocc';

    protected $_code = self::CODE;
    protected $_isGateway                   = true;
    protected $_canCapture                  = false;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_countryFactory;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['BRL'];

    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
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
     * Backend Auth Session
     *
     * @var Magento\Backend\Model\Auth\Session $adminSession
     */
    protected $adminSession;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    protected $request;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $transactionBuilder;

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
        \RicardoMartins\PagSeguro\Model\Notifications $pagSeguroAbModel,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        array $data = [],
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );

        $this->_countryFactory = $countryFactory;

        $this->pagSeguroHelper = $pagSeguroHelper;
        $this->pagSeguroAbModel = $pagSeguroAbModel;
        $this->adminSession = $adminSession;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->transactionBuilder = $transactionBuilder;
    }

    /**
     * Payment capturing
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /*@var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $transactions = [];

        try {
            //First Credit Card
            $returnXmlFirst = $this->_createTransaction($payment, '_first');

            if (isset($returnXmlFirst->errors)) {
                $errMsg = [];
                foreach ($returnXmlFirst->errors as $error) {
                    $message = $this->pagSeguroHelper->translateError((string)$error->message);
                    $errMsg[] = $message . '(' . $error->code . ')';
                }
                throw new \Magento\Framework\Validator\Exception(
                    'Um ou mais erros ocorreram no seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg)
                );
            }

            if (isset($returnXmlFirst->error)) {
                $error = $returnXmlFirst->error;
                $message = $this->pagSeguroHelper->translateError((string)$error->message);
                $errMsg[] = $message . ' (' . $error->code . ')';
                throw new \Magento\Framework\Validator\Exception(__(
                    'Um erro ocorreu em seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg)
                ));
            }

            /* process return result code status*/
            if ((int)$returnXmlFirst->status == 6 || (int)$returnXmlFirst->status == 7) {
                throw new \Magento\Framework\Validator\Exception('An error occurred in your payment.');
            }

            $transactionId  = (string)$returnXmlFirst->code;
            $transactions[] = $transactionId;
            
            //Second Credit Card
            $returnXmlSecond = $this->_createTransaction($payment, '_second');

            if (isset($returnXmlSecond->errors)) {
                $errMsg = [];
                foreach ($returnXmlSecond->errors as $error) {
                    $message = $this->pagSeguroHelper->translateError((string)$error->message);
                    $errMsg[] = $message . '(' . $error->code . ')';
                }

                $this->TransactionCancel($payment, $transactionId);
                throw new \Magento\Framework\Validator\Exception(
                    'Um ou mais erros ocorreram no seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg)
                );
            }

            if (isset($returnXmlSecond->error)) {
                $error = $returnXmlSecond->error;
                $message = $this->pagSeguroHelper->translateError((string)$error->message);
                $errMsg[] = $message . ' (' . $error->code . ')';
                $this->TransactionCancel($payment, $transactionId);
                throw new \Magento\Framework\Validator\Exception(
                    'Um erro ocorreu em seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg)
                );
            }

            /* process return result code status*/
            if ((int)$returnXmlSecond->status == 6 || (int)$returnXmlSecond->status == 7) {
                $this->TransactionCancel($payment, $transactionId);
                throw new \Magento\Framework\Validator\Exception('An error occurred in your payment.');
            }

            $transactionId  = (string)$returnXmlSecond->code;
            $transactions[] = $transactionId;

            $payment->setSkipOrderProcessing(true);

            /** TODO Pegar a Invoice de cada cartão para criar a TransactionId */
            if (isset($returnXmlFirst->code) && isset($returnXmlSecond->code)) {
                $transaction_id_first  = (string)$returnXmlFirst->code;
                $transaction_id_second = (string)$returnXmlSecond->code;
                $additional = [];
                $additional['transaction_id'] = "{$transaction_id_first}_{$transaction_id_second}";
                $additional['transaction_id_first'] = (string)$returnXmlFirst->code;
                $additional['transaction_id_second'] = (string)$returnXmlSecond->code;
                //Sandbox Mode
                if ($this->pagSeguroHelper->isSandbox()) {
                    $additional['is_sandbox'] = '1';
                }

                if ($existing = $payment->getAdditionalInformation()) {
                    if (is_array($existing)) {
                        $additional = array_merge($additional, $existing);
                    }
                }
                $payment->setAdditionalInformation($additional);
                $invoices = $order->getInvoiceCollection();
                foreach($invoices as $invoice){
                    $invoice->setTransactionId("{$transaction_id_first}_{$transaction_id_second}");
                    $invoice->save();
                }

                $trans = $this->transactionBuilder;
                $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transaction_id_first . "-" . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH)
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $additional]
                )
                ->setFailSafe(true)                
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);

                $trans = $this->transactionBuilder;
                $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transaction_id_second . "-" . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH)
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $additional]
                )
                ->setFailSafe(true)                
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);
            }

            $this->pagSeguroAbModel->proccessNotificatonResult($returnXmlFirst, $payment);
            $this->pagSeguroAbModel->proccessNotificatonResult($returnXmlSecond, $payment);

        } catch (\Exception $e) {
            foreach($transactions as $transaction) {
                $this->TransactionCancel($payment, $transaction);
            }
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
        return $this;
    }

    /**
     * Sends the request to create the transaction in PagSeguro
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $cardIndex
     * @return \SimpleXMLElement
     */
    protected function _createTransaction($payment, $cardIndex)
    {
        $order = $payment->getOrder();
        $params = $this->pagSeguroHelper->getCreditCardApiCallParams($order, $payment, $cardIndex);

        try {
            $returnXml = $this->pagSeguroHelper->callApi($params, $payment);
        } catch (WrongInstallmentsException $e) {
            $returnXml = $this->pagSeguroHelper->recalcInstallmentsAndResendOrder($params, $payment, $cardIndex);
        }

        return $returnXml;
    }

    /**
     * Payment refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }
        // recupera a informação adicional do PagSeguro
        $info           = $this->getInfoInstance();
        $transactionId = $payment->getAdditionalInformation('transaction_id');
        $transactionIdFirst = $payment->getAdditionalInformation('transaction_id_first');
        $transactionIdSecond = $payment->getAdditionalInformation('transaction_id_second');

        $token = $this->pagSeguroHelper->getToken();
        $email = $this->pagSeguroHelper->getMerchantEmail();

        if (isset($transactionIdFirst) && isset($transactionIdSecond)) {

            $transactionIdFirstObj  = $this->pagSeguroHelper->getTransaction($transactionIdFirst, $payment);
            $transactionIdSecondObj = $this->pagSeguroHelper->getTransaction($transactionIdSecond, $payment);

            $errorMsg = [];

            if (false !== $transactionIdFirstObj) {
                $params = [
                    'transactionCode'   => $transactionIdFirst,
                    'refundValue'       => number_format($payment->getAdditionalInformation('credit_card_amount_first'), 2, '.', '')
                ];
        
                $params['token'] = $token;
                $params['email'] = $email;
        
                try {
                    // call API - refund
                    $returnXml  = $this->pagSeguroHelper->callApi($params, $payment, 'transactions/refunds');
        
                    if ($returnXml === null) {
                        $errorMsg[] = 'Impossível gerar reembolso do 1º cartão. Aldo deu errado.';
                    }
                } catch (\Exception $e) {
                    $this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
                    $this->pagSeguroHelper->writeLog(__('Payment refunding error.'));
                    $errorMsg[] = __('Payment refunding error.');
                }

                $payment->setTransactionId($transactionIdFirst . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
                $transaction = $payment->addTransaction(
                    \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND,
                    null,
                    true
                );
            } else {
                $params = [
                    'transactionCode'   => $transactionIdFirst
                ];
        
                try {
                    // call API - cancels
                    $returnXml  = $this->callApi($params, $payment, 'transactions/cancels/');
        
                    if ($returnXml === null) {
                        $errorMsg[] = 'Impossível cancelar compra do 1º cartão. Aldo deu errado.';
                    }
                } catch (\Exception $e) {                    
                    $this->writeLog(__('Payment cancels error.'));
                    $errorMsg[] = __('Payment cancels error.');
                }

                $payment->setTransactionId($transactionIdFirst . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID);
                $transaction = $payment->addTransaction(
                    \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID,
                    null,
                    true
                );
            }
            if (false !== $transactionIdSecondObj) {
                $params = [
                    'transactionCode'   => $transactionIdSecond,
                    'refundValue'       => number_format($payment->getAdditionalInformation('credit_card_amount_second'), 2, '.', '')
                ];
        
                $params['token'] = $token;
                $params['email'] = $email;
        
                try {
                    // call API - refund
                    $returnXml  = $this->pagSeguroHelper->callApi($params, $payment, 'transactions/refunds');
        
                    if ($returnXml === null) {
                        $errorMsg[] = 'Impossível gerar reembolso do 2º cartão. Aldo deu errado.';
                    }
                } catch (\Exception $e) {
                    $this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
                    $this->pagSeguroHelper->writeLog(__('Payment refunding error.'));
                    $errorMsg[] = __('Payment refunding error.');
                }

                $payment->setTransactionId($transactionIdSecond . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
                $transaction = $payment->addTransaction(
                    \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND,
                    null,
                    true
                );
            } else {
                $params = [
                    'transactionCode'   => $transactionIdSecond
                ];
        
                try {
                    // call API - cancels
                    $returnXml  = $this->callApi($params, $payment, 'transactions/cancels/');
        
                    if ($returnXml === null) {
                        $errorMsg[] = 'Impossível cancelar compra do 2º cartão. Aldo deu errado.';
                    }
                } catch (\Exception $e) {
                    $this->writeLog(__('Payment cancels error.'));
                    $errorMsg[] = __('Payment cancels error.');
                }

                $payment->setTransactionId($transactionIdSecond . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID);
                $transaction = $payment->addTransaction(
                    \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID,
                    null,
                    true
                );
            }

            if (count($errorMsg) > 0) {
                $errorMsg = implode ( "\n", array_unique($errorMsg));
                throw new \Magento\Framework\Validator\Exception(__($errorMsg));
            }

        } else {
            $params = [
                'transactionCode'   => $transactionId,
                'refundValue'       => number_format($amount, 2, '.', '')
            ];
    
            $params['token'] = $token;
            $params['email'] = $email;
    
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

            $payment->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
            $transaction = $payment->addTransaction(
                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND,
                null,
                true
            );
        }

        return $this;
    }

    private function TransactionCancel($payment, $transactionId) {
        
        $token = $this->getToken();
        $email = $this->getMerchantEmail();

        $errorMsg = [];

        $params = [
            'transactionCode'   => $transactionId
        ];
        
        try {
            // call API - cancels
            $returnXml  = $this->pagSeguroHelper->callApi($params, $payment, 'transactions/cancels/');

            if ($returnXml === null) {
                $errorMsg[] = 'Impossível cancelar compra . Aldo deu errado.';
            }
        } catch (\Exception $e) {                    
            $this->writeLog(__('Payment cancels error.'));
            $errorMsg[] = __('Payment cancels error.');
        }

        if (count($errorMsg) > 0) {
            $errorMsg = implode ( "\n", array_unique($errorMsg));
            throw new \Magento\Framework\Validator\Exception(__($errorMsg));
        }

    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  object
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('sender_hash', $data['additional_data']['sender_hash'] ?? null)
            ->setAdditionalInformation(
                'credit_card_token_first',
                $data['additional_data']['credit_card_token_first'] ?? null
            )
            ->setAdditionalInformation(
                'credit_card_token_second',
                $data['additional_data']['credit_card_token_second'] ?? null
            )
            ->setAdditionalInformation('credit_card_owner_first', $data['additional_data']['first_cc_owner_name'] ?? null)
            ->setAdditionalInformation('credit_card_type_first', $data['additional_data']['first_cc_type'] ?? null)
            ->setAdditionalInformation('credit_card_last_four_first',substr($data['additional_data']['first_cc_number'] ?? null, -4))
            ->setAdditionalInformation('credit_card_amount_first',$data['additional_data']['first_cc_amount'] ?? null)
            ->setAdditionalInformation('credit_card_owner_second', $data['additional_data']['second_cc_owner_name'] ?? null)
            ->setAdditionalInformation('credit_card_type_second', $data['additional_data']['second_cc_type'] ?? null)
            ->setAdditionalInformation('credit_card_last_four_second',substr($data['additional_data']['second_cc_number'] ?? null, -4))
            ->setAdditionalInformation('credit_card_amount_second',$data['additional_data']['second_cc_amount'] ?? null)
            
            ->setCcType($data['additional_data']['cc_type'] ?? null)
            ->setCcLast4(substr($data['additional_data']['cc_number'] ?? null, -4))
            ->setCcExpYear($data['additional_data']['cc_exp_year'] ?? null)
            ->setCcExpMonth($data['additional_data']['cc_exp_month'] ?? null);

        // set cpf
        if ($this->pagSeguroHelper->isCpfVisible()) {
            $ccOwnerCpf = $data['additional_data']['first_cc_owner_cpf'] ?? null;
            $info->setAdditionalInformation($this->getCode() . '_cpf_first', $ccOwnerCpf);
            $ccOwnerCpf = $data['additional_data']['second_cc_owner_cpf'] ?? null;
            $info->setAdditionalInformation($this->getCode() . '_cpf_second', $ccOwnerCpf);
        }

        //DOB value
        if ($this->pagSeguroHelper->isDobVisible()) {
            $dobDay = isset($data['additional_data']['first_cc_owner_birthday_day']) ? trim(
                $data['additional_data']['first_cc_owner_birthday_day']
            ) : '01';
            $dobMonth = isset($data['additional_data']['first_cc_owner_birthday_month']) ? trim(
                $data['additional_data']['first_cc_owner_birthday_month']
            ) : '01';
            $dobYear = isset($data['additional_data']['first_cc_owner_birthday_year']) ? trim(
                $data['additional_data']['first_cc_owner_birthday_year']
            ) : '1970';
            $info->setAdditionalInformation(
                'credit_card_owner_birthdate_first',
                date(
                    'd/m/Y',
                    strtotime(
                        $dobMonth . '/' . $dobDay . '/' . $dobYear
                    )
                )
            );
            $dobDay = isset($data['additional_data']['second_cc_owner_birthday_day']) ? trim(
                $data['additional_data']['second_cc_owner_birthday_day']
            ) : '01';
            $dobMonth = isset($data['additional_data']['second_cc_owner_birthday_month']) ? trim(
                $data['additional_data']['second_cc_owner_birthday_month']
            ) : '01';
            $dobYear = isset($data['additional_data']['second_cc_owner_birthday_year']) ? trim(
                $data['additional_data']['second_cc_owner_birthday_year']
            ) : '1970';
            $info->setAdditionalInformation(
                'credit_card_owner_birthdate_second',
                date(
                    'd/m/Y',
                    strtotime(
                        $dobMonth . '/' . $dobDay . '/' . $dobYear
                    )
                )
            );
        }

        //Installments value
        if (isset($data['additional_data']['first_cc_installments'])) {
            $installments = explode('|', $data['additional_data']['first_cc_installments']);
            if (false !== $installments && count($installments)==2) {
                $info->setAdditionalInformation('installment_quantity_first', (int)$installments[0]);
                $info->setAdditionalInformation('installment_value_first', $installments[1]);
            }
        } else {
            if (isset($data['additional_data']['first_cc_amount'])) {
                $info->setAdditionalInformation('installment_quantity_first', 1);
                $info->setAdditionalInformation('installment_value_first', $data['additional_data']['first_cc_amount']);
            }
        }
        if (isset($data['additional_data']['second_cc_installments'])) {
            $installments = explode('|', $data['additional_data']['second_cc_installments']);
            if (false !== $installments && count($installments)==2) {
                $info->setAdditionalInformation('installment_quantity_second', (int)$installments[0]);
                $info->setAdditionalInformation('installment_value_second', $installments[1]);
            }
        } else {
            if (isset($data['additional_data']['second_cc_amount'])) {
                $info->setAdditionalInformation('installment_quantity_second', 1);
                $info->setAdditionalInformation('installment_value_second', $data['additional_data']['second_cc_amount']);
            }
        }

        //Sandbox Mode
        if ($this->pagSeguroHelper->isSandbox()) {
            $info->setAdditionalInformation('is_sandbox', '1');
        }

        return $this;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($this->adminSession->getUser()) {
            return false;
        }
        $isAvailable =  $this->getConfigData('active', $quote ? $quote->getStoreId() : null);
        if (empty($quote)) {
            return $isAvailable;
        }
        if ($this->getConfigData("group_restriction") == false) {
            return $isAvailable;
        }

        $currentGroupId = $quote->getCustomerGroupId();
        $customerGroups = explode(',', $this->getConfigData("customer_groups"));

        if ($isAvailable && in_array($currentGroupId, $customerGroups)) {
            return true;
        }

        return false;
    }

    /**
     * Availability for currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    /**
     * Validate payment method information object
     *
     * @return Payment Model
     */
    public function validate()
    {
        if(stristr($this->request->getUriString(),"/set-payment-information"))
            return $this;
        $info = $this->getInfoInstance();

        $senderHash = $info->getAdditionalInformation('sender_hash');
        $creditCardTokenFirst = $info->getAdditionalInformation('credit_card_token_first');
        $creditCardTokenSecond = $info->getAdditionalInformation('credit_card_token_second');

        if (!$creditCardTokenFirst || !$creditCardTokenSecond || !$senderHash) {
            $missingInfo = sprintf('Token do 1º cartão: %s', var_export($creditCardTokenFirst, true));
            $missingInfo .= sprintf('Token do 2º cartão: %s', var_export($creditCardTokenSecond, true));
            $missingInfo .= sprintf('/ Sender_hash: %s', var_export($senderHash, true));
            $this->pagSeguroHelper->writeLog(
                "Falha ao obter o token do cartao ou sender_hash.
                Ative o modo debug e observe o console de erros do seu navegador.
                Se esta for uma atualização via Ajax, ignore esta mensagem até a finalização do pedido.
                $missingInfo"
            );

            $errorMsg = __('Falha ao processar seu pagamento. Por favor, entre em contato com nossa equipe.');
            throw new \Magento\Framework\Exception\LocalizedException($errorMsg);
        }
        return $this;
    }

}
