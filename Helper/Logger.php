<?php
/**
 * Created by PhpStorm.
 * User: conflicker
 * Date: 1/15/19
 * Time: 4:23 AM
 */

namespace RicardoMartins\PagSeguro\Helper;


class Logger extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_customLogger;
    public function __construct(
        \Psr\Log\LoggerInterface $customLogger
    ) {

        $this->_customLogger = $customLogger;

    }

    public function writeLog($obj) {
        if (is_string($obj)) {
            $this->_customLogger->debug($obj);
        } else {
            $this->_customLogger->debug(json_encode($obj));
        }
    }
}