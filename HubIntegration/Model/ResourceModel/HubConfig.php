<?php
/**
 * Makewebbetter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement(EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://makewebbetter.com/license-agreement.txt
 *
 * @category  Makewebbetter
 * @package   Makewebbetter_HubIntegration
 * @author    Makewebbetter Core Team <connect@makewebbetter.com>
 * @copyright Copyright Makewebbetter(http://makewebbetter.com/)
 * @license   https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class HubConfig
 * @package Magento\Config\Model\ResourceModel
 */
class HubConfig extends AbstractDb
{
    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('makewebbetter_hub_config_data', 'config_id');
    }

    /**
     * @param $path
     * @param $value
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveConfig($path, $value)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            'path = ?',
            $path
        );
        $row = $connection->fetchRow($select);

        $newData = ['path' => $path, 'value' => $value];

        if ($row) {
            $whereCondition = [$this->getIdFieldName() . '=?' => $row[$this->getIdFieldName()]];
            $connection->update($this->getMainTable(), $newData, $whereCondition);
        } else {
            $connection->insert($this->getMainTable(), $newData);
        }
        return $this;
    }

    /**
     * @param $path
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfigValue($path) {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            [$this->getMainTable()],
            ['value']
        )->where('path ="'.$path.'"');
        return $connection->fetchOne($select);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteContactListsFromConfig() {
        $this->getConnection()->delete($this->getMainTable(), '`path` LIKE "hubspot/lists/%"');
        return $this;
    }
}
