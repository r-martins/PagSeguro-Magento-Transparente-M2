<?php
namespace RicardoMartins\PagSeguro\Logger\Handler;
use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class Custom
 *
 * @see       http://bit.ly/pagseguromagento Official Website
 * @author    Ricardo Martins (and others) <pagseguro-transparente@ricardomartins.net.br>
 * @copyright 2018-2019 Ricardo Martins
 * @license   https://www.gnu.org/licenses/gpl-3.0.pt-br.html GNU GPL, version 3
 * @package   RicardoMartins\PagSeguro\Logger\Handler
 */
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