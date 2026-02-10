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

namespace Makewebbetter\HubIntegration\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Data
 * @package Makewebbetter\HubIntegration\Helper
 */
class Data extends AbstractHelper
{
    const SYSTEM_CONFIG_MODULE_ENABLE = "hub_integration/hubspot_integration/enable";
    const SYSTEM_CONFIG_DATA_SYNC_CRON_TIME = "hub_integration/export_settings/cron_time";
    const SYSTEM_CONFIG_DELETE_ERROR_LOG_TIME_IN_DAYS = "hub_integration/export_settings/delete_error_log";
    const SYSTEM_CONFIG_ABANDONED_CART_TIME = 'hub_integration/hubspot_integration/abandoned_cart_time';
    const SYSTEM_CONFIG_RFM_FIELDS = 'hub_integration/rfm_settings/rfm_fields';

    protected $_allowedFeedType = array();
    protected $_objectManager;
    protected $_storeManager;
    protected $_scopeConfigManager;
    protected $_configValueManager;
    protected $_transaction;
    protected $_context;
    protected $_cacheTypeList;
    protected $_cacheFrontendPool;
    protected $request;
    protected $_productMetadata;
    /* protected $_assetRepo; */
    protected $_storeId = 0;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {

        $this->_objectManager = $objectManager;
        $this->_context = $context;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_request = $request;
        $this->_productMetadata = $productMetadata;
        parent::__construct($context);
        $this->_storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $this->_scopeConfigManager = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $this->_configValueManager = $this->_objectManager->get('Magento\Framework\App\Config\ValueInterface');
        $this->_transaction = $this->_objectManager->get('Magento\Framework\DB\Transaction');
    }


    /**
     * @param $path
     * @return mixed
     */
    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function isModuleEnable()
    {
        return $this->getConfigValue(self::SYSTEM_CONFIG_MODULE_ENABLE);
    }

    /**
     * Function for getting Config value of current store
     *
     * @param string $path,
     */
    public function getStoreConfig($path,$storeId=null)
    {
        $store=$this->_storeManager->getStore($storeId);
        return $this->_scopeConfigManager->getValue($path, 'store', $store->getCode());
    }
}
