<?php
namespace Meetanshi\PayshipRestriction\Model\ResourceModel\PayshipRestriction;
 
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Meetanshi\PayshipRestriction\Model\ResourceModel\PayshipRestriction
 */
class Collection extends AbstractCollection
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_init(
            'Meetanshi\PayshipRestriction\Model\PayshipRestriction',
            'Meetanshi\PayshipRestriction\Model\ResourceModel\PayshipRestriction'
        );
    }
}
