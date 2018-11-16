<?php


namespace RicardoMartins\PagSeguro\Logger\Handler;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

class Custom extends Base
{
    /**
    * @var string
    */
    protected $fileName = '/var/log/pagseguro.log';
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

}