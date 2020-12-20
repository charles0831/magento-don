/**
 *
 */

define([ 'jquery', 'Magento_Ui/js/lib/core/class', 'underscore', 'jquery/ui' ], function (
    jQuery,
    Class,
    _
) {
    'use strict';

    var appClass = Class.extend({

        /**
         * on object init , transform the tables
         *
         * @param object config
         */
        initialize : function (config) {
            this.getFields(config, 'billing');
            this.getFields(config, 'shipping');
        },

        /**
         * Get the fields and call actions and set observers
         *
         * @param object config
         * @param string name
         */
        getFields : function (config, name) {
            var fields = name + 'fields';
            var fieldString = '#row_onestepcheckout_iosc_' + fields + '_' + fields;
            this.addRow(
                fieldString + ' table td:nth-child(2) input',
                name,
                config.fields.merged,
                this,
                fieldString + ' table td:first-child input',
                fieldString + ' table tbody'
            );
            this.setReadonly(fieldString + ' table td:first-child input');
            this.setChecboxes(fieldString + ' table td:nth-child(3) input:checkbox');
            this.setChecboxes(fieldString + ' table td:nth-child(4) input:checkbox');
            this.removeButton(
                fieldString + ' table td:nth-child(8) button',
                'td:nth-child(2) input',
                config.fields.merged,
                this,
                fieldString + ' table td:first-child input'
            );
            this.addObserver(
                fieldString + ' table tfoot button',
                fieldString + ' table td:first-child input',
                this
            );
            this.addSortable(
                fieldString + ' table tbody',
                fieldString + ' table td:first-child input',
                this
            );

        },

        /**
         * Based on selector set readonly on element
         *
         * @param string selector
         */
        setReadonly : function (selector) {
            jQuery(selector).each(function () {
                jQuery(this).prop('readonly', true);
            });
        },

        /**
         * Based on selector set hidden elements to match real checboxes
         *
         * @param string selector
         */
        setChecboxes : function (selector) {

            jQuery(selector).each(function (index) {
                var el = jQuery(this);
                var tmp = el.clone().attr('type', 'hidden').attr('value', '0');
                if (el.val() === "1") {
                    el.prop('checked', true);
                }
                el.val("1");
                tmp.insertBefore(el);
            });

        },

        /**
         * Based on selector update sort value on element
         *
         * @param string selector
         */
        setOrder : function (selector) {
            jQuery(selector).each(function (index) {
                jQuery(this).val(index + 1);
            });
        },

        /**
         * Remove a button element from the table
         *
         * @param string selector
         * @param string search
         * @param object mergedFields
         */
        removeButton : function (
            selector,
            search,
            mergedFields,
            that,
            inputSelector
) {
            jQuery(selector).each(
                function () {
                var inputElem = this.up('tr').select(search).first();
                if (inputElem) {
                    if (typeof mergedFields[inputElem.value.split(
                        /[:]+/
                    ).pop()] !== 'undefined') {
                        inputElem.readOnly = true;
                        this.remove();
                    } else {
                        jQuery(this).bind('click', function () {
                            that.setOrder(inputSelector);
                        });
                    }
                }
                }
            );
        },

        /**
         * attach observer functionality to "add button"
         *
         * @param string selector
         * @param string inputSelector
         * @param object that
         */
        addObserver : function (selector, inputSelector, that) {
            jQuery(selector).first().click(function () {
                that.setReadonly(inputSelector);
                that.setOrder(inputSelector);
            });
        },

        /**
         * attach sortable functionality to table
         *
         * @param string selector
         * @param string inputSelector
         * @param object that
         */
        addSortable : function (selector, inputSelector, that) {
            jQuery(selector).sortable({
                placeholder : 'ui-state-highlight',
                axis : 'y',
                cursor : 'move',
                cursorAt : {
                    left : 5
                },
                update : function (event, ui) {
                    that.setOrder(inputSelector);
                }

            });
        },

        /**
         * Add a row to table via in page method window[containerElemId].add
         *
         * @param string selector
         * @param string name
         * @param object mergedFields
         */
        addRow : function (selector, name, mergedFields, that, inputSelector, rowSelector) {

            var data = jQuery(selector);
            var rowId = jQuery(rowSelector);
            var existing = {};
            var containerElemId = false;
            if (!containerElemId && rowId) {
                containerElemId = rowId.attr('id');
            }

            data.each(function () {
                var value = this.value.split(/[:]+/).pop();
                if (typeof mergedFields[value] !== 'undefined') {
                    existing[value] = value;
                }
            });

            if (containerElemId) {
                containerElemId = containerElemId.replace('addRow', 'arrayRow');

                var fieldsLength = Object.keys(mergedFields).length;
                var existingLength = Object.keys(existing).length;

                if (fieldsLength > existingLength) {
                    var i = existingLength;
                    jQuery.each(mergedFields, function (key) {
                        var d = new Date();
                        var id = '_' + d.getTime() + '_' + d.getMilliseconds();
                        if (typeof existing[key] === 'undefined') {
                            i++;
                            window[containerElemId].add({
                                'field_sort' : '' + i + '',
                                'field_id' : '' + key + '',
                                "enabled": "1",
                                "required": "0",
                                'length' : '2',
                                'css_class' : '',
                                'default_value' : '',
                                '_id' : '' + id + '',
                                'column_values' : {
                                    "' + id + '_field_sort" : +i,
                                    "' + id + '_field_id" : '' + key + '',
                                    "' + id + '_enabled" : '1',
                                    "' + id + '_required" : '' + mergedFields[key].required + '',
                                    "' + id + '_length" : '' + i + '',
                                    "' + id + '_css_class" : '',
                                    "' + id + '_default_value" : ''

                                }
                            });
                        }
                    });
                }
                that.setOrder(inputSelector);
            }
        }

    });

    return appClass;

});
