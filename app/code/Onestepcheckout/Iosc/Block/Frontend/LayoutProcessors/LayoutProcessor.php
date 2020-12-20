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
namespace Onestepcheckout\Iosc\Block\Frontend\LayoutProcessors;

class LayoutProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{
    /**
     *
     * @var string
     */
    const SHIPPING_ADDRESS_PATH = 'components.checkout.children.steps.children.shipping-step.children.shippingAddress.children.shipping-address-fieldset.children';
    /**
     *
     * @var string
     */
    const SHIPPING_COMPONENT_PATH = 'components.checkout.children.iosc';
    /**
     *
     * @var string
     */
    const BILLING_ADDRESS_PATH = 'components.checkout.children.steps.children.billing-step.children.payment.children.payments-list.children';
    const BILLING_ADDRESS_SHARED_PATH = 'components.checkout.children.steps.children.billing-step.children.payment.children.afterMethods.children.billing-address-form';
    /**
     *
     * @var string
     */
    const BILLING_COMPONENT_PATH = 'components.checkout.children.steps.children.shipping-step.children.iosc-billing-fields';
    /**
     *
     * @var string
     */
    const DEFAULT_PATH = 'components.checkout.children.iosc.children';

    /**
     *
     * @var array
     */
    const CSS_CLASSES = [
        '0' => [
            'key' => 'iosc-quarter',
            'width' => 25
        ],
        '1' => [
            'key' => 'iosc-half',
            'width' => 50
        ],
        '2' => [
            'key' => 'iosc-third',
            'width' => 75
        ],
        '3' => [
            'key' => 'iosc-whole',
            'width' => 100
        ]
    ];

    /**
     *
     * @var array
     */
    public $paymentMethods = [];

