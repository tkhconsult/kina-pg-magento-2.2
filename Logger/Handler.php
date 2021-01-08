<?php

namespace TkhConsult\KinaPg\Logger;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base {

    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/kinabank.log';
}
