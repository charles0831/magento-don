<?php

namespace Xcentia\Coster\Cron;
use Exception;

class CronInventory extends CronBase
{

    // This function updates the qty column in xcentia_coster/product table with the API.
//>*/30 * * * * https://pricebusters.furniture/coster?name=syncCosterInventory&key=gorhdufzk
    public function syncCosterInventory()
    {
        $this->StartTime('inventory_sync');

        try {
            $cInventory = $this->_sendRequest('GetInventoryList');
            $inventoryList = $cInventory[1]->InventoryList;  //AT   0->TX
            for ($i = 0; $i < count($inventoryList); $i++) {
                $cProduct = $inventoryList[$i];
                $model = $this->objMgr->create('\Xcentia\Coster\Model\Product');
                $iProduct =$model->load($cProduct->ProductNumber, 'sku');
                $qty = ($cProduct->QtyAvail > 0) ? $cProduct->QtyAvail : $cInventory[0]->InventoryList[$i]->QtyAvail;
                if ($iProduct->getSku() && $iProduct->getQty() != $qty && $iProduct->getStatus() < self::Init_Status) {
                    $iProduct->setQty($qty);
                    $iProduct->setInventoryStatus(1);
                    $iProduct->save();
                }
            }

        } catch (Exception $e) {
            $this->Log($e);
        }

        $this->EndTimeLog();
    }

}