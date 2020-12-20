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
    ["uiComponent", "uiRegistry", "jquery", "Magento_Ui/js/lib/view/utils/dom-observer", "mage/utils/wrapper"],
    function (uiComponent, uiRegistry,jQuery, domObserver, wrapper) {
    "use strict";
    return uiComponent.extend({
        initialize: function () {
            this._super();
            if(typeof this.cnf !== "undefined" && this.cnf.enable > 0){
                this.wrapIsDisplayed();
                this.openDiscount();
                this.listenToChanges();
            }
        },

        wrapIsDisplayed : function () {
            uiRegistry.async("checkout.sidebar.summary.totals.discount")(
                function (discountTotal) {
                    discountTotal.isDisplayed = wrapper.wrap(discountTotal.isDisplayed, function (originalMethod) {
                        return this.getPureValue();
                    });
                }
            );
        },

        openDiscount : function () {
            uiRegistry.async("checkout.sidebar.discount")(
                function (discountView) {
                    domObserver.get("#iosc-summary .payment-option._collapsible", function (elem) {
                        elem =  jQuery(elem);
                        setTimeout(function () {
                            elem.collapsible("forceActivate");}, 100);
                    });

                    domObserver.get("#iosc-summary .payment-option._collapsible .action-apply > span > span", function (elem) {
                        elem =  jQuery(elem);
                        elem.text(jQuery.mage.__("Apply"));
                    });

                    if (discountView.couponCode()) {
                        discountView.isApplied(true);
                    }
                }
            );
        },

        listenToChanges : function () {
            jQuery.ajaxSetup({
                complete: function (hxr, status) {
                    if (typeof this.url !== "undefined" && this.url.indexOf("/coupons") !== -1) {
                        uiRegistry.async("checkout.iosc.ajax")(
                            function (ajax) {
                                ajax.update();
                            }
                        );
                    }
                }
            });
        }
    });

});
