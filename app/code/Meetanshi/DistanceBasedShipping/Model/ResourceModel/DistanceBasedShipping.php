<?php
namespace Meetanshi\DistanceBasedShipping\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DistanceBasedShipping extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mt_distance_shipping_warehouse', 'id');
    }
}
