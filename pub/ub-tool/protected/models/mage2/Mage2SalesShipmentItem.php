<?php

/**
 * This is the model class for table "sales_shipment_item".
 *
 * The followings are the available columns in table 'sales_shipment_item':
 * @property string $entity_id
 * @property string $parent_id
 * @property string $row_total
 * @property string $price
 * @property string $weight
 * @property string $qty
 * @property integer $product_id
 * @property integer $order_item_id
 * @property string $additional_data
 * @property string $description
 * @property string $name
 * @property string $sku
 */
class Mage2SalesShipmentItem extends Mage2ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{sales_shipment_item}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('parent_id', 'required'),
			array('product_id, order_item_id', 'numerical', 'integerOnly'=>true),
			array('parent_id', 'length', 'max'=>10),
			array('weight, qty', 'length', 'max'=>12),
			// rules applied from M2.3.1: changed max length allowed from 12 to 20
			array('row_total, price', 'length', 'max'=>20),
			array('name, sku', 'length', 'max'=>255),
			array('additional_data, description', 'safe'),
		);
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Mage2SalesShipmentItem the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
