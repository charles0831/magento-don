<?php
return array(
    'components'=>array(
        //database of Magento1
        'db1' => array(
            'connectionString' => 'mysql:host=pricebusters.furniture;port=3306;dbname=pricebustersdb',
            'emulatePrepare' => true,
            'username' => 'pricebustersdb',
            'password' => 'SsKacN9_6',
            'charset' => 'utf8',
            'tablePrefix' => '',
            'class' => 'CDbConnection'
        ),
        //database of Magento 2 (we use this database for this tool too)
        'db' => array(
            'connectionString' => 'mysql:host=localhost;port=3306;dbname=m235',
            'emulatePrepare' => true,
            'username' => 'root',
            'password' => 'ajrnskak10',
            'charset' => 'utf8',
            'tablePrefix' => '',
            'class' => 'CDbConnection'
        ),
    )
);
