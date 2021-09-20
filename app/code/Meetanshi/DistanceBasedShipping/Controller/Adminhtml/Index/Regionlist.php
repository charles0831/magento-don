<?php
namespace Meetanshi\DistanceBasedShipping\Controller\Adminhtml\Index;

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Directory\Model\RegionFactory;

class Regionlist extends Action
{
    protected $resultPageFactory;
    protected $countryFactory;
    protected $regionFactory;
    public function __construct(
        Context $context,
        CountryFactory $countryFactory,
        RegionFactory $regionFactory,
        PageFactory $resultPageFactory
    ) {
        $this->countryFactory = $countryFactory;
        $this->regionFactory=$regionFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }
    public function execute()
    {

        $countrycode = $this->getRequest()->getParam('country');
        $state = "<option value=''>".__(" -- Please Select -- ")."</option>";
        if ($countrycode != '') {
            $statearray =$this->countryFactory->create()->loadByCode(
                $countrycode
            )->getLoadedRegionCollection()->loadData()->toOptionArray();

            foreach ($statearray as $stateval) {
                if ($stateval['value']) {
                    $state .= "<option>" . $stateval['label'] . "</option>";
                }
            }
        }
        $result['htmlconent']=$state;
        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }
}
