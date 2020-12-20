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
        "dom"
    ],
    function (Component, dom) {
        "use strict";
        return Component.extend({
            /* eslint-disable */
            initialize : function () {

                this._super();

                tmpConf = this.cnf;

                if (tmpConf.enable === "1") {
                    var googleAddrAutocomplListener = function (autocomplete) {
                        var getGPConst = function () {

                            return {
                                "GP_ADDR_TYPE_COUNTRY" : "country",
                                "GP_ADDR_TYPE_ADMIN_AREA_LEVEL_2" : "administrative_area_level_2",
                                "GP_ADDR_TYPE_ADMIN_AREA_LEVEL_1" : "administrative_area_level_1",
                                "GP_ADDR_TYPE_POSTAL_CODE" : "postal_code",
                                "GP_ADDR_TYPE_STREET_NUMBER" : "street_number",
                                "GP_ADDR_TYPE_ROUTE" : "route",
                                "GP_ADDR_TYPE_LOCALITY" : "locality",
                                "GP_ADDR_TYPE_SUBLOCALITY_LEVEL_1" : "sublocality_level_1"

                            };
                        };

                        var place = autocomplete.getPlace();

                        var countryInput = jQuery(dom.getConst("CSS_SELECTOR_COUNTRY_INPUT"));
                        var postcodeInput = jQuery(dom.getConst("CSS_SELECTOR_POSTCODE_INPUT"));
                        var cityInput = jQuery(dom.getConst("CSS_SELECTOR_CITY_INPUT"));
                        var street0Input = jQuery(dom.getConst("CSS_SELECTOR_STREET0_INPUT"));
                        var street1Input = jQuery(dom.getConst("CSS_SELECTOR_STREET1_INPUT"));
                        countryInput.val(null);
                        postcodeInput.val(null);
                        cityInput.val(null);
                        street0Input.val(null);
                        street1Input.val(null);
                        dom.setRegionInputAndSelectValue(null);
                        var street0Val = "";
                        var housenrVal = "";
                        var streetNameVal = "";
                        var countryVal = "";
                        if (!place.address_components) {
                            street0Input.val(place.name);
                        } else {
                            var addressType, short_name, long_name;

                            for (var i = 0; i < place.address_components.length; i++) {
                                addressType = place.address_components[i].types[0];
                                short_name = place.address_components[i].short_name;
                                long_name = place.address_components[i].long_name;

                                if (addressType === getGPConst().GP_ADDR_TYPE_COUNTRY) {
                                    countryVal = short_name; // remember for
                                    countryInput.val(countryVal);
                                    countryInput[0].dispatchEvent(new Event("change"));
                                } else if (addressType === getGPConst().GP_ADDR_TYPE_POSTAL_CODE) {
                                    postcodeInput.val(long_name);
                                } else if (addressType === getGPConst().GP_ADDR_TYPE_STREET_NUMBER) {
                                    housenrVal = long_name;
                                } else if (addressType === getGPConst().GP_ADDR_TYPE_ROUTE) { // street
                                    streetNameVal = long_name;
                                } else if (addressType === getGPConst().GP_ADDR_TYPE_LOCALITY) {
                                    cityInput.val(long_name);
                                } else if (addressType === getGPConst().GP_ADDR_TYPE_SUBLOCALITY_LEVEL_1) {
                                    street1Input.val(long_name);
                                }
                            } // for
                            var countryUsesThisRegionField = getGPConst().GP_ADDR_TYPE_ADMIN_AREA_LEVEL_1;
                            var houseNrBeforeStreetName = false;
                            if (countryVal === "US") {
                                houseNrBeforeStreetName = true;
                            } else if (countryVal === "UK") {
                                houseNrBeforeStreetName = true;
                            } else if (countryVal === "FR") {
                                countryUsesThisRegionField = getGPConst().GP_ADDR_TYPE_ADMIN_AREA_LEVEL_2;
                            }

                            if (houseNrBeforeStreetName) {
                                street0Input.val(housenrVal + " " + streetNameVal);
                            } else {
                                street0Input.val(streetNameVal + " " + housenrVal);
                            }
                            var regionVal = "";

                            for (i = 0; i < place.address_components.length; i++) {
                                addressType = place.address_components[i].types[0];
                                long_name = place.address_components[i].long_name;

                                if (addressType === countryUsesThisRegionField) {
                                    regionVal = long_name;
                                    break;
                                }
                            } // for
                            dom.setRegionInputAndSelectValue(regionVal);
                        }

                    }; // googleAddrAutocomplListener
                    jQuery(document).on("keyup", "input[name='street[0]']", function (e) {

                        if (!dom.getEl("BODY").hasClass(dom.getFlag("ADDRESS_AUTOCOMPLETE_ATTACHED"))) {
                            var script = document.createElement("script");
                            var apiKey = "";
                            if (tmpConf.google_maps_javascript_api_key) {
                                apiKey = tmpConf.google_maps_javascript_api_key;
                            }

                            script.src = "https://maps.googleapis.com/maps/api/js?key=" + apiKey + "&libraries=places";
                            script.async = true;
                            script.defer = true;

                            script.onload = function () {
                                if (window.location.protocol === "https:" && navigator.geolocation) {
                                    navigator.geolocation.getCurrentPosition(function (position) {
                                        var geolocation = {
                                            lat : position.coords.latitude,
                                            lng : position.coords.longitude
                                        };
                                        var circle = new google.maps.Circle({
                                            center : geolocation,
                                            radius : position.coords.accuracy
                                        });
                                        autocomplete.setBounds(circle.getBounds());
                                    });
                                }

                                {
                                    var handler = jQuery(this).attr("id");

                                    if (e.keyCode === 38 || e.keyCode === 40) {
                                    } else {
                                        var componentForm = {
                                            street_number : "long_name",
                                            route : "long_name",
                                            locality : "long_name",
                                            administrative_area_level_1 : "long_name",
                                            country : "short_name",
                                            postal_code : "long_name"
                                        };
                                        var area = {};
                                        var autocomplete = "";
                                        if (navigator.geolocation) {
                                            navigator.geolocation.getCurrentPosition(function (position) {
                                                var geolocation = {
                                                    lat : position.coords.latitude,
                                                    lng : position.coords.longitude
                                                };
                                                var circle = new google.maps.Circle({
                                                    center : geolocation,
                                                    radius : position.coords.accuracy
                                                });
                                                autocomplete.setBounds(circle.getBounds());
                                            });
                                        }
                                        autocomplete = new google.maps.places.Autocomplete(document.getElementsByName("street[0]")[0], {
                                            types : [ "geocode" ]
                                        });

                                        google.maps.event.addListener(autocomplete, "place_changed", function () {
                                            googleAddrAutocomplListener(autocomplete);
                                        });
                                    }
                                }

                            };

                            document.head.appendChild(script);

                            dom.getEl("BODY").addClass(dom.getFlag("ADDRESS_AUTOCOMPLETE_ATTACHED"));
                        }

                    });
                    return this;
                }
            } // initialize
            /* eslint-enable */
        });

    }
);