    /**
     *
     * @param \Magento\Framework\View\Result\Page $page
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Onestepcheckout\Iosc\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\View\Result\Page $page,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Onestepcheckout\Iosc\Helper\Data $helper
    ) {

        $this->page = $page;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function process($jsLayout)
    {

        if (!$this->helper->isEnabled()) {
            return $jsLayout;
        }
        if ($this->page->getDefaultLayoutHandle() != 'checkout_index_index') {
            return $jsLayout;
        }
        $unset = [
            'general',
            'geoip',
            'cartskip'
        ];
        $special = [
            'billingfields',
            'shippingfields'
        ];
        $config = $this->getConfig('onestepcheckout_iosc', $unset, $special);

        if (empty($config)) {
            return $jsLayout;
        }
        $data = new \Dflydev\DotAccessData\Data($jsLayout);
        $specialConfig = [];

        foreach ($special as $node) {
            $specialConfig[$node] = $config[$node];
            unset($config[$node]);
        }
        foreach ($specialConfig as $field => $values) {
            $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            if ($field == 'billingfields') {
                $billingFieldTargets = [
                    'billingAddressshared'
                ];
                $fieldsAreShared = $this->scopeConfig
                    ->getValue('checkout/options/display_billing_address_on', $scopeStore);
                if ($fieldsAreShared) {
                    $fields = ['shared-form' => $data->get(self::BILLING_ADDRESS_SHARED_PATH)];
                } else {
                    $fields = $data->get(self::BILLING_ADDRESS_PATH);
                    if (!empty(current($this->getPaymentMethods()))) {
                        $billingFieldTargets[] = 'billingAddress'.current($this->getPaymentMethods());
                    }
                }
                $data->set(self::BILLING_COMPONENT_PATH . '.cnf', $values['cnf']);
                $lastfield = false;

                if ($fields) {
                    foreach ($fields as $k => $v) {
                        if (! empty($v['children']['form-fields']) && isset($v['dataScopePrefix'])) {
                            if (in_array($v['dataScopePrefix'], $billingFieldTargets)) {
                                $separateStreetFields = $this->scopeConfig
                                    ->getValue('onestepcheckout_iosc/billingfields/separatestreetfields', $scopeStore);

                                if ($separateStreetFields) {
                                    $groupName = 'street';
                                    if (!empty(
                                        $v['children']['form-fields']
                                            ['children'][$groupName]['children']
                                    )
                                    ) {
                                        $lines = $this
                                            ->explodeFieldLines(
                                                $v['children']['form-fields']['children'][$groupName],
                                                $groupName
                                            );
                                        unset($v['children']['form-fields']['children'][$groupName]);
                                        $v['children']['form-fields']['children'] =
                                        array_merge(
                                            $v['children']['form-fields']['children'],
                                            $lines
                                        );
                                    }
                                }
                                $fields[$k]['children']['form-fields']['children'] =
                                $this->manageAddressFields(
                                    $v['children']['form-fields']['children'],
                                    $values['cnf'],
                                    'billingfields'
                                );
                                if (!$lastfield) {

                                    $lastfield = $this
                                    ->getLastField($fields[$k]['children']['form-fields']['children']);
                                    $data->set(self::BILLING_COMPONENT_PATH . '.lastVisibleField', $lastfield);
                                }
                            } else {
                                unset($v['children']['form-fields']);
                            }
                        }
                    }
                    if ($fieldsAreShared) {
                        $data->set(self::BILLING_ADDRESS_SHARED_PATH, $fields['shared-form']);
                    } else {
                        $data->set(self::BILLING_ADDRESS_PATH, $fields);
                    }
                }
            }

            if ($field == 'shippingfields') {
                $fields = $data->get(self::SHIPPING_ADDRESS_PATH);
                $config[$field] = $values;
                $separateStreetFields = $this->scopeConfig
                ->getValue('onestepcheckout_iosc/shippingfields/separatestreetfields', $scopeStore);

                if ($separateStreetFields) {
                    $groupName = 'street';

                    if (!empty($fields[$groupName]['children'])) {
                        $lines = $this->explodeFieldLines($fields[$groupName], $groupName);
                        unset($fields[$groupName]);
                        $fields = array_merge($fields, $lines);
                    }
                }
                $fields = $this->manageAddressFields($fields, $values['cnf'], 'shippingfields');

                $config[$field]['lastVisibleField'] = $this->getLastField($fields);
                $data->set(self::SHIPPING_ADDRESS_PATH, $fields);
            }
        }

        $data->set(self::DEFAULT_PATH, $config);

        $jsLayout = $data->export();

        return $jsLayout;
    }

    /**
     * Explode children out of parent group
     * and preserve some attributes from parent
     *
     * @param array $group
     * @param string $name
     * @return array []
     */
    public function explodeFieldLines(array $group, $name)
    {
        $lines = [];

        foreach ($group['children'] as $rowNum => $streetRow) {
            $rowName = $name. '.' . $rowNum;
            $lines[$rowName] = $streetRow;
            if ($rowNum == 0) {
                $lines[$rowName]['label'] = __($group['label']);
            } else {
                $lines[$rowName]['label'] = __($group['label'] . ' ' . $rowNum);
            }
            if (isset($lines[$rowName]['dataScope'])) {
                $lines[$rowName]['dataScope'] = $group['dataScope'] . '.' . $rowNum;
            }
        }

        return $lines;
    }

    /**
     * Returns last rendered element dataScope
     *
     * @param array $fields
     * @return string
     */
    public function getLastField($fields)
    {
        $tmp = [];
        foreach ($fields as $k => $field) {
            if (empty($field['component'])) {
                continue;
            }
            if (!empty($field['children'])) {
                foreach ($field['children'] as $kk => $child) {
                    if (!empty($child['config']['sortOrder'])) {
                        $tmp[$child['config']['sortOrder']] = $k.'.'.$kk;
                    } elseif (!empty($child['sortOrder'])) {
                        $tmp[$child['sortOrder']] = $k.'.'.$kk;
                    }
                }
            } else {
                if (!empty($field['config']['sortOrder'])) {
                    $tmp[$field['config']['sortOrder']] = $k;
                } elseif (!empty($field['sortOrder'])) {
                    $tmp[$field['sortOrder']] = $k;
                }
            }
        }

        ksort($tmp);

        $lastIndex = end($tmp);
        if (empty($fields[$lastIndex]) && strstr($lastIndex, '.')) {
            $data = new \Dflydev\DotAccessData\Data($fields);
            $lastfield = $data->get(str_replace('.', '.children.', $lastIndex));
        } else {
            $lastfield = $fields[$lastIndex];
        }
        if (isset($lastfield['dataScope'])) {
            $lastfield = $lastfield['dataScope'];
        }

        return $lastfield;
    }

