<?php

/**
 * This is the model class for table "store_group".
 *
 * The followings are the available columns in table 'store_group':
 * @property integer $group_id
 * @property integer $website_id
 * @property string $code
 * @property string $name
 * @property string $root_category_id
 * @property integer $default_store_id
 */
class Mage2StoreGroup extends Mage2ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{store_group}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name', 'required'),
			array('website_id, default_store_id', 'numerical', 'integerOnly'=>true),
			array('code', 'length', 'max'=>32),
			array('name', 'length', 'max'=>255),
			array('root_category_id', 'length', 'max'=>10),
		);
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Mage2StoreGroup the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
