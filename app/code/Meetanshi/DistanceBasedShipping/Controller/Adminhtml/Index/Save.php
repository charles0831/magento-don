<?php

namespace Meetanshi\DistanceBasedShipping\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Meetanshi\DistanceBasedShipping\Model\Carrier\DistanceBasedShipping as DistanceBasedShippingCarrier;
use Meetanshi\DistanceBasedShipping\Model\DistanceBasedShippingFactory;

class Save extends Action
{
    var $distanceBasedShippingFactory;

    private $distanceBasedShippingCarrier;

    public function __construct(
        Context $context,
        DistanceBasedShippingFactory $distanceBasedShippingFactory,
        DistanceBasedShippingCarrier $distanceBasedShippingCarrier
    ) {
        parent::__construct($context);
        $this->distanceBasedShippingFactory = $distanceBasedShippingFactory;
        $this->distanceBasedShippingCarrier = $distanceBasedShippingCarrier;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            $this->_redirect('*/*/addrow');
            return;
        }
        try {
            $rowData = $this->distanceBasedShippingFactory->create();
            $rowData->setData($data);
            $sourceAddress = [
                'street' => $data['street'],
                'country_id' => $data['country'],
                'city' => $data['city'],
                'postcode' => $data['zipcode'],
                'region' => $data['state']
            ];
            $point1 = $this->distanceBasedShippingCarrier->getLatLngFromAddress($sourceAddress);
            try {
                if ($point1 instanceof \Exception) {
                    throw $point1;
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__("Invalid Google API Key.Please provide valid api key from configuration"));
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($e->getMessage());
                return $this->_redirect('*/*/');
            }
            $rowData->setLongitude($point1['lng']);
            $rowData->setLatitude($point1['lat']);

            if (isset($data['id'])) {
                $rowData->setEntityId($data['id']);
            }
            $rowData->save();
            $this->messageManager->addSuccessMessage(__('Warehouse has been successfully saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
        $this->_redirect('*/*/');
    }
}
