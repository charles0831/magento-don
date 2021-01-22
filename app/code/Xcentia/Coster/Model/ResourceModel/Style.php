<?php

namespace Xcentia\Coster\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Style extends AbstractDb
{

    /**
     * constructor
     *
     * @access public
     * @author Ultimate Module Creator
     */
    public function _construct()
    {
        $this->_init('xcentia_coster_style', 'entity_id');
    }
}
