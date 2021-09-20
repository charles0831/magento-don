/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'mage/utils/wrapper',
    "jquery",
    "jquery/ui",
    "mage/translate",
    "mage/mage",
    "mage/validation",
    "jquery/jquery-storageapi"
], function (wrapper, $) {
    'use strict';

    return function (orderReview) {
        orderReview.prototype._updateOrderSubmit = function(shouldDisable, fn){
            $.cookieStorage.setConf({path:'/'});
            if ($("#regionNotAvaliableError").text() != "" || $.cookieStorage.get('regionNotAvaliable')) {
                if (!$.cookieStorage.get('regionNotAvaliable')) {
                    $.cookieStorage.set('regionNotAvaliable', true)
                }
                shouldDisable = true;
            }
            this._toggleButton(this.options.orderReviewSubmitSelector, shouldDisable);
            if ($.type(fn) === 'function') {
                fn.call(this);
            }
        };
    };
});