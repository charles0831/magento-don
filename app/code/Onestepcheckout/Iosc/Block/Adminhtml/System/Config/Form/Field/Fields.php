<?php
/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
namespace Onestepcheckout\Iosc\Block\Adminhtml\System\Config\Form\Field;

class Fields extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{

    /** @var \Magento\Framework\View\Element\BlockFactory */
    public $blockFactory;

    /**
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\View\Element\BlockFactory $blockFactory
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Onestepcheckout\Iosc\Helper\Data $helper,
        array $data = []
    ) {
        $this->blockFactory = $blockFactory;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Prepare to render
     *
     * @return void
     */
    public function _prepareToRender()
    {
        $elementBlockClass = 'Onestepcheckout\Iosc\Block\Adminhtml\System\Config\Form\Field\FieldArray\Renderer';

        $this->addColumn('field_sort', [
            'label' => __('Sort order')
        ]);

        $this->addColumn('field_id', [
            'label' => __('Field Id'),
            'style' => 'width: 150px'
        ]);

        $this->addColumn('enabled', [
            'label' => __('Enabled'),
            'renderer' => $this->blockFactory->createBlock($elementBlockClass)
                ->setElementType('checkbox')
        ]);

        $this->addColumn('required', [
            'label' => __('Required'),
            'renderer' => $this->blockFactory->createBlock($elementBlockClass)
                ->setElementType('checkbox')
        ]);

        $this->addColumn('length', [
            'label' => __('Length'),
            'renderer' => $this->blockFactory->createBlock($elementBlockClass)
                ->setElementType('select')
                ->setElementOptions([
                __('25%'),
                __('50%'),
                __('75%'),
                __('100%')
                ]),
            'style' => 'width:150px'
        ]);

        $this->addColumn('css_class', [
            'label' => __('Css class'),
            'style' => 'width:150px'
        ]);

        $this->addColumn('default_value', [
            'label' => __('Default value'),
            'style' => 'width:100px'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Fields');
    }

    /**
     *
     * {@inheritDoc}
     *
     * @see \Magento\Config\Block\System\Config\Form\Field::render()
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if (!empty($element->getValue()) && !is_array($element->getValue())) {
            $element->setValue($this->helper->unserialize($element->getValue()));
        }

        $isCheckboxRequired = $this->_isInheritCheckboxRequired($element);
        if ($element->getInherit() == 1 && $isCheckboxRequired) {
            $element->setDisabled(true);
        }
        $html = '';
        $html .= $this->_renderValue($element);

        if ($isCheckboxRequired) {
            $html .= $this->_renderInheritCheckbox($element);
        }
        $html .= $this->_renderHint($element);

        return $this->_decorateRowHtml($element, $html);
    }

    /**
     *
     * {@inheritDoc}
     *
     * @see \Magento\Config\Block\System\Config\Form\Field::_renderValue()
     */
    public function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($element->getTooltip()) {
            $html = '<td class="value with-tooltip">';
            $html .= $this->_getElementHtml($element);
            $html .= '<div class="tooltip"><span class="help"><span></span></span>';
            $html .= '<div class="tooltip-content">' . $element->getTooltip() . '</div></div>';
        } else {
            $html = '<td class="value" colspan="2">';
            $html .= $this->_getElementHtml($element);
        }
        if ($element->getComment()) {
            $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
        }
        $html .= '</td>';
        return $html;
    }
}
