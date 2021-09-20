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
namespace Eltrino\Region\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface as AttributeMetadata;
use Magento\Framework\View\Element\UiComponentInterface;

/**
 * Class Columns
 * @package Eltrino\Region\Ui\Component\Listing
 */
class Columns extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * @var OptionsRepository
     */
    protected $optionsRepository;

    /**
     * @param ContextInterface $context
     * @param OptionsRepository $optionsRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        OptionsRepository $optionsRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->optionsRepository = $optionsRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        foreach ($this->optionsRepository->getList() as $options) {
            if (isset($this->components[$options['component_name']])) {
                $this->addOptions($this->components[$options['component_name']], $options['options']);
            }
        }
        parent::prepare();
    }

    /**
     * Update actions column sort order
     *
     * @return void
     */
    protected function updateActionColumnSortOrder()
    {
        if (isset($this->components['actions'])) {
            $component = $this->components['actions'];
            $component->setData(
                'config',
                array_merge($component->getData('config'), ['sortOrder' => ++$this->columnSortOrder])
            );
        }
    }

    /**
     * Add options to component
     *
     * @param UiComponentInterface $component
     * @param array $options
     */
    public function addOptions(UiComponentInterface $component, array $options)
    {
        $config = $component->getData('config');
        if (!isset($config[AttributeMetadata::OPTIONS])) {
            $component->setData(
                'config',
                array_merge($config, [AttributeMetadata::OPTIONS => $options])
            );
        }
    }

}
