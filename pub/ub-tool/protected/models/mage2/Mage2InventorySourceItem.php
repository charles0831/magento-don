<?php

/**
 * This is the model class for table "inventory_source_item".
 *
 * The followings are the available columns in table 'inventory_source_item':
 * @property integer $source_item_id
 * @property integer $source_code
 * @property string $sku
 * @property string $quantity
 * @property string $status
 */
class Mage2InventorySourceItem extends Mage2ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{inventory_source_item}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status', 'numerical', 'integerOnly'=>true),
			array('quantity', 'numerical'),
			array('sku', 'length', 'max'=>64)

		);
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Mage2InventorySourceItem the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
