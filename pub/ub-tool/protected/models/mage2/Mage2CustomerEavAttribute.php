<?php

/**
 * This is the model class for table "customer_eav_attribute".
 *
 * The followings are the available columns in table 'customer_eav_attribute':
 * @property integer $attribute_id
 * @property integer $is_visible
 * @property string $input_filter
 * @property integer $multiline_count
 * @property string $validate_rules
 * @property integer $is_system
 * @property string $sort_order
 * @property string $data_model
 * @property integer $is_used_in_grid
 * @property integer $is_visible_in_grid
 * @property integer $is_filterable_in_grid
 * @property integer $is_searchable_in_grid
 */
class Mage2CustomerEavAttribute extends Mage2ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{customer_eav_attribute}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('attribute_id', 'required'),
			array('attribute_id, is_visible, multiline_count, is_system', 'numerical', 'integerOnly'=>true),
			array('input_filter, data_model', 'length', 'max'=>255),
			array('sort_order', 'length', 'max'=>10),
			array('validate_rules', 'safe'),
		);
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Mage2CustomerEavAttribute the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
