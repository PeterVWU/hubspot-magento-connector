<?php
/**
 * Makewebbetter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://makewebbetter.com/license-agreement.txt
 *
 * @category    Makewebbetter
 * @package     Makewebbetter_HubIntegration
 * @author      Makewebbetter Core Team <connect@makewebbetter.com>
 * @copyright   Copyright Makewebbetter (http://makewebbetter.com/)
 * @license     https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class Uninstall
 * @package Makewebbetter\HubIntegration\Setup\Patch\Data
 */
class Uninstall implements DataPatchInterface, PatchRevertableInterface
{
    const CONFIG_SECTION = 'hub_integration';
    const MUDULE_NAME = 'Makewebbetter_HubIntegration';
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {

        $this->moduleDataSetup = $moduleDataSetup;
    }

    public static function getDependencies()
    {
        return [];
    }

    /**
     * Rollback all changes, done by this patch
     *
     * @return void
     */
    public function revert()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->dropTable('hub_makewebbetter_error_log');
        $connection->dropTable('makewebbetter_hub_config_data');
        $connection->dropTable('hub_makewebbetter_items');
        $connection->dropTable('hub_makewebbetter_queued_items');
        $connection->delete('core_config_data', ['path LIKE ?' => self::CONFIG_SECTION . '%']);
        $connection->delete('setup_module', ['module = ?' => self::MUDULE_NAME ]);
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
    }
}