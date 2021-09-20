<?php

namespace Meetanshi\DistanceBasedShipping\Block\Adminhtml\DistanceBasedShipping\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Config\Model\Config\Source\Enabledisable;
use Magento\Directory\Model\Config\Source\Country;

class Form extends Generic
{
    protected $index;
    protected $systemStore;
    private $enabledisable;

    private $country;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Enabledisable $enabledisable,
        Country $country,
        array $data = []
    ) {
        $this->enabledisable = $enabledisable;

        parent::__construct($context, $registry, $formFactory, $data);
        $this->country = $country;
    }

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('row_data');
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'enctype' => 'multipart/form-data',
                'action' => $this->getData('action'),
                'method' => 'post'
            ]
            ]
        );


        if ($model->getId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Edit Warehouse'), 'class' => 'fieldset-wide']
            );
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Add Warehouse'), 'class' => 'fieldset-wide']
            );
        }
        $fieldset->addField('state', 'hidden', ['id' => 'state','name' => 'state']);

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'id' => 'status',
                'title' => __('Status'),
                'required' => true,
                'selected' => 1,
                'values' => $this->enabledisable->toOptionArray(),
            ]
        );

        $fieldset->addField(
            'street',
            'text',
            [
                'name' => 'street',
                'label' => __('Street'),
                'id' => 'street',
                'title' => __('Street'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );
        $fieldset->addField(
            'city',
            'text',
            [
                'name' => 'city',
                'label' => __('City'),
                'id' => 'city',
                'title' => __('City'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $optionsc =$this->country->toOptionArray();
        sort($optionsc);

        $country = $fieldset->addField(
            'country',
            'select',
            [
                'name' => 'country',
                'label' => __('Country'),
                'id' => 'country',
                'title' => __('Country'),
                'class' => 'letters-only',
                'required' => true,
                'values' => $optionsc,
            ]
        );

        $fieldset->addField(
            'state2',
            'select',
            [
                'name' => 'state2',
                'id' => 'state2',
                'label' => __('State'),
                'title' => __('State'),
            ]
        );

        $fieldset->addField(
            'zipcode',
            'text',
            [
                'name' => 'zipcode',
                'label' => __('Zip Code'),
                'id' => 'zipcode',
                'title' => __('Zip Code'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $country->setAfterElementHtml("   
            <script type=\"text/javascript\">
                    require([
                    'jquery',
                    'mage/template',
                    'jquery/ui',
                    'mage/translate'
                ],
                function($, mageTemplate) {
                
					var attrstate2 = { };
					$.each($(\"#state2\")[0].attributes, function(idx, attr) {
						if(attr.nodeName!='values' && attr.nodeName!='value')
						{
							attrstate2[attr.nodeName] = attr.nodeValue;
						}
					});
					
					$('#edit_form').on('keyup change', '#state2', function(event){
							$(\"#state\").val($(\"#state2\").val());
					});
					
                   $('#edit_form').on('change', '#country', function(event){
                         try {
                        
                    $.ajax({
                    
                        url : '" . $this->getUrl('*/*/regionlist') . "country/' + $('#country').val(),
                                type: 'get',
                                dataType: 'json',
                               showLoader:true,
                               success: function(data){
									$(\"input#state2\").replaceWith(function () {
										return $(\"<select />\", attrstate2);
									});
                                    $('#state2').empty();
                                    $('#state2').append(data.htmlconent);
									$('#state2 option').filter(function() { 
										return ($(this).text() == $('#state').val());
									}).prop('selected', true); 
 
                                    if($('#state2 option').length==1)
                                    {
										$(\"select#state2\").replaceWith(function () {
											return $(\"<input />\", attrstate2);
										});
										$(\"#state2\").attr('type','text');
										$(\"#state2\").val($(\"#state\").val());
										var atclass=$(\"#state2\").attr('class').replace(\"select admin__control-select\",\"input-text admin__control-text\");
										$(\"#state2\").attr('class',atclass);
										$(\"#state2\").removeClass('required-entry _required');
                                     }
                                     else 
                                   		$(\"#state2\").addClass('required-entry _required');
									
									$('#state').val($('#state2').val());
                               }
                            });
                            
                }
                catch (e) {
                    alert(e);
                }
                
                   });
                $('#country').trigger('change');   
                }
				
            );
            
            </script>");

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
