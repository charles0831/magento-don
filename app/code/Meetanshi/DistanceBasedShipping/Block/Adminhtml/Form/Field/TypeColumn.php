<?php

namespace Meetanshi\DistanceBasedShipping\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class TypeColumn extends Select
{
    const TYPE_FIXED=0;
    const TYPE_PER_UNIT=1;
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function setInputId($value)
    {
        return $this->setId($value);
    }

    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    private function getSourceOptions()
    {
        return [
            ['label' => 'Fixed', 'value' => self::TYPE_FIXED],
            ['label' => 'Per Unit', 'value' => self::TYPE_PER_UNIT],
        ];
    }
}
