<?php
namespace Meetanshi\DistanceBasedShipping\Model;

use Magento\Framework\Model\AbstractModel;

class DistanceBasedShipping extends AbstractModel
{


    protected function _construct()
    {
        $this->_init('Meetanshi\DistanceBasedShipping\Model\ResourceModel\DistanceBasedShipping');
    }
}
