<?php
/**
 * Created by PhpStorm.
 * User: amgPC
 * Date: 8/5/2020
 * Time: 5:59 AM
 */

namespace Xcentia\Coster\Controller\Product;

class Coster extends \Magento\Framework\App\Action\Action
{
//https://pricebusters.furniture/coster/product/coster?sku=CB60RT
    public function execute()
    {
        $sku=$this->getRequest()->getParam('sku');
//        $iProduct = Mage::getModel('xcentia_coster/product')->load($sku, 'sku');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('\Xcentia\Coster\Model\Product');
        $iProduct = $model->load($sku, 'sku');
        print_r($iProduct->getData());
    }
}