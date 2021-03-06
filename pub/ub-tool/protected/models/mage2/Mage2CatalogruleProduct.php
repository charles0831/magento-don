<?php

/**
 * This is the model class for table "catalogrule_product".
 *
 * The followings are the available columns in table 'catalogrule_product':
 * @property string $rule_product_id
 * @property string $rule_id
 * @property string $from_time
 * @property string $to_time
 * @property integer $customer_group_id
 * @property string $product_id
 * @property string $action_operator
 * @property string $action_amount
 * @property integer $action_stop
 * @property string $sort_order
 * @property integer $website_id
 */
class Mage2CatalogruleProduct extends Mage2ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{catalogrule_product}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('website_id', 'required'),
			array('customer_group_id, action_stop, website_id', 'numerical', 'integerOnly'=>true),
			array('rule_id, from_time, to_time, product_id, action_operator, sort_order', 'length', 'max'=>10),
			// rules applied from M2.3.1: changed max length allowed from 12 to 20
			array('action_amount', 'length', 'max'=>20)
		);
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Mage2CatalogruleProduct the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
