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
        "Magento_Checkout/js/view/sidebar",
        "uiRegistry",
        "Magento_Checkout/js/model/step-navigator",
        "ko",
        "underscore",
        "mage/utils/wrapper",
        "Magento_Ui/js/lib/view/utils/dom-observer",
        "Magento_Ui/js/lib/view/utils/async",
        "jquery"
    ],
    function (sidebar, uiRegistry, stepNavigator, ko, _, wrapper, domObserver, $, jQuery) {
        "use strict";
        stepNavigator.isProcessed = wrapper.wrap(stepNavigator.isProcessed, function (origin, code) {
            if (typeof code !== "undefined" && code == "shipping") {
                return true;
            } else {
                return origin(code);
            }
        });

        return sidebar.extend({
            regions: ko.observableArray(),
            isVisible: ko.observable(false),
            initialize: function () {
                this._super();

                $.async({
                    component: "checkout.sidebar",
                    ctx: 'div#opc-sidebar',
                    selector: 'div.iosc-place-order-container'
                }, function(node){
                    this.revealIfFound(node);
                }.bind(this));

                $.async({
                    component: this,
                    ctx: 'div#iosc-summary',
                    selector: 'div.opc-block-summary > span.title'
                }, function(node){
                    this.addTitleNumber(node);
                }.bind(this));

                $.async({
                    component: this,
                    ctx: 'div#iosc-summary',
                    selector: 'div.block.items-in-cart'
                }, function(node){
                    this.moveCartNode(node);
                }.bind(this));

            },

            revealIfFound: function(node) {
                uiRegistry.async("checkout.sidebar")(
                    function (sidebar) {
                        this.regions(sidebar.elems());
                        sidebar.elems.subscribe(function (elem) {
                            this.regions(elem);
                        }.bind(this), true, "change");
                        this.isVisible(true);
                    }.bind(this)
                );
            },

            addTitleNumber: function (node) {
                jQuery(node)
                .addClass('step-title')
                .prepend(jQuery("<span class='title-number'><span>&#10003;</span></span>").get(0));
                jQuery("div#iosc-summary")
                .prepend(node);
            },

            moveCartNode: function (node) {
                jQuery("div#iosc-summary div.block.items-in-cart > div.title").hide();
                jQuery("div#iosc-summary > div.opc-block-summary")
                .prepend(node);
            }

        });
    }
);
