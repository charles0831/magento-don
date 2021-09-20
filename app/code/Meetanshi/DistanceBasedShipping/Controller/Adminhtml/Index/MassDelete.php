<?php

namespace Meetanshi\DistanceBasedShipping\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Meetanshi\DistanceBasedShipping\Model\DistanceBasedShippingFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action
{
    protected $pageFactory;
    protected $objectManager;
    protected $distanceBasedShippingFactory;
    protected $filter;
    public function __construct(
        Context $context,
        DistanceBasedShippingFactory $distanceBasedShippingFactory,
        Filter $filter
    ) {
        $this->filter=$filter;
        $this->distanceBasedShippingFactory = $distanceBasedShippingFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        try {
            $model = $this->distanceBasedShippingFactory->create();
            $collection = $this->filter->getCollection($model->getCollection());
            $collectionSize = $collection->getSize();

            foreach ($collection as $item) {
                $model->load($item->getId());
                $model->delete();
            }

            $this->messageManager->addSuccessMessage(__('A total of %1 warehouse(s) have been deleted.', $collectionSize));

        } catch (\Exception $ex) {
            $this->messageManager->addErrorMessage(__($ex->getMessage()));
        } finally {
            return $resultRedirect->setPath('*/*/');
        }
    }
}
