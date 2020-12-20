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
        "uiRegistry",
        "Magento_Ui/js/lib/view/utils/dom-observer"
    ],
    function (jQuery, _, uiRegistry, domObserver) {
        "use strict";

        return {

            /**
             * set up a handler to report when some dom part is ready and rendered
             */
            domReadyHandler: function (name, context) {
                domObserver.get("div[name='" + name + "']", function (elem) {
                    context.domReady(true);
                });
            },

            /**
             * get field widths out of orm element uiComponents
             */
            getFieldWidths: function (prefix) {

                var fieldWidths = [];
                var fields = uiRegistry.filter("customScope=" + prefix);
                var streetFields = uiRegistry.filter("customScope=" + prefix + ".street");
                var customAttributes = uiRegistry.filter("customScope=" + prefix + ".custom_attributes");

                fields =  fields.concat(streetFields);
                fields =  fields.concat(customAttributes);

                _.each(fields, function (field) {
                    if (field.cnf) {
                        fieldWidths[field.parentScope + "." + field.index] = field.cnf.fieldWidth;
                    }
                });

                return fieldWidths;
            },

            /**
             * try to apply css classes to fields
             */
            applyCssClassnames: function (prefix, selector) {

                var widths = this.getFieldWidths(prefix);

                var row = 0;
                var fields = jQuery(selector).toArray();
                var prev = false;
                /* eslint-disable */
                _.each(fields, function (field) {
                    if (!field.name && field.className.indexOf("street")  > 0) {
                        field.name = prefix + ".street";
                    }
                    if(typeof field.name !== "undefined" && field.name.indexOf("street") > 0 && field.name.indexOf("street.street.") < 0) {
                        field.name= field.name.replace("street.", "street.street.");
                    }


                    if (widths[field.name]) {
                        var br = false;
                        var cssClass = "between";
                        var className = "iosc-between";

                        if (row === 0 ) {
                            br = true;
                            className = "iosc-start";
                        }

                        if (row >= 100 ) {
                            br = true;
                            row = 0;
                            className = "iosc-start";

                            if (prev) {
                                prev.className = prev.className.replace("iosc-between", "iosc-end");
                            }
                        }

                        if (br) {
                            className += " iosc-break";
                        }

                        row = row + widths[field.name];
                        prev = field;
                        field.className =  field.className + " " + className;
                        if (field.name === prefix + ".region_id") {
                            var region = document.getElementsByName(prefix + ".region");
                            if (region.length) {
                                region["0"].className += " " + className;
                            }
                        }
                    }

                });
                /* eslint-enable */
            }
        };
    }
);

