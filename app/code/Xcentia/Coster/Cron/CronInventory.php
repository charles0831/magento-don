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

    // This function updates the product qty in magento with the xcentia_coster/product table.
    public function updateInventory()
    {
        $this->StartTime('inventory_sync');
        $this->SecureArea();
        $iProducts = $this->objMgr->create('\Xcentia\Coster\Model\Product')
            ->getCollection()
            ->addFieldToFilter('inventory_status', '1')
            ->setPageSize(500)
            ->setCurPage(1);

        if ($iProducts->getSize() > 0) {
            foreach ($iProducts as $iProduct) {
                $iProductObject = $this->objMgr->create('\Xcentia\Coster\Model\Product')->load($iProduct->getId());
                $sku = $iProductObject->getSku();
                $qty = $iProductObject->getQty();
                $iProductObject->setInventoryStatus(3)->save();
                try{
                    $updateProduct = $this->productRepository->get($sku);
                } catch (Exception $e) {
                    $iCStatus = $iProductObject->getCreateProductStatus();
                    $log = 'No Product will remove ' . $sku . ' Create_product_status:' . $iCStatus;
                    $this->Log($log);
                    $iProductObject->delete();
                    $this->Log($e);
                    continue;
                }
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($updateProduct->getId());
                if ($stockItem->getId() > 0 and $stockItem->getManageStock()) {
                    $log = 'updating ' . $sku . ' '.$stockItem->getQty(). ' -> ' . $qty;
                    $stockItem->setQty((int)$qty);
                    $stockItem->setIsInStock((int)((int)$qty > 0));
                    try {
                        if ($iProductObject->status == self::Created_Status) { //init
                            $iProductObject->setStatus(self::Qty_Status)->save(); //Inventory Updates Init.
                        } else if ($iProductObject->status != self::Qty_Status) {
                            if ($updateProduct->getStatus() < 2) {
                                $updateProduct->setStatus((int)((int)$qty > 0));
                            }
                            $iProductObject->setStatus((int)((int)$qty > 0))->save();
                        }
                        $updateProduct->save();
                        $stockItem->save();
                        Mage::log($log, null, 'inventory_sync.log', true);
                    } catch (Exception $e) {
                        $log = "\n" . 'Exception [' . $sku . '] - [' . $qty . "]\n";
                        Mage::log($log, null, 'inventory_sync.log', true);
                        Mage::logException($e);
                    }
                } else {
                    $log = "\n" . 'No Stock ' . $sku . '-' . $qty;
                    Mage::log($log, null, 'inventory_sync.log', true);
                }
            }
        }
        Mage::unregister('isSecureArea');

        $this->EndTimeLog();
    }

}