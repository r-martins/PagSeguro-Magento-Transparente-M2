<?php

namespace RicardoMartins\PagSeguro\Model\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class that represents a wrong installments exception. That exception
 * occurs when the value of the parcels sent to PagSeguro are inconsistent
 * with the order total and the interest value of the transaction
 */
class WrongInstallmentsException extends LocalizedException
{
}
