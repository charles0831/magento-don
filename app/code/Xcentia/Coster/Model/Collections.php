<?php

namespace Xcentia\Coster\Model;

class Collections extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY    = 'xcentia_coster_collections';
    const CACHE_TAG = 'xcentia_coster_collections';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'xcentia_coster_collections';

    /**
     * Parameter name in event
     *
     * @var string
     */
    protected $_eventObject = 'collections';

    /**
     * constructor
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function _construct()
    {
        $this->_init('Xcentia\Coster\Model\ResourceModel\Collections');
    }

    /**
     * before save collection
     *
     * @access protected
     * @return Xcentia_Coster_Model_Collections
     * @author Ultimate Module Creator
     */
    protected function _beforeSave()
    {
        if ($this->isObjectNew()) {
            $this->setCreatedAt(time());
        }
        $this->setUpdatedAt(time());
        return parent::beforeSave();
    }

    /**
     * save collection relation
     *
     * @access public
     * @return Xcentia_Coster_Model_Collections
     * @author Ultimate Module Creator
     */
    protected function _afterSave()
    {
        return parent::_afterSave();
    }

    /**
     * get default values
     *
     * @access public
     * @return array
     * @author Ultimate Module Creator
     */
    public function getDefaultValues()
    {
        $values = array();
        $values['status'] = 1;
        return $values;
    }
    
}
