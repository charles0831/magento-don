** INSTALLATION INSTRUCTIONS

MANUAL INSTALLATION >= 1.2.*

1 - unzip to app/code/Onestepcheckout/Iosc
2 - require composer packages while in magneto root folder

php ./vendor/composer/composer/bin/composer require dflydev/dot-access-data;
php ./vendor/composer/composer/bin/composer require geoip2/geoip2:~2.0;
php bin/magento module:enable Onestepcheckout_Iosc;
php bin/magento setup:upgrade;
php bin/magento setup:di:compile;
php bin/magento cache:flush;

3 - log in to admin and go to Stores > Configuration > Sales > OneStepCheckout and see the system settings, enable OneStepCheckout
4 - add something to cart and reach /checkout

COMPOSER INSTALLATION >= 1.2.*

1 - make a directory /path/to/zipfiles/
2 - drop OneStepCheckout-{packageversion}.zip to that folder
3 - go to magento root folder and : composer config repositories.onestepcheckout_iosc artifact /path/to/zipfiles/
4 - install the package: composer require "onestepcheckout/iosc:{packageversion}"
5 - run following

php bin/magento module:enable Onestepcheckout_Iosc;
php bin/magento setup:upgrade;
php bin/magento setup:di:compile;
php bin/magento cache:flush;

6 - log in to admin and go to Stores > Configuration > Sales > OneStepCheckout and see the system settings, enable OneStepCheckout
7 - add something to cart and reach /checkout

** UNINSTALL INSTRUCTIONS

php -d memory_limit=2048M bin/magento module:uninstall Onestepcheckout_Iosc  --clear-static-content
run in mysql: DELETE FROM `core_config_data` WHERE `path` LIKE '%onestepcheckout_iosc/%';

If installed via composer:
rm -rf vendor/onestepcheckout

If installed manually:
rm -rf app/code/Onestepcheckout
