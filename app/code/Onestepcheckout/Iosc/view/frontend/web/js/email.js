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
     "Magento_Customer/js/model/customer",
     "jquery",
     "Magento_Ui/js/lib/view/utils/dom-observer"

     ],
    function (
        uiComponent,
        uiRegistry,
        customer,
        jQuery,
        domObserver
    ) {
     
    "use strict";
    return uiComponent.extend({
        initialize : function () {
            this.customerEmail = null;
            this._super();

            if (!customer.isLoggedIn()) {
                uiRegistry.async("checkout.steps.shipping-step.shippingAddress.customer-email")(
                    function (customerEmail) {
                        domObserver.get(
                            "#checkoutSteps input[type='email']",
                            function (elem) {
                                this.customerEmail = customerEmail;
                                uiRegistry.async('checkout.iosc.ajax')(
                                    function (ajax) {
                                        ajax.addMethod(
                                            'params',
                                            'customerEmail',
                                            this.paramsHandler.bind(this)
                                        );
                                    }.bind(this)
                                );
                            }.bind(this)
                        );
                    }.bind(this)
                );
            }

        },

        paramsHandler : function () {
            var response = false;
            var isValid;

            isValid = this.customerEmail.validateEmail(false);

            if (isValid) {
                response = this.customerEmail.email();
            }

            return response;
        }

    });

    }
);
