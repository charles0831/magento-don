<?php

/**
 * This is the model class for table "catalogrule".
 *
 * The followings are the available columns in table 'catalogrule':
 * @property string $rule_id
 * @property string $name
 * @property string $description
 * @property string $from_date
 * @property string $to_date
 * @property integer $is_active
 * @property string $conditions_serialized
 * @property string $actions_serialized
 * @property integer $stop_rules_processing
 * @property string $sort_order
 * @property string $simple_action
 * @property string $discount_amount
 */
class Mage2Catalogrule extends Mage2ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{catalogrule}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('is_active, stop_rules_processing', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>255),
			array('sort_order', 'length', 'max'=>10),
			array('simple_action', 'length', 'max'=>32),
			// rules applied from M2.3.1: changed max length allowed from 12 to 20
			array('discount_amount', 'length', 'max'=>20),
			array('description, from_date, to_date, conditions_serialized, actions_serialized', 'safe'),
		);
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Mage2Catalogrule the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
