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
        "jquery",
        "underscore",
        "uiComponent",
        "uiRegistry",
        "Magento_Ui/js/lib/view/utils/dom-observer",
        "Magento_Checkout/js/model/payment/additional-validators"
    ],
    function (jQuery, _, uiComponent, uiRegistry, domObserver, additionalValidators) {
        "use strict";

        return uiComponent.extend({
            initialize: function () {
                this._super();
                uiRegistry.async("checkout.sidebar.agreements")(
                    function (checkoutAgreementsView) {

                        domObserver.get("#iosc-summary div.checkout-agreements input", function (elem) {
                            jQuery.validator.addMethod("validate-checked", this.validateRequired, jQuery.mage.__("Please accept terms & conditions"));
                            if (jQuery.validator.validateSingleElement.length === 1) {
                                jQuery.validator.validateSingleElement = this.validateSingleElement.bind(jQuery.validator);
                            }
                            elem.className += " validate-checked";
                        }.bind(this));

                        additionalValidators.registerValidator(this.getValidator());
                        uiRegistry.async("checkout.iosc.ajax")(
                            function (ajax) {
                                ajax.addMethod("params", "terms", this.paramsHandler);
                        }.bind(this)
                        );

                    }.bind(this)
                );
            },

            validateRequired: function (value, element) {
                if (element.checked) {
                    return true;
                }
                return false;
            },

            getValidator: function () {
                return {
                    validate: this.validationHandler.bind(this)
                    };
            },

            validationHandler: function () {

                var isValid = true;
                var agreements = jQuery("#iosc-summary div.checkout-agreements input");

                agreements.each(function (index, element) {
                    if (!jQuery.validator.validateSingleElement(element, {errorElement: "div"})) {
                        isValid = false;
                    }
                });

            return isValid;
            },

            /**
             * Validate single element.
             *
             * @param {Element} element
             * @param {Object} config
             * @returns {*}
             */
            validateSingleElement: function (element, config) {
                var errors = {},
                    valid = true,
                    validateConfig = {
                        errorElement: "label",
                        ignore: ".ignore-validate"
                    },
                    form, validator, classes, elementValue;

                jQuery.extend(validateConfig, config);
                element = jQuery(element).not(validateConfig.ignore);

                if (!element.length) {
                    return true;
                }

                form = element.get(0).form;
                validator = form ? jQuery(form).data("validator") : null;

                if (validator) {
                    return validator.element(element.get(0));
                }

                classes = element.prop("class").split(" ");
                validator = element.parent().data("validator") ||
                    jQuery.mage.validation(validateConfig, element.parent()).validate;

                element.removeClass(validator.settings.errorClass);
                validator.toHide = validator.toShow;
                validator.hideErrors();
                validator.toShow = validator.toHide = jQuery([]);

                jQuery.each(classes, jQuery.proxy(function (i, className) {
                    elementValue = element.val();

                    if (element.is(":checkbox") || element.is(":radio")) {
                        elementValue = element.is(":checked") || null;
                    }

                    if (this.methods[className] && !this.methods[className](elementValue, element.get(0))) {
                        valid = false;
                        errors[element.get(0).name] = this.messages[className];
                        validator.invalid[element.get(0).name] = true;
                        validator.showErrors(errors);

                        return valid;
                    }
                }, this));

                return valid;
            },

            /**
             * Collect agreement ids from frontend form
             */
            paramsHandler: function () {

                var agreementForm = jQuery("#iosc-summary div[data-role=checkout-agreements] input");
                var agreementData = agreementForm.serializeArray();
                var agreementIds = [];
                var response = false;

                agreementData.forEach(function (item) {
                    agreementIds.push(item.value);
                });

                if (agreementIds.length > 0) {
                    response = {"extension_attributes" : {"agreement_ids" : agreementIds }, "mergewith" : "paymentMethod"};
                }

                return response;
            }

        });

    }
);
