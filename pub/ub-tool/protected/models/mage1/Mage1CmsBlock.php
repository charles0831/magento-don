<?php

/**
 * This is the model class for table "cms_block".
 *
 * The followings are the available columns
 * @property string $block_id
 * @property string $title
 * @property string $identifier
 * @property string $content
 * @property string $creation_time
 * @property string $update_time
 * @property string $is_active
 */
class Mage1CmsBlock extends Mage1ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{cms_block}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('title, identifier, is_active', 'required'),
			array('title, identifier', 'length', 'max'=>255)
		);
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Mage1CmsBlock the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
