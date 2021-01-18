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
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Xcentia\Coster\Model\ProductFactory $productFactory)
    {
        $this->_pageFactory = $pageFactory;
        $this->_productFactory=$productFactory;
        return parent::__construct($context);
    }
//https://pricebusters.org/coster?key=gorhdufzk&name=syncCosterProducts
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if($this->getRequest()->getParam('key') != "gorhdufzk") {
            echo "Wrong Key!";
            exit;
        }
        $name=$this->getRequest()->getParam('name');
        echo $name."..";
        if ($name=='syncCosterProducts'){
            //0 0 * * *  https://pricebusters.org/coster?name=syncCosterProducts&key=gorhdufzk
            $cron = $objectManager->create('\Xcentia\Coster\Cron\CosterProduct');
            echo nl2br("\nrunning...\n");
            $cron->isBrowser=true;
            $cron->syncCosterProducts();
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