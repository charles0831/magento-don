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

namespace Eltrino\Region\Block\Visibility\Edit;

use Eltrino\Region\Helper\DisabledRegionHelper;
use Eltrino\Region\Helper\CountryHelper;
use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Locale\ListsInterface;

/**
 * Class Form
 * @package Eltrino\Region\Block\Visibility\Edit
 */
class Form extends Generic
{
    /**
     * Locale model
     *
     * @var \Magento\Framework\Locale\ListsInterface
     */
    protected $localeLists;

    /**
     * @var DisabledRegionHelper
     */
    protected $disabledRegionHelper;

    /**
     * @var CountryHelper
     */
    protected $countryHelper;

    /**
     * @param ListsInterface $localeLists
     * @param DisabledRegionHelper $disabledRegionHelper
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param CountryCollection $countryCollection
     * @param array $data
     */
    public function __construct(
        ListsInterface $localeLists,
        DisabledRegionHelper $disabledRegionHelper,
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        CountryHelper $countryHelper,
        array $data = []
    ) {
        $this->localeLists = $localeLists;
        $this->disabledRegionHelper = $disabledRegionHelper;
        $this->countryHelper = $countryHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form fields
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $countryId = $this->_coreRegistry->registry('disabledRegionsCountryId');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setHtmlIdPrefix('visibility_');
        $baseFieldset = $form->addFieldset('base_fieldset', ['legend' => __('Region Configuration')]);

        if ($countryId) {
            $baseFieldset->addField(
                'country_id',
                'hidden',
                [
                    'name' => 'country_id',
                    'value' => $countryId
                ]
            );

            $baseFieldset->addField(
                'country_name',
                'text',
                [
                    'name' => 'country_name',
                    'label' => __('Country name'),
                    'title' => __('Country name'),
                    'readonly' => true,
                    'value' => $countryId
                ]
            );
        } else {
            $baseFieldset->addField(
                'country_id',
                'select',
                [
                    'name' => 'country_id',
                    'label' => __('Country'),
                    'title' => __('Country'),
                    'required' => true,
                    'values' => $this->countryHelper->getCountries(),
                    'class' => 'select',
                    'onchange' => 'countryChanged(this)'
                ]
            );
        }

        $commonSettings = $this->disabledRegionHelper->getCommonSettingsList($countryId);

        $baseFieldset->addField(
            'common_settings',
            'select',
            [
                'name' => 'common_settings',
                'label' => __('Common Settings'),
                'title' => __('Common Settings'),
                'values' => $commonSettings,
                'class' => 'select',
                'onchange' => 'commonSettingsChanged(this)',
                'disabled' => isset($commonSettings['label']) ? true : false
            ]
        );

        $baseFieldset->addField(
            'disabled_regions',
            'multiselect',
            [
                'name' => 'disabled_regions',
                'label' => __('Disabled Regions'),
                'title' => __('Disabled Regions'),
                'required' => true,
                'values' => $this->disabledRegionHelper->getRegions($countryId),
                'onchange' => 'regionsChanged(this)',
                'class' => 'multiselect',
                'style' => 'min-width: 175px',
                'note' => __('<span style="font-size:12px;">Items selected in this list will NOT be displayed to customers.</span>')

            ]
        );

        if ($countryId) {
            $data['country_name'] = (string)$this->localeLists->getCountryTranslation($countryId);
            $data['country_id'] = $countryId;
            $data['disabled_regions'] = $this->disabledRegionHelper->getDisabledRegionByCountry($countryId);
            $data['common_settings'] = $this->disabledRegionHelper->checkCommonSettings($commonSettings, $countryId);
            $form->setValues($data);
        }
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