    /**
     * Method that manipulates config structure
     *
     * @param array $config
     * @return array
     */
    private function prepFieldConfig($config)
    {

        $fieldClasses = $this->getFieldClasses();

        foreach ($config as $key => $item) {
            if (isset($item['length'])) {
                $fieldClass = $fieldClasses[$item['length']];
                $config[$key]['fieldWidth'] = $fieldClass['width'];
                $config[$key]['css_class'] = (!empty($item['css_class'])) ? $fieldClass['key'] . ' ' . $item['css_class'] : $fieldClass['key'];
            }
            if (strstr($key, '.')) {
                $parts = explode('.', $key);
                $parent = current($parts);
                $slave = end($parts);

                if (!isset($config[$parent])) {
                    $config[$parent] = $config[$key];
                }

                if (isset($config[$parent])) {
                    $config[$parent]['children'][$slave] = $config[$key];
                }
            }
            if ($key == 'region') {
                $config['region_id'] = $config[$key];
                $config['region_id']['field_id'] = 'region_id';
            }
        }

        return $config;
    }

    /**
     * Compare default values against address object stored data
     *
     * @param array $config
     * @param string $type
     * @param object $quote
     * @return array
     */
    private function getDefaultFieldValue(array $config = [], $type = '', $quote = null, $field)
    {

        $addressObj = null;

        if ($type == 'shippingfields') {
            $addressObj = $quote->getShippingAddress();
        } else {
            $addressObj = $quote->getBillingAddress();
        }
        if (is_object($addressObj)) {
            $newValue = $addressObj->getData($config['field_id']);

            if ($newValue && $newValue !='-') {
                $config['default_value'] = $newValue;
            }
            if (isset($config['children'])) {
            }
        }

        if (!empty($config['css_class'])) {
            $existingClass = [];

            if (isset($field['config']['additionalClasses']) && $field['config']['additionalClasses'] !=1) {
                $existingClass[] = $field['config']['additionalClasses'];
            }

            if (isset($field['additionalClasses']) && $field['additionalClasses'] !=1) {
                $existingClass[] = $field['additionalClasses'];
            }

            if (!empty($existingClass)) {
                $config['css_class'] =
                (!empty($config['css_class'])) ? $config['css_class'] . ' ' . implode(' ', $existingClass) : implode(' ', $existingClass);
            }
        }

        return $config;
    }
    /**
     *
     * @param array $paramNames
     * @param array $field
     * @param array $fieldConfig
     * @return array
     */
    public function applyConfig(array $paramNames, $field, $fieldConfig)
    {

        if (!empty($fieldConfig)) {
            foreach ($paramNames as $param) {
                $field = $this->applyParam($param, $field, $fieldConfig);
            }
        }

        return $field;
    }

    /**
     *
     * @param unknown $param
     * @param unknown $field
     * @param unknown $fieldConfig
     * @return mixed|\Onestepcheckout\Iosc\Block\Frontend\LayoutProcessors\unknown
     */
    public function applyParam($param, $field, $fieldConfig)
    {
        $assignToParent = false;
        $removeIfNotNeeded = false;
        switch ($param) {
            case 'enabled':
                $map = 'visible';
                break;
            case 'default_value':
                $map = 'value';
                break;
            case 'required':
                $map = ['validation' => 'required-entry'];
                if (empty($fieldConfig['required'])) {
                    $removeIfNotNeeded = 'validation';
                }
                break;
            case 'field_sort':
                $map = 'sortOrder';
                $assignToParent = true;
                break;

            case 'css_class':
                $map = ['config' => 'additionalClasses'];
                break;
            default:
                $map = 'cnf';
                break;
        }

        $field = $this->mapParam($map, $param, $field, $fieldConfig, $assignToParent, $removeIfNotNeeded);

        return $field;
    }

