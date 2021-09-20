<?php
/**
 * Remove or Change Displayed States and Regions
 *
 * LICENSE
 *
 * This source file is subject to the Eltrino LLC EULA
 * that is bundled with this package in the file LICENSE_EULA.txt.
 * It is also available through the world-wide-web at this URL:
 * http://eltrino.com/license-eula.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 *
 * @category    Eltrino
 * @package     Eltrino_Region
 * @copyright   Copyright (c) 2015 Eltrino LLC. (http://eltrino.com)
 * @license     http://eltrino.com/license-eula.txt  Eltrino LLC EULA
 */
namespace Eltrino\Region\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

/**
 * Class RegionName
 * @package Eltrino\Region\Model\ResourceModel
 */
class RegionName extends AbstractDb
{
    /**
     * Class Constructor
     */
    protected function _construct()
    {
        $this->_init('directory_country_region_name', 'region_id');
    }

    protected function isObjectNotNew(AbstractModel $object)
    {
        if (!$object->getData('new')) {
            return $object->getId() !== null && (!$this->_useIsObjectNew || !$object->isObjectNew());
        } else {
            return false;
        }
    }

    protected function saveNewObject(AbstractModel $object)
    {
        $bind = $this->_prepareDataForSave($object);

        if ($this->_isPkAutoIncrement && !$object->getData('new')) {
            unset($bind[$this->getIdFieldName()]);
        }

        $connection = $this->getConnection();
        $connection->beginTransaction();
        try {
            $connection->insert($this->getMainTable(), $bind);
            $object->setId($connection->lastInsertId($this->getMainTable()));

            if ($this->_useIsObjectNew) {
                $object->isObjectNew(false);
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
