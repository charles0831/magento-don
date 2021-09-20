<?php

namespace Meetanshi\DistanceBasedShipping\Block\Adminhtml\DistanceBasedShipping;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;

class AddRow extends Container
{
    protected $coreRegistry = null;

    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
        if ($this->getRequest()->getParam("que_id")) {
            $this->addButton(
                'delete',
                [
                    'label' => __('Delete'),
                    'onclick' => 'deleteConfirm(' . json_encode(__('Are you sure you want to do this?'))
                        . ','
                        . json_encode($this->getDeleteUrl())
                        . ')',
                    'class' => 'scalable delete',
                    'level' => -1
                ]
            );
        }
    }

    protected function _construct()
    {
        $this->_objectId = 'row_id';
        $this->_blockGroup = 'Meetanshi_DistanceBasedShipping';
        $this->_controller = 'adminhtml_DistanceBasedShipping';
        parent::_construct();
        $this->buttonList->update('save', 'label', __('Save'));
        $this->buttonList->remove('reset');
        $this->buttonList->update('back', 'label', __('Back to register or connect an account'));
    }

    public function getHeaderText()
    {
        return __('Add Warehouse');
    }

    protected function isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    public function getFormActionUrl()
    {
        if ($this->hasFormActionUrl()) {
            return $this->getData('form_action_url');
        }
        return $this->getUrl('*/*/save');
    }

    public function getDeleteUrl(array $args = [])
    {
        return $this->getUrl('*/*/delete/id/' . $this->getRequest()->getParam("id"));
    }
}