    /**
     *
     * @param unknown $map
     * @param unknown $param
     * @param unknown $field
     * @param unknown $fieldConfig
     * @param string $assignToParent
     * @param string $removeIfNotNeeded
     * @return unknown
     */
    public function mapParam($map, $param, $field, $fieldConfig, $assignToParent = false, $removeIfNotNeeded = false)
    {
        if (is_array($map)) {
            foreach ($map as $kk => $vv) {
                $field[$kk][$vv] = (isset($fieldConfig[$param])) ? $fieldConfig[$param] : $fieldConfig;
            }
        } else {
            $field[$map] = (isset($fieldConfig[$param])) ? $fieldConfig[$param] : $fieldConfig;
        }

        if ($removeIfNotNeeded) {
            unset($field[$removeIfNotNeeded]);
        }

        return $field;
    }

    /**
     *
     * @param array $fields
     * @param array $cnf
     * @return array
     */
    public function manageAddressFields(
        array $fields = [],
        array $cnf = [],
        $type = 'shippingfields'
    ) {

        $params = ['cnf','enabled', 'default_value', 'required', 'field_sort','css_class'];

        /*
         * iterate over fields  =
         *  getDefaultFieldValue ->
         *      -> applyconfig
         *          -> applyparam
         *              -> mapparam
         */

        foreach ($fields as $fieldName => $field) {
            $isEnabled = true;

            $fieldConfig = (!empty($cnf[$fieldName])) ? $cnf[$fieldName] : [] ;

            if (!isset($fieldConfig['field_id'])) {
                continue;
            }

            $fieldConfig = $this->getDefaultFieldValue($fieldConfig, $type, $this->checkoutSession->getQuote(), $field);
            if ($fieldName == 'region_id') {
                $field['component'] = 'Onestepcheckout_Iosc/js/mixin/region';
                unset($fields['region']);
            }

            if ($fieldConfig) {
                $field = $this->applyConfig($params, $field, $fieldConfig);
                if (isset($field['visible']) && !$field['visible']) {
                    $isEnabled = false;
                }
            } else {
                $isEnabled = false;
            }

            if (!$isEnabled) {
                unset($fields[$fieldName]);
            } else {
                $fields[$fieldName] = $field;
            }
        }

        return $fields;
    }

    /**
     * return match for field width defining classes
     *
     * @return array
     */
    public function getFieldClasses()
    {
        return self::CSS_CLASSES;
    }

    /**
     * Get a list of payment method codes
     * @return array
     */
    public function getPaymentMethods()
    {

        if (!empty($this->paymentMethods)) {
            return $this->paymentMethods;
        }

        $this->paymentMethods = [];
        $quote = $this->checkoutSession->getQuote();
        foreach ($this->paymentMethodManagement->getList($quote->getId()) as $paymentMethod) {
            $this->paymentMethods[] = $paymentMethod->getCode();
        }
        return $this->paymentMethods;
    }

    /**
     * Get the array of default address and customer attributes
     *
     * @param string $config
     * @param array $unset
     * @param array $special
     * @return []
     */
    public function getConfig(
        $config,
        array $unset = [],
        array $special = []
    ) {

        $config = $this->scopeConfig->getValue($config, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (empty($config)) {
            return [];
        }

        foreach ($unset as $key) {
            unset($config[$key]);
        }

        foreach ($special as $key) {
            if (! empty($config[$key]) && is_array($config[$key])) {
                foreach ($config[$key] as $k => $v) {
                    if ($key == $k) {
                        $value = $config[$key][$key];
                        if (! empty($value) && is_string($value)) {
                            $config[$key] = array_merge($config[$key], $this->helper->getSerialized($value));
                            unset($config[$key][$key]);
                            $config[$key] = $this->prepFieldConfig($config[$key]);
                        }
                    } else {
                        $config[$key]['config'][$k] = $v;
                    }
                }
            }
        }

        foreach ($config as $key => $value) {
            $tmp = [];
            if (! empty($value['config'])) {
                $tmp['config'] = $value['config'];
                unset($value['config']);
            }
            $tmp['component'] = 'Onestepcheckout_Iosc/js/' . $key;
            $tmp['cnf'] = $value;
            $config[$key] = $tmp;
        }

        return $config;
    }
}
