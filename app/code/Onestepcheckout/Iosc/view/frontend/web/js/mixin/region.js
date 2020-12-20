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
    [ 'Magento_Ui/js/form/element/region', 'mageUtils', 'uiLayout' ],
    function (region, utils, layout) {
            'use strict';

            var inputNode = {
                parent : '${ $.$data.parentName }',
                component : 'Magento_Ui/js/form/element/abstract',
                template : '${ $.$data.template }',
                provider : '${ $.$data.provider }',
                name : '${ $.$data.index }_input',
                dataScope : '${ $.$data.customEntry }',
                customScope : '${ $.$data.customScope }',
                sortOrder : '${ $.$data.sortOrder }',
                displayArea : 'body',
                label : '${ $.$data.label }'
            };

            return region.extend({

                /**
                 * Creates input from template, renders it via renderer.
                 *
                 * @returns {Object} Chainable.
                 */
                initInput : function () {
                    var node = utils.template(inputNode, this);
                    node.additionalClasses = this.additionalClasses;
                    layout([ node ]);
                    return this;
                }

            });
    }
);
