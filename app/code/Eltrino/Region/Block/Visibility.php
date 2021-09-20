<?php
/**
 * Remove or Change Displayed States and Regions
 *
 * LICENSE
 *
 * This source file is subject to the Eltrino LLC EULA
 * that is bundled with this package in the file LICENSE_EULA.txt.
 * It is also available through the world-wide-web at this URL:
 * http://eltrino.com/license-eula.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 *
 * @category    Eltrino
 * @package     Eltrino_Region
 * @copyright   Copyright (c) 2015 Eltrino LLC. (http://eltrino.com)
 * @license     http://eltrino.com/license-eula.txt  Eltrino LLC EULA
 */
namespace Eltrino\Region\Block;

use Magento\Backend\Block\Widget\Context;
use Magento\User\Model\ResourceModel\User;
use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Visibility
 * @package Eltrino\Region\Block
 */
class Visibility extends Container
{
    /**
     * @var User
     */
    protected $_resourceModel;

    /**
     * @param Context $context
     * @param User $resourceModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        User $resourceModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_resourceModel = $resourceModel;
    }

    /**
     * Class constructor
     */
    protected function _construct()
    {
        $this->addData(
            [
                Container::PARAM_BUTTON_NEW => __('New Region Configuration'),
                Container::PARAM_HEADER_TEXT => __('Visibility'),
            ]
        );
        parent::_construct();
        $this->_addNewButton();
    }
}
