<?php

/**
 * This is the model class for table "cms_page".
 *
 * The followings are the available columns
 * @property string $page_id
 * @property string $title
 * @property string $root_template
 * @property string $meta_keywords
 * @property string $meta_description
 * @property string $identifier
 * @property string $content_heading
 * @property string $content
 * @property string $creation_time
 * @property string $update_time
 * @property string $is_active
 * @property string $sort_order
 * @property string $layout_update_xml
 * @property string $custom_theme
 * @property string $custom_root_template
 * @property string $custom_layout_update_xml
 * @property string $custom_theme_from
 * @property string $custom_theme_to
 *
 */
class Mage1CmsPage extends Mage1ActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{cms_page}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('identifier, is_active, sort_order', 'required'),
			array('title, root_template, content_heading, custom_root_template', 'length', 'max'=>255)
		);
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Mage1CmsPage the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
