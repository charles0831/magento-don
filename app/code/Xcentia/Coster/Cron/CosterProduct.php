<?php

namespace Xcentia\Coster\Cron;
use Exception;
use Magento\Framework\Filesystem\Io\File;

class CosterProduct extends CronBase
{
    //This function checks the products with the API, adds new products to the xcentia_coster/product table
//0 0 * * *  https://pricebusters.furniture/coster?name=syncCosterProducts&key=gorhdufzk
    public function syncCosterProducts()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/product_sync.log');
        $this->logger->addWriter($writer);

        $importdate = date("d-m-Y H:i:s", strtotime("now"));
        $log = "Sync started at: " . $importdate;
        $this->Log($log);

        $cn=0;
        try {
            $cProducts = $this->_sendRequest('GetProductList');

            $this->SecureArea();

            foreach ($cProducts as $cProduct) {
                $sku = $cProduct->ProductNumber;
                $lastSku = substr($sku, -2);
                $B1 = ($lastSku == "B1" || $lastSku == "B2" || $lastSku == "B3") ? true : false;
                $iProduct=$this->GetCosterProductBySku($sku);
                if (!$iProduct->getSku() && !$cProduct->IsDiscontinued && $cProduct->NumImages > 0 && !$B1) {
                    $iProduct->setSku($sku);
                    $iProduct->setContent(json_encode($cProduct));
                    $iProduct->setCreateProductStatus(1);
                    $iProduct->setCostStatus(0);
                    $iProduct->setInventoryStatus(0);
                    $iProduct->setPriceStatus(0);  //for exception price
                    $iProduct->setState(1);
                    $iProduct->setStatus(self::Init_Status);  //init
                    $iProduct->save();
                    $cn++;
                    $this->Log("New iProduct: " . $sku);
//                    return;
                } else if ($iProduct->getSku() && ($cProduct->IsDiscontinued || $cProduct->NumImages == 0 || $B1)) {
                    $this->Log("Delete: " . $sku);
                    $iProduct->delete();
                    try{
                        $this->productRepository->deleteById($sku);
                    }catch (Exception $e){
                        $this->Log("No Exist Product");
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->isBrowser)
                print_r($e->getMessage());
            $this->logger->err($e);
        }

        $importdate = date("d-m-Y H:i:s", strtotime("now"));
        $log = "sync finished cn:".$cn." at: " . $importdate . "\n";
        $this->Log($log);
    }

    //This function creates new products in magento
//10 * * * *     https://pricebusters.furniture/coster?name=createNewProduct?key=gorhdufzk
    public function createNewProduct()
    {
        $this->StartTime('product_sync');

        $iProducts = $this->objMgr->create('\Xcentia\Coster\Model\Product')
            ->getCollection()
            ->addFieldToFilter('create_product_status', '1')
            ->setPageSize(100)
            ->setCurPage(1);

        if ($iProducts->getSize() > 0) {
            $mediaAttribute = array('thumbnail', 'small_image', 'image');
            $file = new File();
            $path = $this->directoryList->getPath('media') . DS . 'imports' . DS;
            $file->mkdir($path);
            foreach ($iProducts as $iProduct) {
                $iProductObject = $this->objMgr->create('\Xcentia\Coster\Model\Product')->load($iProduct->getId());
                $sku = $iProductObject->getSku();
                $productId = $this->objMgr->create('\Magento\Catalog\Model\Product')->getIdBySku($sku);
                $prodInfo = json_decode($iProductObject->getContent());
                $lastSku = substr($sku, -2);
                $B1 = ($lastSku == "B1" || $lastSku == "B2" || $lastSku == "B3") ? true : false;
                if ($prodInfo->NumImages > 0 && false === $productId && $prodInfo->IsDiscontinued == false && !$B1) {
                    $images = array();
                    $num = 1;
                    while ($num <= $prodInfo->NumImages) {
                        $name = $prodInfo->ProductNumber . '-' . $num . '.jpg';
                        if (!file_exists($path . $name)) {
                            try{
                                $image_url='http://assets.coasteramer.com/productpictures/' . $prodInfo->ProductNumber . '/' . $num . 'x900.jpg';
                                $data = file_get_contents($image_url);
                                $file->write($path . $name, $data);
                            }
                            catch (Exception $e){
                                $this->Log("--No Image: ".$image_url);
                            }
                        }
                        $images[$num] = $name;
                        $num++;
                    }

                    $name = '';
                    $description = '';

                    if (!empty($prodInfo->CollectionCode)) {
                        $collect = $this->objMgr->create('\Xcentia\Coster\Model\Collections')->load($prodInfo->CollectionCode, 'collection_code')->getCollectionName();
                        $name .= ucwords(strtolower($collect));
                        $description .= 'Part of the ' . ucwords(strtolower($collect)) . ' by Coaster<br />';
                    }
                    if (!empty($prodInfo->StyleCode)) {
                        $style = $this->objMgr->create('\Xcentia\Coster\Model\Style')->load($prodInfo->StyleCode, 'style_code')->getStyleName();
                        $name .= ucwords(strtolower($style));
                    }

                    $name .= ucwords(strtolower(isset($prodInfo->MeasurementList[0]->PieceName)?$prodInfo->MeasurementList[0]->PieceName:' '));

                    $description .= 'Model Number: ' . $iProductObject->getSku() . '<br />';

                    if (isset($prodInfo->MeasurementList[0]->Width) && isset($prodInfo->MeasurementList[0]->Length) && isset($prodInfo->MeasurementList[0]->Height)) {
                        $description .= 'Dimensions: Width: ' . $prodInfo->MeasurementList[0]->Width . '  x  Depth: ' . $prodInfo->MeasurementList[0]->Length . '  x  Height: ' . $prodInfo->MeasurementList[0]->Height . '<br />';
                    }

                    $cat = $this->objMgr->create('\Xcentia\Coster\Model\Category')
                        ->getCollection()
                        ->addFieldToFilter('categorycode', $prodInfo->CategoryCode)
                        ->addFieldToFilter('subcategorycode', $prodInfo->SubcategoryCode)
                        ->addFieldToFilter('piececode', $prodInfo->PieceCode)
                        ->getFirstItem();
                    $categories = array(9, $cat->getCategoryId(), $cat->getSubcategoryId(), $cat->getPeiceId());

                    $price = $iProductObject->getCost() * self::MARGIN;

                    $product = $this->objMgr->create('\Magento\Catalog\Model\Product');

                    if ($iProductObject->getPrice() > 0) {
                        $product->setMultishipping_rate('0');
                    } else {
                        $product->setMultishipping_rate(self::SHIPPING_RATE);
                    }

                    $shippable = 1;
                    $store_id = 1;
                    $website_id =(int)$this->storeManager->getStore($store_id)->getWebsiteId();

                    $product->setStoreId($store_id)
                        ->setWebsiteIds(array($website_id))
                        ->setCreatedAt(strtotime('now'))
                        ->setCategoryIds($categories)
                        ->setAttributeSetId('4')
                        ->setPrice($price)
                        ->setCost($iProductObject->getPrice())
                        ->setShortDescription($prodInfo->Description)
                        ->setDescription($description)
                        ->setSku($iProductObject->getSku())
                        ->setName($prodInfo->Name)
                        ->setWeight($prodInfo->BoxWeight)
                        ->setBoxWidth($prodInfo->BoxSize->Width)
                        ->setBoxLength($prodInfo->BoxSize->Length)
                        ->setBoxHeight($prodInfo->BoxSize->Height)
                        ->setTaxClassId(2)
                        ->setStatus(0)
                        ->setIs_coaster(1)
                        ->setIsFeatured(0)
                        ->setTypeId('simple')
                        ->setMetaTitle($name)
                        ->setPkgQty($prodInfo->PackQty)
                        ->setShipable($shippable)
                        ->setMetaDescription(strip_tags($prodInfo->Description))
                        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);

                    $product->setStockData(array(
                            'manage_stock' => 1,
                            'is_in_stock' => (int)((int)$iProductObject->getQty() > 0),
                            'qty' => (int)$iProductObject->getQty()
                        )
                    );

                    $noImage=false;
                    foreach ($images as $n => $image) {
                        if (file_exists($path . $image)) {
                            if ($n == 1)
                                $product->addImageToMediaGallery($path . $image, $mediaAttribute, false, false);
                            else
                                $product->addImageToMediaGallery($path . $image, null, false, false);
                            $log = $path . $image;
                            $this->Log($log);
                        } else {
                            $noImage=true;
                            $this->Log("no image");
                        }
                    }
                    if ($noImage){
                        $log = 'no Image ' . $iProductObject->getSku() . ' will be recreate---------';
                        $this->Log($log);
                        continue;
                    }

                    //echo '<pre>'; print_r($product); die('OK');
                    try {
                        $iProductObject->setCreate_product_status(0)->save();
                        $iProductObject->setStatus(self::Created_Status)->save();
                        $log = 'new product ' . $iProductObject->getSku() . ' saved';
                        $this->Log($log);
                        $product->save();
                    } catch (Exception $e) {
                        $this->Log($e);
                        $log = "\n" . 'Could not save product ' . $iProductObject->getSku() . ' ID [' . $iProductObject->getEntity_id() . "]\n";
                        $this->Log($log);
                    }
                } else {
                    $iProductObject->setCreate_product_status(2)->save();
                    $iProductObject->setInventory_status(2)->save();
                    $iProductObject->setCost_status(2)->save();
                    $iProductObject->setPrice_status(2)->save();
                    $iProductObject->setStatus(self::Disable_Status)->save();
                    if ($prodInfo->IsDiscontinued != false) {
                        $log = 'not create ' . $iProductObject->getSku() . ' Discontinued';
                    } elseif ($prodInfo->NumImages <= 0) {
                        $log = 'not create ' . $iProductObject->getSku() . 'NumImages=0';
                    } elseif ($productId !== false) {
                        $log = 'not create ' . $iProductObject->getSku() . ' Product Id already exist';
                    } elseif ($B1) {
                        $log = 'not create ' . $iProductObject->getSku() . ' ' . $lastSku;
                    } else {
                        $log = 'not create ' . $iProductObject->getSku() . ' other reason ';
                    }
                    $this->Log($log);
                }
//                return;
            }
        }
        $this->EndTimeLog();
    }
}