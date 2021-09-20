<?php

namespace Meetanshi\DistanceBasedShipping\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Meetanshi\DistanceBasedShipping\Model\DistanceBasedShippingFactory;

class Delete extends Action
{
    protected $pageFactory;
    protected $objectManager;
    protected $distanceBasedShippingFactory;

    public function __construct(
        Context $context,
        DistanceBasedShippingFactory $distanceBasedShippingFactory
    ) {
        $this->distanceBasedShippingFactory = $distanceBasedShippingFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam("id");
            $model = $this->distanceBasedShippingFactory->create();
            $model->load($id);
            if ($model->getId() == $id) {
                $model->delete();
                $this->messageManager->addSuccessMessage(__("Warehouse Deleted Successfully"));
            } else {
                $this->messageManager->addErrorMessage(__("Warehouse Not Found"));
            }

        } catch (\Exception $ex) {
            $this->messageManager->addErrorMessage(__($ex->getMessage()));
        } finally {
            return $this->_redirect('*/*/');
        }
    }
}
