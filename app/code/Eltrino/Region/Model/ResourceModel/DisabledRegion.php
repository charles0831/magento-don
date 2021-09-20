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

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class DisabledRegion
 * @package Eltrino\Region\Model\ResourceModel
 */
class DisabledRegion extends AbstractDb
{

    protected $regionNameTable;
    protected $_localeResolver;

    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_localeResolver = $localeResolver;
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('eltrino_region_entity', 'entity_id');
        $this->regionNameTable = $this->getTable('directory_country_region_name');
    }
    /**
     * Load by country id and locale
     * @param $countryId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException.
     */
    public function loadByCountry($countryId)
    {
        $connection = $this->getConnection();
        $locale = $this->_localeResolver->getLocale();
        $joinCondition = $connection->quoteInto('rname.region_id = mt.region_id AND rname.locale = ?', $locale);
        $select = $connection->select()->from(
            ['mt' => $this->getMainTable()],
            array('rname.name', 'rname.region_id')
        )->joinLeft(
            ['rname' => $this->regionNameTable],
            $joinCondition,
            ['name']
        )->where(
            'mt.country_id = ?',
            $countryId
        );

        $data = [];
        foreach ($connection->fetchAll($select) as $item) {
            $data[$item['region_id']] = $item['name'];
        }

        return $data;
    }
    /**
     * Get Disabled Regions Ids
     *
     * @param $countryId
     * @return array
     */
    public function getDisabledRegionIdsByCountry($countryId)
    {
        return array_keys($this->loadByCountry($countryId));

    }

    public function deleteDisabledRegionsByCountry($countryId)
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();
        try {
            $connection->delete($this->getMainTable(), ['country_id = ?' => $countryId]);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}