<?php
namespace Raveinfosys\Rlcarriers\Model\System\Config\Source;
    
class Shipclass implements \Magento\Framework\Option\ArrayInterface
{
    public function __construct(
        \Raveinfosys\Rlcarriers\Helper\Data $dataHelper
    ) {
    
        $this->dataHelper = $dataHelper;
    }
    
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Please Select')],
            ['value' => 50, 'label' => __('Class 50')],
            ['value' => 55, 'label' => __('Class 55')],
            ['value' => 60, 'label' => __('Class 60')],
            ['value' => 65, 'label' => __('Class 65')],
            ['value' => 70, 'label' => __('Class 70')],
            ['value' => 77.5, 'label' => __('Class 77.5')],
            ['value' => 85, 'label' => __('Class 85')],
            ['value' => 92.5, 'label' => __('Class 92.5')],
            ['value' => 100, 'label' => __('Class 100')],
            ['value' => 110, 'label' => __('Class 110')],
            ['value' => 125, 'label' => __('Class 125')],
            ['value' => 150, 'label' => __('Class 150')],
            ['value' => 175, 'label' => __('Class 175')],
            ['value' => 200, 'label' => __('Class 200')],
            ['value' => 250, 'label' => __('Class 250')],
            ['value' => 300, 'label' => __('Class 300')],
            ['value' => 400, 'label' => __('Class 400')],
            ['value' => 500, 'label' => __('Class 500')],
        ];
    }
}
