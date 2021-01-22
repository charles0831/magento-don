<?php

namespace Xcentia\Coster\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Product extends AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    )
    {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('xcentia_coster_product', 'entity_id');
    }
}

// done