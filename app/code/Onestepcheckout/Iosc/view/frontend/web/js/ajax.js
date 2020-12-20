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
        "underscore",
        "Magento_Checkout/js/model/full-screen-loader",
        "mage/storage"
    ],
    function (uiComponent, uiRegistry, jQuery, _, loader, storage) {
        "use strict";

        return uiComponent.extend({

            initialize : function () {
                this._super();

                this.config = {
                    "url" : "checkout/onepage/update"
                };

                this.successMethods = {};
                this.validationMethods = {};
                this.errorMethods = {};
                this.paramsMethods = {};

                jQuery.ajaxPrefilter(
                    function ( options, localOptions, jqXHR ) {
                    this.passPayload(options, localOptions, jqXHR);
                    }.bind(this)
                );

            },

            passPayload: function (options, localOptions, jqXHR) {
                var whiteList  = ["checkout/onepage/update", "rest/"];
                var methods = ["post","delete","put"];
                var matches = _.filter(
                    whiteList,
                    function (seek) {
                        return options.url.indexOf(seek) !== -1;
                    }
                );

                if (matches.length > 0 && methods.indexOf(options.type.toLowerCase()) >= 0) {
                    var existingData = false;
                    var newData = this.collectParams(this.getMethods("params"));
                    if (typeof localOptions.data === "string") {
                        try {
                            existingData = JSON.parse(localOptions.data);
                        } catch (e) {
                        }
                    } else {
                        existingData = localOptions.data;
                    }
                    if (!existingData || _.isObject(existingData)) {
                        var mergedData = this.mergeJson(newData, existingData);
                        options.data = JSON.stringify(mergedData);
                    }
                }
            },

            /**
             * add method to method collection arrays
             *
             * @param type
             * @param method
             */
            addMethod: function (type, name, method) {
                if (type === "error") {
                    this.errorMethods[name] = method;
                    return;
                }

                if (type === "params") {
                    this.paramsMethods[name] = method;
                    return;
                }
                this.successMethods[name] = method;
                return;
            },

            /**
             * Return methods from methods collection arrays
             * @param type
             * @returns {Array}
             */
            getMethods: function (type) {

                if (type === "error") {
                    return this.errorMethods;
                }

                if (type === "params") {
                    return this.paramsMethods;
                }

                return this.successMethods;
            },

            /**
             * Ajax call
             */
            update: function () {

                loader.startLoader();

                storage.post(
                    this.config.url,
                    "{}",
                    false
                ).done(
                    function (response) {
                            var that = this;
                            if (!response.error) {
                                that.callMethods(response, that.getMethods("success"));
                            } else {
                                that.callMethods(response, that.getMethods("error"));
                            }
                        }.bind(this)
                ).fail(
                    function (response) {
                        if (
                            typeof response.responseJSON !== "undefined" &&
                            response.responseJSON['redirect'] !== "undefined" &&
                            response.responseJSON['redirect'] == window.checkoutConfig.cartUrl
                        )
                        {
                        } else if (
                                typeof response.responseJSON !== "undefined" &&
                                response.responseJSON['message'] !== "undefined" &&
                                response.responseJSON['message'])
                        {
                            uiRegistry.async("checkout.errors")(
                                function (errors) {
                                    errors.messageContainer.addErrorMessage({'message': response.responseJSON['message']});
                                }.bind(this)
                            );
                        } else {
                            uiRegistry.async("checkout.errors")(
                                function (errors) {
                                    errors.messageContainer.addErrorMessage({'message': "Update request has failed, probably did not received valid JSON response"});
                                }.bind(this)
                            );
                        }
                    }
                ).always(
                    function () {
                            loader.stopLoader();
                        }
                );

            },

            /**
             * Iterate over array of methods to collect ajax POST variables
             *
             * @param methods
             * @returns {object}
             */
        collectParams: function (methods) {

            var data = {};
            var result;

            _.each(methods, function (value, key) {
                result = value();
                if (result) {
                    if (typeof data[key] !== "undefined") {
                        data[key] = this.mergeJson(data[key], result);
                    } else {
                        data[key] = result;
                    }

                    if (typeof result.mergewith !== "undefined") {
                        if (typeof data[result.mergewith] === "undefined") {
                            data[result.mergewith] = {};
                        }
                        data[result.mergewith] =  this.mergeJson(data[result.mergewith], result);
                        delete data[result.mergewith].mergewith;
                        delete data[key];
                    }
                }
            }.bind(this));

            return data;
        },

            /**
             * Iterate over methods to pass ajax data back to registered methods (success, error)
             *
             * @param data
             */
            callMethods: function (data, methods) {
                _.each(methods, function (method) {
                    method(data);
                });
                return;
            },

            mergeJson: function (target, add) {
                for (var key in add) {
                    if (add.hasOwnProperty(key)) {
                        if (target[key] && this.isObject(target[key]) && this.isObject(add[key])) {
                        this.mergeJson(target[key], add[key]);
                        } else if (typeof target[key] === "undefined" || target[key] === null) {
                            target[key] = add[key];
                        }
                    }
                }
                return target;
            },

            isObject: function (obj) {
                if (typeof obj === "object") {
                    for (var key in obj) {
                        if (obj.hasOwnProperty(key)) {
                            return true;
                        }
                    }
                }
                return false;
            }

        });
    }
);
