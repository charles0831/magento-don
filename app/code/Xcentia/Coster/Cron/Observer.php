<?php

namespace Xcentia\Coster\Cron;
use Exception;

class Observer
{
    const MARGIN = '1.92';
    const SHIPPING_RATE = '150';
    const Init_Status = '6';
    const Created_Status = '5';
    const Price_Status = '4';
    const Qty_Status = '3';
    const Enable_Status = '1';
    const Disable_Status = '0';

    protected $logger;
    protected $objMgr;

    public function __construct() {
        $this->logger = new \Zend\Log\Logger();
        $this->objMgr = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function test()
    {
//        print_r("OK  Test");
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test1.log');
        $this->logger->addWriter($writer);
        $this->logger->info('Your new test!');
    }
}