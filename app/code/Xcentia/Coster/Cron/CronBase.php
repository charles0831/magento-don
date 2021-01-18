<?php

namespace Xcentia\Coster\Cron;

class CronBase
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
    protected $productRepository;
    protected $registry;
    protected $startTime;

    public $isBrowser=false;

    public function __construct(\Magento\Catalog\Model\ProductRepository $productRepository,
                                \Magento\Framework\Registry $registry) {
        $this->productRepository = $productRepository;
        $this->registry = $registry;

        $this->logger = new \Zend\Log\Logger();
        $this->objMgr = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function Log($log){
        if ($this->isBrowser)
            echo $log."<br/>";
        $this->logger->info($log);
    }

    public function StartTime($file){
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/'.$file.'.log');
        $this->logger->addWriter($writer);

        $this->startTime = microtime(true);
        $importdate = date("d-m-Y H:i:s", strtotime("now"));
        $log = $file."started at: " . $importdate;
        $this->Log($log);
    }

    public function SecureArea(){
//        $registry=$this->objMgr->get('Magento\Framework\Registry');
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);
//        $registry->unregister('isSecureArea');
//        $registry->register('isSecureArea', true);
    }

    public function GetCosterProductBySku($sku){
        $model = $this->objMgr->create('\Xcentia\Coster\Model\Product');
        $iProduct =$model->load($sku, 'sku');
        return $iProduct;
    }

    public function GetCatalogProductBySku($sku){
        $model = $this->objMgr->create('\Magento\Catalog\Model\Product');
        $iProduct =$model->load($sku, 'sku');
//        $iProduct =$model->loadByAttribute($sku, 'sku');
        return $iProduct;
    }

    function _sendRequest($endpoint)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api.coasteramer.com/api/product/" . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array("keycode: E122443B8549416BAA0629ED0C"),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        } else {
            return json_decode($response);
        }
    }
}