<?php
namespace Meetanshi\DistanceBasedShipping\Model\ResourceModel\DistanceBasedShipping;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Meetanshi\DistanceBasedShipping\Model\DistanceBasedShipping as DistanceBasedShippingModel;
use Meetanshi\DistanceBasedShipping\Model\ResourceModel\DistanceBasedShipping as DistanceBasedShippingResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected function _construct()
    {
        $this->_init(
            DistanceBasedShippingModel::class,
            DistanceBasedShippingResourceModel::class
        );
    }
}
