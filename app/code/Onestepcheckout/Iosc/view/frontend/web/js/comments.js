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
define([ "uiComponent", "uiRegistry" ], function (uiComponent, uiRegistry) {
    "use strict";
    return uiComponent.extend({
        initialize : function () {
            this.customerNote = null;
            this._super();
            uiRegistry.async("checkout.sidebar.comments.iosc-comment")(
                    function (customerNote) {
                        this.customerNote = customerNote;
                        uiRegistry.async('checkout.iosc.ajax')(
                                function (ajax) {
                                    ajax.addMethod(
                                        'params',
                                        'customerNote',
                                        this.paramsHandler.bind(this)
                                    );
                                }.bind(this));

                    }.bind(this));
        },

        paramsHandler : function () {
            var response = false;

            if (this.customerNote.value().length > 0) {
                response = {
                    "customerNote" : this.customerNote.value()
                };
            }

            return response;
        }

    });

});
