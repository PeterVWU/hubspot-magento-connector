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

namespace Makewebbetter\HubIntegration\Cron;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Helper\Feed;
use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Makewebbetter\HubIntegration\Controller\Adminhtml\Main\License;
use Makewebbetter\HubIntegration\Model\Source\RequestType;
use Magento\Store\Model\StoreManagerInterface;

class Check
{

    protected $feedHelper;
    protected $license;
    protected $httpRequest;
    private $helper;
    private $connectionManager;
    protected $storeManager;

    public function __construct(
        Feed $feedHelper,
        License $license,
        \Magento\Framework\App\RequestInterface $httpRequest,
        Helper  $helper,
        ConnectionManager $connectionManager,
        StoreManagerInterface $storeManager
    )
    {
        $this->feedHelper = $feedHelper;
        $this->license = $license;
        $this->httpRequest = $httpRequest;
        $this->helper = $helper;
        $this->connectionManager = $connectionManager;
        $this->storeManager = $storeManager;
    }


    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {

        if (!$this->helper->isModuleEnable())
            return true;
        try {
            $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
            $licenseStatusDb = $checkStatus['license_status'];
            $flag = $checkStatus['trail_status'];
            if($licenseStatusDb == 'ACTIVATED' || $flag != 'Yes'){

                $baseUrl = $this->storeManager->getStore()->getBaseUrl();
                $parse_url = parse_url($baseUrl);
                $serverDomain = $parse_url['host']; 
               
                $allModules = $this->feedHelper->getAllModules();
                foreach ($allModules as $moduleName => $module) {
                    if (preg_match('/HubCustomisation/i', $moduleName) || preg_match('/HubEmailSubscriberSync/i', $moduleName)) {
                        continue;
                    } else {
                        $path = \Makewebbetter\HubIntegration\Block\Extensions::HASH_PATH_PREFIX . strtolower($moduleName);
                        $licenseKey = $this->feedHelper->getStoreConfig($path);
                        $itemReference = '';
                        $productReference = '';
                        $postDataCheck['module_name'] = $moduleName;
                        if (preg_match('/Makewebbetter_HubIntegration/i', $moduleName)) {
                            $itemReference = 'HubSpot Magento Integration';
                            $productReference = 'MWBPK-71868';
                        } elseif (preg_match('/Makewebbetter_HubGuestUser/i', $moduleName)) {
                            $itemReference = 'HubSpot Magento Guest User Syncing';
                            $productReference = 'MWBPK-72417';
                        } elseif (preg_match('/Makewebbetter_HubContactImport/i', $moduleName)) {
                            $itemReference = 'HubSpot Magento Two-Way Syncing';
                            $productReference = 'MWBPK-72614';
                        } elseif (preg_match('/Makewebbetter_HubSpotMagentoFieldMapping/i', $moduleName)) {
                            $itemReference = 'HubSpot Magento Field Mapping';
                            $productReference = 'MWBPK-75387';
                        }
                        $postDataCheck['slm_action'] = 'slm_check';
                        $postDataCheck['secret_key'] = $this->license::SECRET_KEY;
                        $postDataCheck['license_key'] = $licenseKey;
                        $postDataCheck['_registered_domain'] = $serverDomain;
                        $postDataCheck['item_reference'] = urlencode($itemReference);
                        $postDataCheck['product_reference'] = $productReference;

                        $response = $this->license->validateAndActivateLicense_new($postDataCheck);
                        $licenseStatus = false;
                        if ($response && $response['result'] == 'success' && isset($response['registered_domains'])) {
                            foreach ($response['registered_domains'] as $domain) {
                                if ($domain['registered_domain'] == $serverDomain && $domain['lic_key'] == $postDataCheck['license_key']) {
                                    $licenseStatus = true;
                                }
                            }
                        }
                        if ($licenseStatus == false) {
                            $path = \Makewebbetter\HubIntegration\Block\Extensions::LICENSE_STATUS_PATH_PREFIX . strtolower($postDataCheck['module_name']);
                            $this->feedHelper->setDefaultStoreConfig($path, 'DE-ACTIVATED', 0);
                        }
                    }
                }
            }
        }catch (\Exception $e) {
            $this->connectionManager->createLog(
                400,
                'exception',
                [
                    'cron' => 'License Check cron',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );
        }
    }
}
