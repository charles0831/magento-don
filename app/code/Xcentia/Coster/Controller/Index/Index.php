<?php
/**
 * Created by PhpStorm.
 * User: amgPC
 * Date: 8/5/2020
 * Time: 5:59 AM
 */

namespace Xcentia\Coster\Controller\Index;


class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_productFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory)
    {
        $this->_pageFactory = $pageFactory;
//        $this->_productFactory=$productFactory;
        return parent::__construct($context);
    }
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if($this->getRequest()->getParam('key') != "gorhdufzk") {
            echo "Wrong Key!";
            exit;
        }
        $name=$this->getRequest()->getParam('name');
        echo $name."..";
        $cron = $objectManager->create('\Xcentia\Coster\Cron\CosterProduct');
        $cronInventory = $objectManager->create('\Xcentia\Coster\Cron\CronInventory');
        echo nl2br("\nrunning...\n");
        $cron->isBrowser=true;
        $cronInventory->isBrowser=true;
        if ($name=='syncCosterProducts'){
            //0 0 * * *  https://pricebusters.org/coster?name=syncCosterProducts&key=gorhdufzk
            $cron->syncCosterProducts();
        }
        else if($name=='createNewProduct'){
            //10 * * * * https://pricebusters.furniture/coster?name=createNewProduct&key=gorhdufzk
            $cron->createNewProduct();
        }
        else if($name=='syncCosterInventory'){
            //>*/30 * * * * https://pricebusters.furniture/coster?name=syncCosterInventory&key=gorhdufzk
            $cronInventory->syncCosterInventory();
        }
        else if($name=='updateInventory'){
            //>*/30 * * * * https://pricebusters.furniture/coster?name=updateInventory&key=gorhdufzk
            $cronInventory->updateInventory();
        }
        else{
            //https://pricebusters.furniture/coster?key=gorhdufzk&sku=CB60RT
            $sku=$this->getRequest()->getParam('sku');
            $objMgr = \Magento\Framework\App\ObjectManager::getInstance();
            $productRepository= $objMgr->create('\Magento\Catalog\Model\ProductRepository');
//            $product = $this->_productFactory->create();
            $product=$productRepository->get($sku);
            print_r($product->debug());
            echo "----------------<br/>";
//            $product->load($product->getIdBySku($sku));
//            print_r($product->debug());
        }

        echo "<br/> Done!";

        exit;

//        $post = $this->_productFactory->create();
//        $collection = $post->getCollection();
//        foreach($collection as $item){
//            echo "<pre>";
//            print_r($item->getData());
//            echo "</pre>";
//        }
//        return $this->_pageFactory->create();
    }
}