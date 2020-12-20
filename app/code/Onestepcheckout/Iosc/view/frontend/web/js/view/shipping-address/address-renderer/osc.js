define([
    'Magento_Checkout/js/view/shipping-address/address-renderer/default',
    'uiRegistry'
], function (addressRenderer, uiRegistry) {
    'use strict';

    return addressRenderer.extend({
        defaults: {
            template: 'Onestepcheckout_Iosc/view/shipping-address/address-renderer/osc'
        }
    });
});
