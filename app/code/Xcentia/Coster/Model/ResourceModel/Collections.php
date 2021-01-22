<?php
namespace Xcentia\Coster\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Collections extends AbstractDb
{

    public function _construct()
    {
        $this->_init('xcentia_coster_collections', 'entity_id');
    }
}
