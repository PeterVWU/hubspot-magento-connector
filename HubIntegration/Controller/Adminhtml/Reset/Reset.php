<?php
/**
 * Class Reset
 * @package Makewebbetter\HubIntegration\Controller\Adminhtml\Reset
 */
namespace Makewebbetter\HubIntegration\Controller\Adminhtml\Reset;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\View\Result\PageFactory;
use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;

class Reset extends Action
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

    /**
     * Authorization level of a basic admin session
     */

    const ADMIN_RESOURCE = 'Makewebbetter_HubIntegration::hub_config';

    /**
     * @var PageFactory
     */

    protected $resultPageFactory;

    /**
     * @var Helper
     */

    public $helper;

    public $resource;

    public $moduleManager;
     /**
     * @var \Makewebbetter\HubIntegration\Helper\ConnectionManager
     */
    public $connectionManager;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Helper $helper
     */

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        Helper $helper,
        \Magento\Framework\Module\Manager $moduleManager,
        ConnectionManager $connectionManager
    ) {

        $this->moduleDataSetup = $moduleDataSetup;
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->resource = $resource;
        $this->moduleManager = $moduleManager;
        $this->connectionManager = $connectionManager;
        parent::__construct($context);
    }

    /**
     * Rollback all changes, done by this patch
     *
     * @return void
     */
    public function execute()
    {
        $response = $this->connectionManager->removeConnectorFromHubSpot();
       
        $connection = $this->resource->getConnection();
        $data = ['value' => null];
        $connection->delete('hub_makewebbetter_error_log');
        $connection->delete('makewebbetter_hub_config_data');
        $connection->delete('hub_makewebbetter_items');
        $connection->delete('hub_makewebbetter_queued_items');

        $connection->update('quote', ['hub_deal_id' => null]);
        $connection->update('quote_item', ['hub_line_items_id' => null]);
        $connection->update('sales_order', ['hub_deal_id' => null]);
        $connection->update('sales_order_item', ['hub_line_items_id' => null]);


        $eavTable = 'eav_attribute';
        $customerAttributeTable = 'customer_entity_varchar';
        $productAttributeTable = 'catalog_product_entity_varchar';

        $sql = "Select attribute_id FROM " . $eavTable." Where attribute_code = 'hub_contact_id'";
        $result = $connection->fetchAll($sql);
        $customerAttributeId = $result[0]['attribute_id'];

        $sql = "Select attribute_id FROM " . $eavTable." Where attribute_code = 'hub_product_id'";
        $result = $connection->fetchAll($sql);
        $productAttributeId = $result[0]['attribute_id'];

        $connection->update($customerAttributeTable, $data, ['attribute_id = ?' => $customerAttributeId ]);
        $connection->update($productAttributeTable, $data, ['attribute_id = ?' => $productAttributeId ]);

        //if HubGuestUser module is enable
        if($this->moduleManager->isEnabled('Makewebbetter_HubGuestUser')){
            $connection->update('sales_order', ['hub_contact_id' => null]);
        }

        // if HubSpotMagentoFieldMapping is enable
        if($this->moduleManager->isEnabled('makewebbetter_HubSpotMagentoFieldMapping')) {
            $connection->update('core_config_data', $data, ['path = ?' => 'hub_integration/fields_mapping/mapping_table']);
        }
    }
}
