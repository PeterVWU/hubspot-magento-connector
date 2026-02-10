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

namespace Makewebbetter\HubIntegration\Controller\Adminhtml\Main;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;

class License extends \Magento\Backend\App\Action
{
    protected $_licenseActivateUrl = null;
    protected $_feedHelper = null;

    const LICENSE_ACTIVATION_URL_PATH = 'system/license/activate_url';

    const SECRET_KEY = '59f32ad2f20102.74284991';

    const END_POINT = 'https://makewebbetter.com/';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $urlDecoder;

    protected $curl;

    protected $helper;

    protected $jsonFactory;
    protected $endpoint;
    protected $httpRequest;
    protected $storeManager;


    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Curl $curl,
        DecoderInterface $urlDecoder,
        \Makewebbetter\HubIntegration\Helper\Feed $_feedHelper,
        \Makewebbetter\HubIntegration\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\App\RequestInterface $httpRequest,
        StoreManagerInterface $storeManager

    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->urlDecoder = $urlDecoder;
        $this->curl = $curl;
        $this->_feedHelper = $_feedHelper;
        $this->helper = $helper;
        $this->jsonFactory = $jsonFactory;
        $this->httpRequest = $httpRequest;
        $this->storeManager = $storeManager;
    }

    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        $postData = $this->getRequest()->getParams();
        unset($postData['key']);
        unset($postData['form_key']);
        unset($postData['isAjax']);

        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $parse_url = parse_url($baseUrl);
        $serverDomain = $parse_url['host'];        

        $path = \Makewebbetter\HubIntegration\Block\Extensions::LICENSE_STATUS_DOMAIN;
        $this->_feedHelper->setDefaultStoreConfig($path, $serverDomain, 0);
        $helper = $this->helper;
        $path = \Makewebbetter\HubIntegration\Block\Extensions::LICENSE_STATUS_PATH_PREFIX;
        $licenseStatusDb = $helper->getStoreConfig($path);
        $json = array('success' => 0, 'message' => __('There is an Error Occurred.'));
            if ($postData) {
                foreach ($postData as $moduleName => $licensekey) {
                    if (preg_match('/makewebbetter_/i', $moduleName)) {
                        if (strlen($licensekey) == 0) {
                            $json = array('success' => 1, 'message' => '');
                            $this->getResponse()->setHeader('Content-type', 'application/json');
                            $resultJson = $this->jsonFactory->create();
                            return $resultJson->setData(json_encode($json));
                        }
                        unset($postData[$moduleName]);
                        $postData['module_name'] = $moduleName;
                        $allModules = $this->_feedHelper->getAllModules();
                        $itemReference = '';
                        $productReference = '';
                        $postData['module_version'] = isset($allModules[$moduleName]['release_version']) ? $allModules[$moduleName]['release_version'] : '';
                        if (preg_match('/makewebbetter_HubIntegration/i', $moduleName)) {
                            $itemReference = 'HubSpot Magento Integration';
                            $productReference = 'MWBPK-71868';
                        } elseif (preg_match('/makewebbetter_HubGuestUser/i', $moduleName)) {
                            $itemReference = 'HubSpot Magento Guest User Syncing';
                            $productReference = 'MWBPK-72417';
                        } elseif (preg_match('/makewebbetter_HubContactImport/i', $moduleName)) {
                            $itemReference = 'HubSpot Magento Two-Way Syncing';
                            $productReference = 'MWBPK-72614';
                        } elseif (preg_match('/makewebbetter_HubSpotMagentoFieldMapping/i', $moduleName)) {
                            $itemReference = 'HubSpot Magento Field Mapping';
                            $productReference = 'MWBPK-75387';
                        }
                        $postData['slm_action'] = 'slm_activate';
                        $postData['secret_key'] = self::SECRET_KEY;
                        $postData['license_key'] = $licensekey;
                        $postData['_registered_domain'] = $serverDomain;
                        $postData['item_reference'] = urlencode($itemReference);
                        $postData['product_reference'] = $productReference;
                    }
                }
                $response = $this->validateAndActivateLicense_new($postData);
                try {
                    if ($response && $response['result'] == 'success')
                        {
                        $path = \Makewebbetter\HubIntegration\Block\Extensions::HASH_PATH_PREFIX . strtolower($postData['module_name']);
                        $this->_feedHelper->setDefaultStoreConfig($path, $licensekey, 0);
                        $path = \Makewebbetter\HubIntegration\Block\Extensions::LICENSE_STATUS_PATH_PREFIX . strtolower($postData['module_name']);
                        $this->_feedHelper->setDefaultStoreConfig($path, 'ACTIVATED', 0);
                        $json['success'] = 1;
                        $json['message'] = __('Module Activated successfully.');
                    } else {
                        $json['success'] = 0;
                        $json['message'] = isset($response2['error_code']) && $response['result'] == 'error' ? 'Error: ' . $response['message'] : __('Invalid License Key.');
                    }
                } catch (\Exception $e) {
                    $json['success'] = 0;
                    $json['message'] = $e->getMessage();
                }
            }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $resultJson =$this->jsonFactory->create();
        return $resultJson->setData(json_encode($json));
    }

    public function validateAndActivateLicense_new($urlParams = array())
    {
        $result = false;
        $body = '';
        if(isset($urlParams['form_key'])) unset($urlParams['form_key']);
        try {
            $url = self::END_POINT. '?' . http_build_query($urlParams);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response,true);
        } catch (\Exception $e) {
            return false;
        }
        return $result;
    }
}
