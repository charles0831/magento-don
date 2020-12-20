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
define(['uiComponent'], function (Component) {
    "use strict";
    return Component.extend({
        initialize: function () {
            this._super();
        }
    });

});

/**

'use strict';








define([    'jquery',    'dom',    'domstate', 'osc_billingfields'    ],
function (    jQuery,     dom,     domstate,     billingaddress        ) {



    return function(enabled) {
            if (enabled) {
            jQuery.getJSON("https://freegeoip.net/json?callback=?", function(location) {

                var countryInput = jQuery(dom.getConst("CSS_SELECTOR_COUNTRY_INPUT"));
                var regionInput = jQuery(dom.getConst("CSS_SELECTOR_REGION_INPUT"));
                var postcodeInput = jQuery(dom.getConst("CSS_SELECTOR_POSTCODE_INPUT"));
                var cityInput = jQuery(dom.getConst("CSS_SELECTOR_CITY_INPUT"));

                countryInput.val(location.country_code);
                countryInput[0].dispatchEvent(new Event('change'));


                if (regionInput && regionInput.size()>0) {
                    dom.setRegionInputAndSelectValue(location.region_name);
                }

                postcodeInput.val(location.zip_code);
                postcodeInput[0].dispatchEvent(new Event('change'));

                cityInput.val(location.city);
                cityInput[0].dispatchEvent(new Event('change'));
                billingaddress.cloneFields();

                });

            } else {
                billingAddress.cloneFields();

            }


        } // getGeoIPOnline: function() {


 });

**/
