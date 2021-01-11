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

    // This function updates the qty column in xcentia_coster/product table with the API.
//>*/30 * * * * pricebusters.furniture/coster/product/syncCosterInventory?key=gorhdufzk
    public function syncCosterInventory()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/inventory_sync.log');
        $this->logger->addWriter($writer);

        $importdate = date("d-m-Y H:i:s", strtotime("now"));
        $log = "sync started at: " . $importdate;
        $this->logger->info($log);

        try {
            $cInventory = $this->_sendRequest('GetInventoryList');
            $inventoryList = $cInventory[1]->InventoryList;  //AT   0->TX
            for ($i = 0; $i < count($inventoryList); $i++) {
                $cProduct = $inventoryList[$i];
                $model = $this->objMgr->create('\Xcentia\Coster\Model\Product');
                $iProduct =$model->load($cProduct->ProductNumber, 'sku');
                $qty = ($cProduct->QtyAvail > 0) ? $cProduct->QtyAvail : $cInventory[0]->InventoryList[$i]->QtyAvail;
                if ($iProduct->getSku() && $iProduct->qty != $qty && $iProduct->status < self::Init_Status) {
                    $iProduct->qty = $qty;
                    $iProduct->inventory_status = "1";
                    $iProduct->save();
                }
            }
            $importdate = date("d-m-Y H:i:s", strtotime("now"));
            $log = "Inventory sync finished at: " . $importdate . "\n";
            $this->logger->info($log);

        } catch (Exception $e) {
            $this->logger->err($e->getMessage());
        }
    }
}