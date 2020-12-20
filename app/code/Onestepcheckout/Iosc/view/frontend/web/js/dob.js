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
define(
    [
         "uiComponent",
         "uiRegistry",
         "jquery",
        "Magento_Ui/js/lib/view/utils/dom-observer",
        "Magento_Checkout/js/model/quote"
     ],
    function (uiComponent, uiRegistry, jQuery, domObserver, quote) {
    "use strict";
    return uiComponent.extend({

        initialize : function () {
            this._super();
            uiRegistry.async("checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.dob")(
                function (dobElement) {
                    this.cnf = dobElement.cnf;
                    this.setValue(dobElement.value());
                    dobElement.value.subscribe(this.setValue, dobElement, 'change');
                }.bind(this)
            );
        },

        setValue: function (value) {

            var shippingAddress =  quote.shippingAddress();

            if (!shippingAddress) {
                return;
            }

            if (typeof shippingAddress.extension_attributes === "undefined") {
                shippingAddress.extension_attributes = {};
            }

            if (typeof value !== "undefined") {
                shippingAddress.extension_attributes[this.cnf.field_id] = value;
            } else {
                shippingAddress.extension_attributes[this.cnf.field_id] = "";
            }

        }

    });

    }
);
