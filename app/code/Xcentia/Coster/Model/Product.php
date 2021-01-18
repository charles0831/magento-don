<?php

namespace Xcentia\Coster\Model;

class Product extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY    = 'xcentia_coster_product';
    const CACHE_TAG = 'xcentia_coster_product';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'xcentia_coster_product';

    /**
     * Parameter name in event
     *
     * @var string
     */
    protected $_eventObject = 'product';

    protected function _construct()
    {
        $this->_init('Xcentia\Coster\Model\ResourceModel\Product');
//        $this->_init('xcentia_coster/product', 'entity_id');
    }

    public function getDefaultValues()
    {
        $values = array();
        $values['status'] = 1;
        return $values;
    }
//---new code?
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    //-----old code
    public function beforeSave()
    {
        if ($this->isObjectNew()) {
            $this->setCreatedAt(time());
        }
        $this->setUpdatedAt(time());
        return parent::beforeSave();
    }

    /**
     * save product relation
     *
     */
    public function afterSave()
    {
        return parent::afterSave();
    }
}

//not done

