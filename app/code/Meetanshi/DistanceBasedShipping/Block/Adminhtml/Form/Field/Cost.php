<?php
namespace Meetanshi\DistanceBasedShipping\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

class Cost extends AbstractFieldArray
{
    private $costRenderer;

    protected function _prepareToRender()
    {
        $this->addColumn('from', ['label' => __('From (Distance Unit)'), 'class' => '']);
        $this->addColumn('to', ['label' => __('To (Distance Unit)'), 'class' => '']);
        $this->addColumn('cost', ['label' => __('Cost'), 'class' => 'validate-number validate-zero-or-greater']);
        $this->addColumn('type', [
            'label' => __('Type'),
            'renderer' => $this->getTypeRenderer()
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];

        $type = $row->getType();
        if ($type !== null) {
            $options['option_' . $this->getTypeRenderer()->calcOptionHash($type)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    private function getTypeRenderer()
    {
        if (!$this->costRenderer) {
            $this->costRenderer = $this->getLayout()->createBlock(
                TypeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->costRenderer;
    }
}
