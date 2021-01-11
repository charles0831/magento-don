<?php
/**
 * Created by PhpStorm.
 * User: amgPC
 * Date: 8/5/2020
 * Time: 5:59 AM
 */

namespace Xcentia\Coster\Controller\Product;

class Test extends \Magento\Framework\App\Action\Action
{
    //https://pricebusters.org/coster/product/test
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('\Xcentia\Coster\Model\Observer');
        $iProduct = $model->execute();
        echo $iProduct;
        exit;
    }
}