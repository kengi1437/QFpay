<?php

namespace QFPay\PaymentGateway\Logger;

use \Monolog\Logger as MonologLogger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = MonologLogger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/PaymentDebug.log';
}
