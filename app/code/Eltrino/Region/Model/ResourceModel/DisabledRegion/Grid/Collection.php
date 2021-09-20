<?php

namespace Eltrino\Region\Model\ResourceModel\DisabledRegion\Grid;

use Eltrino\Region\Model\ResourceModel\DisabledRegion\Collection as DisabledRegionCollection;
use Magento\Framework\Api\Search\SearchResultInterface;

class Collection extends DisabledRegionCollection implements SearchResultInterface
{

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $resourceModel
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_init('Magento\Framework\View\Element\UiComponent\DataProvider\Document', 'Eltrino\Region\Model\ResourceModel\DisabledRegion');

    }
    protected function _initSelect()
    {
        $subQuery = $this->getConnection()->Select()->from(
            [
                'main_table' => $this->getMainTable()
            ],
            [
                'main_table.entity_id as entity_id',
                'main_table.country_id as country_id',
                'main_table.country_id as country_name',
                'main_table.region_id as region_id'
            ]
        )->group(
            [
                'main_table.country_id'
            ]
        );
        $this->getSelect()->from(['mt' => $subQuery]);

        return $this;
    }
    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\Search\DocumentInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null)
    {
        // TODO: Implement setItems() method.
    }

    /**
     * @return \Magento\Framework\Api\Search\AggregationInterface
     */
    public function getAggregations()
    {
        // TODO: Implement getAggregations() method.
    }

    /**
     * @param \Magento\Framework\Api\Search\AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        // TODO: Implement setAggregations() method.
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\Search\SearchCriteriaInterface
     */
    public function getSearchCriteria()
    {
        // TODO: Implement getSearchCriteria() method.
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        // TODO: Implement setSearchCriteria() method.
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        // TODO: Implement setTotalCount() method.
    }
}