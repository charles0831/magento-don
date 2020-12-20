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
            this.element = null;
            this.paramName = '';
            this._super();
            uiRegistry.async("checkout.sidebar.subscribe.iosc-subscribe")(
                    function (subscribe) {
                        this.element = subscribe;
                        this.paramName = this.element.inputName;

                        if (this.cnf.autoselect === "1") {
                            subscribe.checked(true);
                        }
                        uiRegistry.async('checkout.iosc.ajax')(
                            function (ajax) {
                                ajax.addMethod(
                                    'params',
                                    'subscribe',
                                    this.paramsHandler.bind(this)
                                );
                            }.bind(this)
                        );
                    }.bind(this));
        },

        paramsHandler : function () {
            var response = {};
            response[this.paramName] = this.element.value();
            return response;
        }

    });

});
