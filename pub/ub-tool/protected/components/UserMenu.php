<?php

Yii::import('zii.widgets.CPortlet');

class UserMenu extends CPortlet
{
	public function init()
	{
		parent::init();
	}

	protected function renderContent()
	{
        //authentication for security
        $msg1 = Yii::t('frontend', 'Sign in with your Magento 2 admin credentials is required');
        $msg2 = '<div style="padding: 20px;background-color: #FFFECE;border-left: 5px solid #EAEC80;color: #7D6C4B;line-height: 30px;">';
        $msg2 .= '<span style="color: #372404;font-weight: bold;">'.Yii::t('frontend','AUTHENTICATION REQUIRED:').'</span>';
        $msg2 .= '<br/>'.Yii::t('frontend','You must sign in with your Magento 2 admin credentials to use our UB Data Migration Pro tool.');
        $msg2 .= '<br/>'.Yii::t('frontend','Please press F5 or tab UB Data Migration Pro icon on the Admin sidebar to proceed to the log in form.');
        $msg2 .= '</div>';

        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate:Basic realm="'.$msg1.'"');
            header('HTTP/1.0 401 Unauthorized');
            echo $msg2;
            //throw new CHttpException(401, $msg2);
            Yii::app()->end();
        } else {
            if (!UserMenu::authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                header('WWW-Authenticate:Basic realm="'.$msg1.'"');
                header('HTTP/1.0 401 Unauthorized');
                echo $msg2;
                //throw new CHttpException(401, $msg2);
                Yii::app()->end();
            }
        }

        $steps = UBMigrate::model()->findAll();
		$this->render('userMenu', array('steps' => $steps));
	}

    /**
     * @Todo: authenticate by M2 admin credentials
     * @param $username
     * @param $password
     * @return bool
     */
    public static function authenticate($username, $password) {
        $rs = false;
        $tablePrefix = Yii::app()->db->tablePrefix;
        $query = "SELECT `password` FROM {$tablePrefix}admin_user WHERE `username` = '{$username}'";
        $strPassword = Yii::app()->db->createCommand($query)->queryScalar();
        if ($strPassword) {
            $hashes = explode(':', $strPassword);
            $version = (int) $hashes[2];
            if ($version === 1) {
                $hash = hash('sha256',$hashes[1] . $password);
            } else if ($version === 2) {
                $hash  = self::_getArgonHash($password, $hashes[1]);
            }
            if ($hashes[0] === $hash) {
                $rs = true;
            }
        }

        return $rs;
    }

    /**
     * Generate Argon2ID13 hash.
     *
     * @param string $data
     * @param string $salt
     * @return string
     * @throws \SodiumException
     */
    private static function _getArgonHash($data, $salt = '')
    {
        $salt = empty($salt) ?
            random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES) :
            substr($salt, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES);

        if (strlen($salt) < SODIUM_CRYPTO_PWHASH_SALTBYTES) {
            $salt = str_pad($salt, SODIUM_CRYPTO_PWHASH_SALTBYTES, $salt);
        }

        return bin2hex(
            sodium_crypto_pwhash(
                SODIUM_CRYPTO_SIGN_SEEDBYTES,
                $data,
                $salt,
                SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
                SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
                SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
            )
        );
    }

}
