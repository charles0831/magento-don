<?php

namespace Eltrino\Region\Controller\Adminhtml\Visibility;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResponseInterface;
use Eltrino\Region\Model\ResourceModel\DisabledRegion\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Backend\App\Action as Action;

class DeleteByMassaction extends Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();
            if (!empty($collection->getColumnValues('country_id'))) {
                $collection->getConnection()->delete(
                    $collection->getResource()->getMainTable(),
                    ['country_id IN (?)' => $collection->getColumnValues('country_id')]
                );
            } else {
                $this->logger->debug("Collection of country id is empty, country deletion query not performed.");
            }

            $this->messageManager->addSuccess(__('A total of %1 element(s) have been deleted.', $collectionSize));
        } catch (\Exception $e) {
            $this->_objectManager->get(LoggerInterface::class)->critical($e);
            $this->messageManager->addException($e, __('Can not delete selected countries.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
