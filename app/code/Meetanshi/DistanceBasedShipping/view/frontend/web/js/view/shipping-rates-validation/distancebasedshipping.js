/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-rates-validation-rules',
    '../../model/shipping-rates-validator/distancebasedshipping',
    '../../model/shipping-rates-validation-rules/distancebasedshipping'
], function (
    Component,
    defaultShippingRatesValidator,
    defaultShippingRatesValidationRules,
    mtDistShippingRatesValidator,
    mtDistShippingRatesValidationRules
) {
    'use strict';

    defaultShippingRatesValidator.registerValidator('distancebasedshipping', mtDistShippingRatesValidator);
    defaultShippingRatesValidationRules.registerRules('distancebasedshipping', mtDistShippingRatesValidationRules);

    return Component;
});
