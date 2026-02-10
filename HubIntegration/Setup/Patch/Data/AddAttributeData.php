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
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class AddAttributeData implements DataPatchInterface
{
	/**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;


    /** @var EavSetupFactory */
	private $eavsetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavsetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavsetupFactory = $eavsetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
    	 /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavsetupFactory->create(['setup' => $this->moduleDataSetup]);

        /**
         * Add attributes to the eav/attribute
         */

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,'hub_contact_id',
            [
                'type' => 'varchar',
                'label' => 'Hubspot Contact Sync',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'user_defined' => false,
                'position' => 999,
                'system' => 0
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'hub_product_id',
            [
                'type' => 'varchar',
                'backend' => '',
                'frontend' => '',
                'label' => 'Hubspot Product Sync',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => false,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => ''
            ]
        );


    }

	/**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }
}
