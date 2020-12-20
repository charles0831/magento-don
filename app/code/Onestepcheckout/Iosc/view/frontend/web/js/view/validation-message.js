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
define([ "uiComponent", "ko", "Magento_Checkout/js/model/quote" ], function (uiComponent, ko, quote) {
    "use strict";
    ko.bindingHandlers.scrollTo = {
        update: function (element, valueAccessor) {
            var value = ko.utils.unwrapObservable(valueAccessor());
            if (value) {
                element.scrollIntoViewIfNeeded();
                ko.contextFor(element).$data.focused(false);
            }
        }
    };

    /**
     * scrollIntoViewIfNeeded polyfill
     */
    if (!Element.prototype.scrollIntoViewIfNeeded) {
        Element.prototype.scrollIntoViewIfNeeded = function (centerIfNeeded) {
            "use strict";

            function makeRange(start, length)
            {
                return {"start": start, "length": length, "end": start + length};
            }

            function coverRange(inner, outer)
            {
                if (false === centerIfNeeded ||
                    (outer.start < inner.end && inner.start < outer.end)
                ) {
                    return Math.max(
                        inner.end - outer.length,
                        Math.min(outer.start, inner.start)
                    );
                }
                return (inner.start + inner.end - outer.length) / 2;
            }

            function makePoint(x, y)
            {
                return {
                    "x": x,
                    "y": y,
                    "translate": function translate(dX, dY)
                    {
                        return makePoint(x + dX, y + dY);
                    }
                };
            }

            function absolute(elem, pt)
            {
                while (elem) {
                    pt = pt.translate(elem.offsetLeft, elem.offsetTop);
                    elem = elem.offsetParent;
                }
                return pt;
            }

            var target = absolute(this, makePoint(0, 0)),
                extent = makePoint(this.offsetWidth, this.offsetHeight),
                elem = this.parentNode,
                origin;

            while (elem instanceof HTMLElement) {
                origin = absolute(elem, makePoint(elem.clientLeft, elem.clientTop));
                elem.scrollLeft = coverRange(
                    makeRange(target.x - origin.x, extent.x),
                    makeRange(elem.scrollLeft, elem.clientWidth)
                );
                elem.scrollTop = coverRange(
                    makeRange(target.y - origin.y, extent.y),
                    makeRange(elem.scrollTop, elem.clientHeight)
                );
                target = target.translate(-elem.scrollLeft, -elem.scrollTop);
                elem = elem.parentNode;
            }
        };
    }

    return uiComponent.extend({

        initialize: function () {

            this._super();
            this.focused = ko.observable(false),
            this.errorValidationMessage = ko.observable(false),

            quote.paymentMethod.subscribe(function (value) {
                if (value) {
                    this.setValidationMessage(false);
                }
            }.bind(this), null, "change");

        },

        validatePaymentMethods: function () {
            var result = false;
            var method = quote.paymentMethod();
            if (method) {
                result = true;
                this.focused(false);
            }

            if (!result) {
                this.focused(true);
            }
            return result;
        },

        setValidationMessage: function (message) {
            if (message && typeof message === "string") {
                this.errorValidationMessage(message);
            } else {
                this.errorValidationMessage(false);
            }
        }

    });

});
