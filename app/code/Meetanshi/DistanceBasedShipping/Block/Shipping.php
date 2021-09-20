<?php

namespace Meetanshi\DistanceBasedShipping\Block;

use Magento\Checkout\Block\Cart\Shipping as MagentoShipping;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Meetanshi\DistanceBasedShipping\Model\ResourceModel\DistanceBasedShipping\CollectionFactory as DBSCollectionFactory;
use Meetanshi\DistanceBasedShipping\Model\Carrier\DistanceBasedShipping;

class Shipping extends MagentoShipping
{

    private $dbsCollectionFactory;
    private $distanceBasedShipping;
    private $checkoutSession;

    public function __construct(
        Context $context,
        Session $customerSession,
        CheckoutSession $checkoutSession,
        CompositeConfigProvider $configProvider,
        DBSCollectionFactory $dbsCollectionFactory,
        DistanceBasedShipping $distanceBasedShipping,
        array $layoutProcessors = [],
        array $data = [],
        Json $serializer = null
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $configProvider, $layoutProcessors, $data, $serializer);
        $this->dbsCollectionFactory = $dbsCollectionFactory;
        $this->distanceBasedShipping = $distanceBasedShipping;
        $this->checkoutSession = $checkoutSession;
    }

    public function getIsEnable()
    {
        return $this->distanceBasedShipping->getConfigFlag('active');
    }
    public function getAddresses()
    {
        $dbsCollectionFactory=$this->dbsCollectionFactory->create();
        $data=$dbsCollectionFactory->addFieldToSelect(['id','street','city','state','country','zipcode'])->addFieldToFilter('status', 1)->load()->getData();
        $dataToReturn=[];
        $cnt=0;
        foreach ($data as $value) {
            $dataToReturn[$cnt]['id']=$value['id'];
            $dataToReturn[$cnt++]['warehouse_address']="{$value['street']},{$value['city']},{$value['state']},{$value['country']},{$value['zipcode']}";
        }
        return $dataToReturn;
    }

    public function getPickupFromId()
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            return $quote->getPickupFromId();
        } catch (NoSuchEntityException $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($e->getMessage());
        } catch (LocalizedException $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($e->getMessage());
        }
    }
}
