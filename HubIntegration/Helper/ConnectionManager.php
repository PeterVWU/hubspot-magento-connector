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

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Backend\Model\UrlInterface As BackendUrl;
use Makewebbetter\HubIntegration\Model\Source\RequestType;
use Makewebbetter\HubIntegration\Model\ErrorLogFactory;
use Makewebbetter\HubIntegration\Helper\Ecomdetails;
use Makewebbetter\HubIntegration\Helper\Data as HubHelper;

class ConnectionManager extends AbstractHelper
{
    const HUBINTEGRATION_MODULE_VERSION = '2.1.9';
    const REQUIRED_HUBINTEGRATION_MODULE_VERSION = '2.0.15';
    const DEVELOPER_CLIENT_ID = '8d71cc2f-4fbc-4bde-819d-8c17d3fa69fa';
    const DEVELOPER_SECRET_ID = 'a3650c54-908c-4bcf-a996-f6907b357607';
    const STORE_ID = 'default';
    const STORE_LABEL = 'Makewebbetter';
    const ADMIN_ROUTE_PATH = 'adminhtml';
    const BULK_EXPORT_START_TIME = 'bulk_export_start_time';
    const BULK_EXPORT_END_TIME = 'bulk_export_end_time';
    const BULK_EXPORT_INCLUDE_QUOTE = 'bulk_export_include_quote';
    const DEFAULT_CONTACT_LIST_COUNT = 50;
    const PIPELINE_LABEL = 'Ecommerce Pipeline';

    /**
     * Base url of hubSpot api.
     */
    private $baseUrl = "https://api.hubapi.com/";

    /**
     * @var HubConfig
     */
    public $resourceConfig;

    /**
     * @var TypeListInterface
     */
    public $cache;

    /**
     * @var mixed|null
     */
    public $connectionEstablished;

    /**
     * @var int
     */
    public $exportStartTime = 0;

    /**
     * @var int
     */
    public $exportEndTime = 0;

    /**
     * @var mixed|null
     */
    public $accessToken;

    /**
     * @var ResourceConnection
     */
    public $resource;

    /**
     * @var Properties
     */
    public $properties;

    /**
     * @var BackendUrl
     */
    private $backendUrl;

    /**
     * @var ErrorLogFactory
     */
    private $errorLogFactory;

    /**
     * @var array
     */
    private $allContactProperties = [];

    /**
     * @var array
     */
    private $allContactPropertyGroups = [];

    /**
     * @var array
     */
    private $allDealPropertyNames = [];

    /**
     * @var array
     */
    private $allMakewebbetterWorkflows = [];

    /**
     * @var array
     */
    private $availableContactLists = [];

    /**
     * @var int
     */
    private $contactListsOffset = 0;

    /**
     * @var Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory
     */
    public $statusCollectionFactory;
    /**
     * @var Ecomdetails $ecomdetails
     */
    public $ecomdetails;

    /**
     * @var HubHelper
     */
    public $hubHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $dateTime;

    /**
     * ConnectionManager constructor.
     * @param Context $context
     * @param HubConfig $resourceConfig
     * @param TypeListInterface $cache
     * @param ResourceConnection $resource
     * @param BackendUrl $backendUrl
     * @param ErrorLogFactory $errorLogFactory
     * @param Properties $properties
     * @param Ecomdetails $ecomdetails
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory
     * @param \Makewebbetter\HubIntegration\Helper\Data $hubHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
 */
    public function __construct(
        Context $context,
        HubConfig $resourceConfig,
        TypeListInterface $cache,
        ResourceConnection $resource,
        BackendUrl $backendUrl,
        ErrorLogFactory $errorLogFactory,
        Properties $properties,
        Ecomdetails $ecomdetails,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory,
        HubHelper $hubHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime

    ) {
        $this->resourceConfig = $resourceConfig;
        $this->cache = $cache;
        $this->resource = $resource;
        $this->backendUrl = $backendUrl;
        $this->connectionEstablished = $this->getHubConfig('connection_established');
        $this->accessToken = $this->getHubConfig('access_token');
        $this->errorLogFactory = $errorLogFactory;
        $this->properties = $properties;
        $this->ecomdetails = $ecomdetails;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->hubHelper = $hubHelper;
        $this->dateTime = $dateTime;
        parent::__construct($context);
    }

    /**
     * Validating OAuth 2.0.
     * @since 1.0.0
     */
    public function hubspotValidateOauthToken()
    {
        if ($this->getHubConfig('oauth_is_valid')) {
            if ($this->isAccessTokenExpired()) {
                return $this->hubspotRefreshToken();
            } else {
                return true;
            }
        }
        return false;
    }

    public function getHubIntegrationModuleVersion(){
        $HubIntegrationVersion = self::HUBINTEGRATION_MODULE_VERSION;
        return $HubIntegrationVersion;
    }
    public function getRequiredHubIntegrationModuleVersion(){
        $requiredHubIntegrationVersion = self::REQUIRED_HUBINTEGRATION_MODULE_VERSION;
        return $requiredHubIntegrationVersion;
    }

    /**
     * Refreshing access token from refresh token.
     * @return bool
     */
    public function hubspotRefreshToken()
    {
        $mwbUrl = 'https://auth.makewebbetter.com/integration/hubspot-auth/';
        $redirectUrl = '';
        $tokeGenerated = $this->getHubConfig( 'static_redirect_url_new');
        if($tokeGenerated == 'yes'){
            $redirectUrl = $mwbUrl;
        } else{
            $redirectUrl = $this->getHubConfig('redirect_url');
        }
        $endpoint = 'oauth/v1/token';
        $refresh_token = $this->getHubConfig('refresh_token');
        $data = [
            'grant_type' => 'refresh_token',
            'client_id' => self::DEVELOPER_CLIENT_ID,
            'client_secret' => self::DEVELOPER_SECRET_ID,
            'refresh_token' => $refresh_token,
            'redirect_uri' => $redirectUrl
        ];
        $body = http_build_query($data);
        return $this->hubspotOauthPostApi($endpoint, $body, 'refresh');
    }

    /**
     * Fetching access token from code.
     * @param $code
     * @return bool
     */
    public function hubspotFetchAccessTokenFromCode($code)
    {
        $endpoint = 'oauth/v1/token';
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => self::DEVELOPER_CLIENT_ID,
            'client_secret' => self::DEVELOPER_SECRET_ID,
            'code' => $code,
            'redirect_uri' => 'https://auth.makewebbetter.com/integration/hubspot-auth/'
        ];
        $body = http_build_query($data);
        return $this->hubspotOauthPostApi($endpoint, $body, 'access');
    }

    /**
     * post api for oauth access and refresh token.
     * @param $endpoint
     * @param $body
     * @param $action
     * @return bool
     */
    public function hubspotOauthPostApi($endpoint, $body, $action)
    {
        $headers = ['Content-Type: application/x-www-form-urlencoded;charset=utf-8'];
        if ($action == 'refresh') {
            $access_token = $this->getHubConfig('access_token');
            $headers = ['Authorization: Bearer ' . $access_token];
        }
        $response = $this->_post($endpoint, $body, $headers);

        if ($response) {
            $status_code = $response['status_code'];
            $api_body = json_decode($response['response'], true);
            if ($status_code == 200) {
                if (isset($api_body['refresh_token']) && isset($api_body['access_token']) && $api_body['expires_in']) {
                    $accountInfo = $this->getRefreshTokenInfo($api_body['refresh_token']);
                    $hubId = 0;
                    if ($accountInfo !== null) {
                        $hubUserData = json_decode($accountInfo, true);
                        if (isset($hubUserData['hub_id'])) {
                            $hubId = $hubUserData['hub_id'];
                        }
                    }
                    $keyValues = [
                        'access_token' => $api_body['access_token'],
                        'refresh_token' => $api_body['refresh_token'],
                        'token_expiry' => time() + $api_body['expires_in'] - 5,
                        'account_info' => $accountInfo,
                        'hub_id' => $hubId,
                        'oauth_is_valid' => true
                    ];

                    $this->accessToken = $api_body['access_token'];
                    foreach ($keyValues as $key => $value) {
                        $this->setHubConfig($key, $value);
                    }
                    return true;
                }
            }
        }
        $this->setHubConfig('oauth_valid', false);
        return false;
    }

    /**
     * Fetching refresh token Information.
     * @param $token
     * @return mixed|null
     */
    public function getRefreshTokenInfo($token)
    {
        $endpoint = 'oauth/v1/refresh-tokens/'.$token;
        $headers = ['Content-Type: application/json'];
        $data = $this->_get($endpoint, $headers);
        if (isset($data['response']) && $data['status_code'] == 200) {
            return $data['response'];
        }
        return null;
    }

    /**
     * @return string
     */
    public function getAdminUrl() {
        return $this->backendUrl->getUrl(self::ADMIN_ROUTE_PATH);
    }

 

    /**
     * Get requests to HubSpot.
     * @param $endpoint
     * @param $headers
     * @return array
     */
    public function _get($endpoint, $headers)
    {
        try{
            $url = $this->baseUrl . $endpoint;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errors = curl_error($ch);
            curl_close($ch);
            $this->createLog($status_code, $url, $response, RequestType::REQUEST_TYPE_GET, []);
        } catch (\Exception $e) {
            $this->createLog(
                400,
                'exception',
                [
                    'request_method' => '_get',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );
            $status_code = 400;
            $response = '';
            $curl_errors = '';
        }
        return ['status_code' => $status_code, 'response' => $response, 'errors' => $curl_errors];
    }

    /**
     * send post and format the response to HubSpot.
     * @param $endpoint
     * @param $post_params
     * @param $headers
     * @return array
     */
    public function _post($endpoint, $post_params, $headers)
    {
        try{
            $url = $this->baseUrl . $endpoint;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errors = curl_error($ch);
            curl_close($ch);
            $this->createLog($status_code, $url, $response, RequestType::REQUEST_TYPE_POST, $post_params);
        } catch (\Exception $e) {
            $this->createLog(
                400,
                'exception',
                [
                    'request_method' => '_post',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );
            $status_code = 400;
            $response = '';
            $curl_errors = '';
        }
        return ['status_code' => $status_code, 'response' => $response, 'errors' => $curl_errors];
    }

public function _delete($endpoint, $headers): array
    {
        try{
            $url = $endpoint;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, value: $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errors = curl_error($ch);
            curl_close($ch);
            $this->createLog($status_code, $url, $response, RequestType::REQUEST_TYPE_DELETE);
        } catch (\Exception $e) {
            $this->createLog(
                400,
                'exception',
                [
                    'request_method' => 'DELETE',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );
            $status_code = 400;
            $response = '';
            $curl_errors = '';
        }
        return ['status_code' => $status_code, 'response' => $response, 'errors' => $curl_errors];
    }

    /**
     * send post and format the response to HubSpot.
     * @param $endpoint
     * @param $post_params
     * @param $headers
     * @return array
     */
    public function _put($endpoint, $post_params, $headers)
    {
        try{
            $url = $this->baseUrl . $endpoint;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errors = curl_error($ch);
            curl_close($ch);
            $this->createLog($status_code, $url, $response, RequestType::REQUEST_TYPE_PUT , $post_params);
        } catch (\Exception $e) {
            $this->createLog(
                400,
                'exception',
                [
                    'request_method' => '_put',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );
            $status_code = 400;
            $response = '';
            $curl_errors = '';
        }
        return ['status_code' => $status_code, 'response' => $response, 'errors' => $curl_errors];
    }

    /**
     * create customer group on HubSpot.
     * @param $groupDetails
     * @return array
     */
    public function createGroup($groupDetails)
    {
        if (is_array($groupDetails)) {
            if (isset($groupDetails['name']) && isset($groupDetails['displayName'])) {
                if ($this->isGroupExist($groupDetails['name'])) {
                    return $this->updateGroup($groupDetails);
                }
                $url = 'properties/v1/contacts/groups';
                $this->hubspotValidateOauthToken();
                $access_token = $this->getHubConfig('access_token');
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ];
                $groupDetails = json_encode($groupDetails);
                return $this->_post($url, $groupDetails, $headers);
            }
        }
    }

    /**
     * update customer group on HubSpot.
     * @param $groupDetails
     * @return array
     */
    public function updateGroup($groupDetails)
    {
        if (is_array($groupDetails)) {
            if (isset($groupDetails['name']) && isset($groupDetails['displayName'])) {
                $url = 'properties/v1/contacts/groups/named/' . $groupDetails['name'];
                $this->hubspotValidateOauthToken();
                $access_token = $this->getHubConfig('access_token');
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ];
                $groupDetails = json_encode($groupDetails);
                return $this->_put($url, $groupDetails, $headers);
            }
        }
    }

    /**
     * Get Customer group from HubSpot by name
     * @param string $name
     * @return array
     */
    public function getGroup($name = '') {
        if ($name) {
            $url = 'properties/v1/contacts/groups/named/' . $name;
            $this->hubspotValidateOauthToken();
            $access_token = $this->getHubConfig('access_token');
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token
            ];
            return $this->_get($url, $headers);
        }
    }

    /**
     * Get All Customer property groups from HubSpot
     * @return array|mixed
     */
    public function getAllContactGroups() {
        $groups = [];
        $url = '/properties/v1/contacts/groups';
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $data = $this->_get($url, $headers);
        if (isset($data['response']) && $data['status_code'] == 200) {
            $groups = json_decode($data['response'], true);
        }
        return $groups;
    }

    /**
     * @return array
     */
    public function getContactGroupNames() {
        if (count($this->allContactPropertyGroups) == 0) {
            foreach ($this->getAllContactGroups() as $property) {
                if (isset($property['name']) && $property['name']) {
                    $this->allContactPropertyGroups[] = $property['name'];
                }
            }
        }
        return $this->allContactPropertyGroups;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isGroupExist($name = '') {
        if ($name) {
            $groupNames = $this->getContactGroupNames();
            if (in_array($name, $groupNames)) {
                return true;
            }
        }
        return false;
    }

    /**
     * create customer property on HubSpot.
     * @param $propDetails
     * @return array
     */
    public function createContactProperty($propDetails)
    {
        if (is_array($propDetails)) {
            if (isset($propDetails['name']) && isset($propDetails['groupName'])) {
                if ($this->isContactPropertyExist($propDetails['name'])) {
                    return $this->updateContactProperty($propDetails);
                }
                $url = 'properties/v1/contacts/properties';
                $this->hubspotValidateOauthToken();
                $access_token = $this->getHubConfig('access_token');
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ];
                $propDetails = json_encode($propDetails);
                return $this->_post($url, $propDetails, $headers);
            }
        }
    }

    /**
     * update customer property on HubSpot.
     * @param $propDetails
     * @return array
     */
    public function updateContactProperty($propDetails)
    {
        if (is_array($propDetails)) {
            if (isset($propDetails['name']) && isset($propDetails['groupName'])) {
                $url = 'properties/v1/contacts/properties/named/' . $propDetails['name'];
                $this->hubspotValidateOauthToken();
                $access_token = $this->getHubConfig('access_token');
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ];
                $propDetails = json_encode($propDetails);
                return $this->_put($url, $propDetails, $headers);
            }
        }
    }

    /**
     * Get Customer property from HubSpot by name
     * @param string $name
     * @return array
     */
    public function getContactPropertyByName($name = '') {
        if ($name) {
            $url = 'properties/v1/contacts/properties/named/' . $name;
            $this->hubspotValidateOauthToken();
            $access_token = $this->getHubConfig('access_token');
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token
            ];
            return $this->_get($url, $headers);
        }
    }

    /**
     * Get All Customer properties from HubSpot
     * @return array|mixed
     */
    public function getContactProperties() {
        $properties = [];
        $url = 'properties/v1/contacts/properties';
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $data = $this->_get($url, $headers);
        if (isset($data['response']) && $data['status_code'] == 200) {
            $properties = json_decode($data['response'], true);
        }
        return $properties;
    }

    /**
     * @return array
     */
    public function getContactPropertyNames() {
        if (count($this->allContactProperties) == 0) {
            foreach ($this->getContactProperties() as $property) {
                if (isset($property['name']) && $property['name']) {
                    $this->allContactProperties[] = $property['name'];
                }
            }
        }
        return $this->allContactProperties;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isContactPropertyExist ($name = '') {
        if ($name) {
            $propertyNames = $this->getContactPropertyNames();
            if (in_array($name, $propertyNames)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get list of smart list
     * @return array
     */
    public function getStaticList()
    {
        $lists = [];
        $lists["select"] = "--Please Select a Static List--";
        $url = '/contacts/v1/lists/static?count=250';
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $response = $this->_get($url, $headers);
        if (isset($response['response'])) {
            $response = json_decode($response['response']);
        }
        if (!empty($response->lists)) {
            foreach ($response->lists as $single_list) {
                if (isset($single_list->name) && isset($single_list->listId)) {
                    $lists[$single_list->listId] = $single_list->name;
                }
            }
        }
        return $lists;
    }

    /**
     * @param $email
     * @param $list_id
     * @return array
     */
    public function listEnrollment($email, $list_id)
    {
        if (!empty($email) && !empty($list_id)) {
            $url = '/contacts/v1/lists/' . $list_id . '/add';
            $this->hubspotValidateOauthToken();
            $access_token = $this->getHubConfig('access_token');
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token
            ];
            $emails = [];
            $emails[] = $email;
            $request = ["emails" => $emails];
            $request = json_encode($request);
            return $this->_post($url, $request, $headers);
        }
    }

    /**
     * @param array $lists
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkAndUpdateContactListIdsInConfig($lists = []) {
        if (!count($lists))
            $lists = $this->properties->getAllLists();
        $contactListsNames = array_column($lists, 'name');
        $this->resourceConfig->deleteContactListsFromConfig();
        $this->getAvailableContactListsInHubSpot($contactListsNames);
        return true;
    }

    /**
     * @param $contactListsNames
     * @return bool
     */
    public function getAvailableContactListsInHubSpot($contactListsNames) {
        $url = 'contacts/v1/lists?count=' . self::DEFAULT_CONTACT_LIST_COUNT;
        if ($this->contactListsOffset)
            $url .= '&offset=' . $this->contactListsOffset;
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $data = $this->_get($url, $headers);
        if (isset($data['response']) && $data['status_code'] == 200) {
            $lists = json_decode($data['response'], true);
            if (isset($lists['offset']) && $lists['offset'])
                $this->contactListsOffset = $lists['offset'];
            if (isset($lists['lists']) && count($lists['lists'])) {
                foreach ($lists['lists'] as $list) {
                    if (in_array($list['name'], $contactListsNames)) {
                        $this->availableContactLists[$list['name']] = $list['listId'];
                        $path = 'hubspot/lists/'.$list['name'];
                        $this->properties->setUserOption($path, $list['listId']);
                    }
                }
            }
            if (isset($lists['has-more']) &&
                $lists['has-more'] == true &&
                (count($this->availableContactLists) < count($contactListsNames))
            ) {
                $this->getAvailableContactListsInHubSpot($contactListsNames);
            }
        }
        return true;
    }

    /**
     * create / update contact list on HubSpot.
     * @param $listDetails
     * @return array
     */
    public function createContactList($listDetails)
    {
        if (is_array($listDetails)) {
            if (isset($listDetails['name'])) {
                $url = 'contacts/v1/lists';
                if ($listId = $this->isContactListExist($listDetails['name'])) {
                    $url = 'contacts/v1/lists/' . $listId;
                }
                $this->hubspotValidateOauthToken();
                $access_token = $this->getHubConfig('access_token');
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ];
                $listDetails = json_encode($listDetails);
                return $this->_post($url, $listDetails, $headers);
            }
        }
    }

    /**
     * Get contact list from HubSpot
     * @return array|mixed
     */
    public function getContactListById($listId) {
        $contactList = [];
        $url = '/contacts/v1/lists/' . $listId;
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $data = $this->_get($url, $headers);
        if (isset($data['response']) && $data['status_code'] == 200) {
            $contactList = json_decode($data['response'], true);
        }
        return $contactList;
    }

    /**
     * @param $name
     * @return string|null
     */
    public function getContactListIdByName($name) {
        return $this->getContactListConfig($name);
    }

    /**
     * @param string $name
     * @return bool|mixed|null
     */
    public function isContactListExist ($name = '') {
        if ($name) {
            $listId = $this->getContactListIdByName($name);
            if ($listId) {
                $contactList = $this->getContactListById($listId);
                if (count($contactList)) {
                    return $listId;
                }
            }
        }
        return false;
    }

    /**
     * create workflow on hubspot.
     * @return array
     * @since 1.0.0
     */
    public function createWorkflow($workflow_details)
    {
        if (is_array($workflow_details)) {
            if (isset($workflow_details['name'])) {
                if (!$this->isWorkflowExist($workflow_details['name'])) {
                    $url = 'automation/v3/workflows';
                    $this->hubspotValidateOauthToken();
                    $access_token = $this->getHubConfig('access_token');
                    $headers = [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $access_token
                    ];
                    $workflow = json_encode($workflow_details);
                    return $this->_post($url, $workflow, $headers);
                }
            }
        }
        return [];
    }

    /**
     * Get list of All workFlows
     * @return array
     */
    public function getWorkflows()
    {
        $workflows = [];
        $workflows["select"] = "--Please Select a Workflow--";
        $url = 'automation/v3/workflows';
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $response = $this->_get($url, $headers);
        if (isset($response['response'])) {
            $response = json_decode($response['response']);
        }
        if (!empty($response->workflows)) {
            foreach ($response->workflows as $single_workflow) {
                if (isset($single_workflow->name) && isset($single_workflow->id)) {
                    $workflows[$single_workflow->id] = $single_workflow->name;
                }
            }
        }
        return $workflows;
    }

    /**
     * @return array
     */
    public function getWorkflowNames() {
        if (count($this->allMakewebbetterWorkflows) == 0) {
            $allWorkflows = $this->getWorkflows();
            if (isset($allWorkflows['select'])) {
                unset($allWorkflows['select']);
            }
            $this->allMakewebbetterWorkflows = $allWorkflows;
        }
        return $this->allMakewebbetterWorkflows;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isWorkflowExist ($name = '') {
        if ($name) {
            $workflowNames = $this->getWorkflowNames();
            if (in_array($name, $workflowNames)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $email
     * @param $workflow_id
     * @return array
     */
    public function workflowEnrollment($email, $workflow_id)
    {
        if (!empty($email) && !empty($workflow_id)) {
            $url = 'automation/v2/workflows/' . $workflow_id . '/enrollments/contacts/' . $email;
            $this->hubspotValidateOauthToken();
            $access_token = $this->getHubConfig('access_token');
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token
            ];
            return $this->_post($url, [], $headers);
        }
    }

    /**
     * getting all HubSpot properties.
     * @since 1.0.0
     */
    public function getAllHubspotProperties()
    {
        $url = 'crm/v3/properties/contacts';
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        return $this->_get($url, $headers);
    }

     public function getAllHubspotProductProperties()
    {
        $url = 'crm/v3/properties/products';
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        return $this->_get($url, $headers);
    }
    public function getAllHubspotDealProperties()
    {
        $url = 'crm/v3/properties/deals';
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        return $this->_get($url, $headers);
    }

    /**
     * create deal group on HubSpot.
     * @param $dealGroups
     * @return array
     */
    public function createDealGroup($dealGroups)
    {
        $url = '/properties/v1/deals/groups/';
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $dealGroups = json_encode($dealGroups);
        return $this->_post($url, $dealGroups, $headers);
    }

    /**
     * create deal property on HubSpot.
     * @param $propDetails
     * @return array
     */
    public function createDealProperty($propDetails)
    {
        if (is_array($propDetails)) {
            if (isset($propDetails['name']) && isset($propDetails['groupName'])) {
                if ($this->isDealPropertyExist($propDetails['name'])) {
                    return $this->updateDealProperty($propDetails);
                }
                $url = '/properties/v1/deals/properties/';
                $this->hubspotValidateOauthToken();
                $access_token = $this->getHubConfig('access_token');
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ];
                $propDetails = json_encode($propDetails);
                return $this->_post($url, $propDetails, $headers);
            }
        }
    }

    /**
     * update deal property on HubSpot.
     * @param $propDetails
     * @return array
     */
    public function updateDealProperty($propDetails)
    {
        if (is_array($propDetails)) {
            if (isset($propDetails['name']) && isset($propDetails['groupName'])) {
                $url = '/properties/v1/deals/properties/named/' . $propDetails['name'];
                $this->hubspotValidateOauthToken();
                $access_token = $this->getHubConfig('access_token');
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ];
                $propDetails = json_encode($propDetails);
                return $this->_put($url, $propDetails, $headers);
            }
        }
    }

    /**
     * Get Deal property from HubSpot by name
     * @param string $name
     * @return array
     */
    public function getDealPropertyByName($name = '') {
        if ($name) {
            $url = '/properties/v1/deals/properties/named/' . $name;
            $this->hubspotValidateOauthToken();
            $access_token = $this->getHubConfig('access_token');
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token
            ];
            return $this->_get($url, $headers);
        }
    }

    /**
     * Get All Deal properties from HubSpot
     * @return array|mixed
     */
    public function getAllDealProperties() {
        $properties = [];
        $url = '/properties/v1/deals/properties/';
        $this->hubspotValidateOauthToken();
        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $data = $this->_get($url, $headers);
        if (isset($data['response']) && $data['status_code'] == 200) {
            $properties = json_decode($data['response'], true);
        }
        return $properties;
    }

    /**
     * @return array
     */
    public function getDealPropertyNames() {
        if (count($this->allDealPropertyNames) == 0) {
            foreach ($this->getAllDealProperties() as $property) {
                if (isset($property['name']) && $property['name']) {
                    $this->allDealPropertyNames[] = $property['name'];
                }
            }
        }
        return $this->allDealPropertyNames;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isDealPropertyExist ($name = '') {
        if ($name) {
            $dealProperties = $this->getDealPropertyNames();
            if (in_array($name, $dealProperties)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param $days
     * @return int
     */
    public function deleteErrorLog($days) {
        $errorLogDeleteTime = date('Y-m-d H:i:s' , strtotime(date('Y-m-d H:i:s'). ' -'.$days.' days'));
        $connection = $this->resource->getConnection();
        $makewebbetterErrorLogTable = $this->resource->getTableName('hub_makewebbetter_error_log');
        $effectedRowsCount = $connection->delete($makewebbetterErrorLogTable, ['created_at < ?' => $errorLogDeleteTime]);
        return $effectedRowsCount;
    }

    /**
     * check if access token is expired.
     * @return boolean [description]
     */
    public function isAccessTokenExpired()
    {
        $get_expiry = $this->getHubConfig('token_expiry');
        if ($get_expiry) {
            $current_time = time();
            if ($current_time > $get_expiry) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $keyValue
     */
    public function setHubConfig($field, $value)
    {
        if (!$field) {
            return null;
        }
        $path = 'hub_integration/hubspot_integration/' . $field;
        return $this->resourceConfig->saveConfig($path, $value);
    }

    /**
     * Get HubSpot Related Setting From Core Config Data
     * @param $key
     * @param null $defaultValue
     * @return mixed|null
     */
    public function getHubConfig($field)
    {
        if (!$field) {
            return null;
        }
        $path = 'hub_integration/hubspot_integration/' . $field;
        return $this->resourceConfig->getConfigValue($path);
    }

    /**
     * Get Contact List Related Setting From Core Config Data
     * @param $field
     * @return string|null
     */
    public function getContactListConfig($field)
    {
        if (!$field) {
            return null;
        }
        $path = 'hubspot/lists/' . $field;
        return $this->resourceConfig->getConfigValue($path);
    }

    /**
     * create log of requests.
     *
     * @param  string $responseCode log status_code.
     * @param  string $url acceptable url.
     * @param  string $response
     * @since 1.0.0
     */
    public function createLog($responseCode, $url, $response, $requestType, $params = [])
    {
        $allowedStatus = [200, 201, 202, 204, 207];
        if (!in_array($responseCode, $allowedStatus)) {
            $response = is_array($response) ? json_encode($response) : $response;
            $params = is_array($params) ? json_encode($params) : $params;
            $this->errorLogFactory->create()
                ->setRequestUrl($url)
                ->setRequestType($requestType)
                ->setRequestParams($params)
                ->setResponseCode($responseCode)
                ->setResponse($response)
                ->save();
        }
    }

    /**
     * Add the array of arguments in url to prepare query string.
     *
     * @param  array $args arguments array.
     * @param  string $url request_uri.
     * @return string $query query_string.
     * @since 1.0.0
     */
    public function addQueryArguments($args, $url)
    {
        $query = [];
        foreach ($args as $key => $value) {
            $query[] = $key . '=' . $value;
        }
        if (!empty($query)) {
            $response = $url . '?' . implode('&', $query);
        } else {
            $response = $url;
        }
        return $response;
    }

    /**
     * Clear magento cache
     */
    public function cleanCache()
    {
        $cacheType = [
            \Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER,
            \Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER,
            'vertex',
        ];
        foreach ($cacheType as $cache) {
            $this->cache->cleanType($cache);
        }
    }


    /**
     * create update pipeline.
     *
     */
    public function createUpdatePipeline(){
        $pipeline_label = self::PIPELINE_LABEL;
        $only_stages = true;
        $dealPipelines = $this->fetchAllDealPipelines();
        $fetched_pipeline   = array();
        if (($dealPipelines['results']) != null) {
                array_map(
                    function( $single_pipeline ) use ( $pipeline_label, &$fetched_pipeline, $only_stages ) {

                        if ( $single_pipeline['label'] == $pipeline_label ) {

                            $fetched_pipeline = $only_stages ? $single_pipeline['stages'] : $single_pipeline;

                            $pipeline_id = $single_pipeline['id'];

                            $this->setHubConfig( 'hub_ecomm_pipeline_id', $pipeline_id );
                            if( 36 > strlen( $pipeline_id ) ) {
                                $this->setHubConfig( 'hub_ecomm_pipeline_created', 1 );
                            }
                        }
                    },
                    $dealPipelines['results']
                );
        }

        if(empty($fetched_pipeline)) {

            $response = $this->createDealPipeline($this->ecomdetails->getPipelineDetails());

            if( 201 == $response['status_code'] ) {
                $all_deal_pipelines = $this->fetchAllDealPipelines();

                array_map(
                    function( $single_pipeline ) use ( $pipeline_label, &$fetched_pipeline, $only_stages ) {

                        if ( $single_pipeline['label'] == $pipeline_label ) {

                            $fetched_pipeline = $only_stages ? $single_pipeline['stages'] : $single_pipeline;

                            $this->setHubConfig( 'hub_ecomm_pipeline_id', $single_pipeline['id'] );
                        }
                    },
                    $all_deal_pipelines['results']
                );

                $this->setHubConfig( 'hub_ecomm_pipeline_created', 1 );

            }
        }

        if(!empty( $fetched_pipeline )){
            $mapping_with_new_stages = array();
            foreach( $fetched_pipeline as $single_pipeline ) {
                switch ($single_pipeline['label']) {
                    case 'Checkout Abandoned':
                        $mapping_with_new_stages['checkout_abandoned'] = $single_pipeline['id'];
                        break;
                    /*case 'Return':
                        $mapping_with_new_stages['return'] = $single_pipeline['id'];
                        break;*/
                    case 'Processing':
                        $mapping_with_new_stages['processed'] = $single_pipeline['id'];
                        break;
                    case 'Completed':
                        $mapping_with_new_stages['shipped'] = $single_pipeline['id'];
                        break;
                    case 'Refunded/Cancelled':
                        $mapping_with_new_stages['cancelled'] = $single_pipeline['id'];
                        break;
                    case 'Checkout Completed':
                        $mapping_with_new_stages['checkout_completed'] = $single_pipeline['id'];
                        break;
                }
            }

            $this->setHubConfig( 'hub_ecomm_deal_stage_ids', json_encode($mapping_with_new_stages));
            $this->setHubConfig( 'hub_ecomm_final_mapping', json_encode($this->hubDealsMapping()));
            $this->setHubConfig( 'hub_ecomm_default_mapping', json_encode($this->hubDealsMapping()));
            $defaultStagesCheck = $this->setDefaultStageMapping();
        }
        return true;
    }

    /**
     * create update pipeline.
     *
     */

    public function fetchAllDealPipelines(){
        $endpoint = "crm/v3/pipelines/deals";

        $this->hubspotValidateOauthToken();

        $access_token = $this->getHubConfig('access_token');
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];

        $data = $this->_get($endpoint, $headers);

        if (isset($data['response']) && $data['status_code'] == 200) {
            return json_decode($data['response'], true );
        }return null;
    }

    public function hubDealsMapping() {

        $mapping = array();

        $default_dealstages = $this->ecomdetails->dealsMapping();

        if($this->getHubConfig( 'hub_ecomm_pipeline_created')) {
            $new_stages = json_decode($this->getHubConfig( 'hub_ecomm_deal_stage_ids'), true);
            foreach( $default_dealstages as $key => $value ) {
                $default_dealstages[$key] = $new_stages[$value];
            }
        }

        $mapping = array_map(
            function( $order_status ) use ( $default_dealstages ) {
                $mapped_data['status'] = $order_status;

                if ( array_key_exists( $order_status, $default_dealstages ) ) {
                    $mapped_data['deal_stage'] = $default_dealstages[ $order_status ];
                } else {
                    $mapped_data['deal_stage'] = 'checkout_completed';
                }
                return $mapped_data;
            },
            array_keys( $this->ecomdetails->magentoOrderStatus() )
        );
        return $mapping;
    }


    public function createDealPipeline($pipelineDetails){
        if ( is_array( $pipelineDetails ) ) {
            $endpoint      = 'crm/v3/pipelines/deals';

            $this->hubspotValidateOauthToken();

            $access_token = $this->getHubConfig('access_token');
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token
            ];

            $pipelineDetails = json_encode( $pipelineDetails );

            $dealPipeline =$this->_post($endpoint, $pipelineDetails, $headers);

            return $dealPipeline;
        }
    }

    public function getObjectFromHubSpot($method, $endpoints, $searchData = ''){

        $this->hubspotValidateOauthToken();
        if ($this->accessToken) {
            $access_token = $this->accessToken;
        } else {
            $access_token = $this->getHubConfig('access_token');
        }
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        if ($method == "POST") {
            return $this->_post($endpoints, json_encode($searchData), $headers);
        }
        if ($method == "GET") {
            return $this->_get($endpoints, $headers);
        }

    }

    public function exportObjectToHubSpot($method, $batch , $endpints, $inputs){
        $this->hubspotValidateOauthToken();
        if ($this->accessToken) {
            $access_token = $this->accessToken;
        } else {
            $access_token = $this->getHubConfig('access_token');
        }
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        if ($batch) {
            $requestParams["inputs"] = $inputs;
        }else{
            $requestParams = $inputs;
        }

        if ($method == "POST") {
            return $this->_post($endpints, json_encode($requestParams), $headers);
        }

    }

    /**
     * Sync Deal on hubSpot.
     * @param $objectstype
     * @param $HubDealId
     * @param $lineItemId
     * @param $associationtypeId
     * @return array
     */

    public function associateDealtoObject($objectstype, $HubDealId, $lineItemId, $associationtypeId){

        $this->hubspotValidateOauthToken();
        if ($this->accessToken) {
            $access_token = $this->accessToken;
        } else {
            $access_token = $this->getHubConfig('access_token');
        }
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $messages = [];

        $url = "crm/v3/objects/deals/".$HubDealId."/associations/".$objectstype."/".$lineItemId."/".$associationtypeId;

        return $this->_put($url, json_encode($messages), $headers);
    }

    /**
     * @param $dealId
     * @return array
     */


    public function qetAssocitedObjects($dealId, $object_type) {
        $this->hubspotValidateOauthToken();

        if ($this->accessToken) {
            $access_token = $this->accessToken;
        } else {
            $access_token = $this->getHubConfig('access_token');
        }
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];

        $endpoint = 'crm/v3/objects/deals/'.$dealId.'/associations/'.$object_type;
        $data = $this->_get($endpoint, $headers);
        if (isset($data['response']) && $data['status_code'] == 200) {
            return json_decode($data['response']);
        }

        return null;
    }

    /**
     * @param $dealId
     * @return array
     */

    public function removeContact($contactId, $dealId) {
        $this->hubspotValidateOauthToken();

        if ($this->accessToken) {
            $access_token = $this->accessToken;
        } else {
            $access_token = $this->getHubConfig('access_token');
        }
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];
        $endpoint = $this->baseUrl .'deals/v1/deal/'.$dealId.'/associations/CONTACT?id='.$contactId;
        try{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errors = curl_error($ch);
            $this->createLog($status_code, $endpoint, $response, RequestType::REQUEST_TYPE_PUT);
        } catch (\Exception $e) {
            $this->createLog(
                400,
                'exception',
                [
                    'request_method' => 'delete',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );

            $status_code = 400;
            $response = '';
            $curl_errors = '';
        }
        return ['status_code' => $status_code, 'response' => $response, 'errors' => $curl_errors];
    }

    /**
     * @param $lineItems
     * @return array
     */

    public function removeQuotesLineItems($lineItems) {

        $this->hubspotValidateOauthToken();

        if ($this->accessToken) {
            $access_token = $this->accessToken;
        } else {
            $access_token = $this->getHubConfig('access_token');
        }
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];

        $endpoint = 'crm-associations/v1/associations/delete-batch';
        return $this->_put($endpoint, json_encode($lineItems), $headers);
    }


    public function delQuotesProductsHubSpot($endpoint) {

        $this->hubspotValidateOauthToken();

        if ($this->accessToken) {
            $access_token = $this->accessToken;
        } else {
            $access_token = $this->getHubConfig('access_token');
        }
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];

        $endpoint = $this->baseUrl .$endpoint;
        try{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errors = curl_error($ch);
            $this->createLog($status_code, $endpoint, $response, RequestType::REQUEST_TYPE_PUT);
        } catch (\Exception $e) {
            $this->createLog(
                400,
                'exception',
                [
                    'request_method' => 'delete',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );
            $status_code = 400;
            $response = '';
            $curl_errors = '';
        }
        return ['status_code' => $status_code, 'response' => $response, 'errors' => $curl_errors];
    }

    /**
     * Get status options
     *
     * @return array
     */
    public function getmagentoOrderStatus()
    {
        $options = $this->statusCollectionFactory->create()->toOptionArray();
        $orderstatus = [];
        foreach($options as $status){
            $orderstatus[$status['value']] = $status['label'];
        }
        $orderstatus['checkout_abandoned'] = 'Checkout Abandoned';
        return $orderstatus;
    }

    public function getEcommerceDefaultStages(){

        $magentoOrderStatus = $this->getmagentoOrderStatus();
        $default_dealstages = [];
        foreach($magentoOrderStatus as $key => $value){
            switch($key) {

                case 'checkout_abandoned':
                    $default_dealstages[$key] = 'Checkout Abandoned';
                    break;
                case 'processing':
                    $default_dealstages[$key] = 'Processed';
                    break;
                case 'pending':
                    $default_dealstages[$key] = 'Checkout Completed';
                    break;
                case 'complete':
                    $default_dealstages[$key] = 'Shipped';
                    break;
                default:
                    $default_dealstages[$key] = 'Cancelled';
            }
        }
        return $default_dealstages;
    }

    /**
     * @return array
     */

    public function getHubPipelineDetails(){
        $hubPipelineDetails = $this->fetchAllDealPipelines();
        return $hubPipelineDetails;
    }
    public function setDefaultStageMapping(){
        $defaultStageCheck = [];
        $magentoOrderStatus =  $this->getmagentoOrderStatus();
        $defaultStageMapping = json_decode($this->getHubConfig( 'hub_ecomm_default_mapping'), true);
        foreach($defaultStageMapping as $key => $value){
            $dkey = '';
            $dvalue = '';
            foreach($value as $pkey => $pvalue){
                if($pkey == 'status'){
                    $dkey = $pvalue;
                }else{
                    $dvalue = $pvalue;
                }
            }
            $defaultStageCheck[$dkey] = $dvalue;
        }
        foreach($magentoOrderStatus as $mkey => $mvalue){
            foreach($defaultStageCheck as $hkey => $hvalue){
                if($mkey == 'checkout_abandoned'){
                    $defaultStageCheck[$mkey] = $mkey;
                }
                if($mkey == 'pending' || $mkey == 'new'){
                    $defaultStageCheck[$mkey] = 'checkout_completed';
                }
            }
        }

        foreach($magentoOrderStatus as $mkey => $mvalue){
            foreach($defaultStageCheck as $hkey => $hvalue){
                if(!array_key_exists($mkey,$defaultStageCheck)){
                    $defaultStageCheck[$mkey] = 'cancelled';
                }
            }
        }
        $this->setHubConfig( 'hub_ecomm_default_mapping', json_encode($defaultStageCheck));

        $dealPipelinesresponse = $this->fetchAllDealPipelines();
        $dealPipelinesresponse = $this->getHubPipelineDetails();
        $hubPipelineDetails = [];
        if(!empty($dealPipelinesresponse) && isset($dealPipelinesresponse['results'])){
            $hubPipelineDetails = $dealPipelinesresponse['results'];
        }
        $pipelines = [];
        $pipelinesWithStages = [];
        foreach ($hubPipelineDetails as $pipeline) {
            $pipelines[$pipeline['id']] = $pipeline['label'];
            foreach ($pipeline['stages'] as $key => $stage) {
                $pipelinesWithStages[$pipeline['label']][$stage['id']] = $stage['label'];
            }
        }
        $this->setHubConfig('hub_all_pipeline_detail', json_encode($pipelinesWithStages));
        $this->setHubConfig('hub_all_pipeline_id', json_encode($pipelines));
        return $defaultStageCheck;
    }

    public function getLicenseStatusWithTrailFlag(){
        $status = [];
        $path = \Makewebbetter\HubIntegration\Block\Extensions::LICENSE_STATUS_PATH_PREFIX . 'magento2_makewebbetter_hubintegration';
        $licenseStatusDb = $this->hubHelper->getStoreConfig($path);
        $status['license_status'] = $licenseStatusDb;

        $trialVersionStartDate = $this->hubHelper->getStoreConfig(\Makewebbetter\HubIntegration\Block\Extensions::HASH_PATH_EXTENSION_TRIAL);
        $trialVersionEndDate = $this->dateTime->date('Y-m-d');
        $flag = '';

        $datetime1 = new \DateTime($trialVersionStartDate);
        $datetime2 = new \DateTime($trialVersionEndDate);
        $interval = $datetime1->diff($datetime2);

        if($interval->days <= 15 && $interval->invert !=1){
            $flag = 'Yes';
        }else{
            $flag = 'No';
        }
        $status['trail_status'] = $flag;
        return $status;
    }
    public function getLicenseStatusWithTrailFlagPerModule($moduleName){
        $status = [];
        $path = \Makewebbetter\HubIntegration\Block\Extensions::LICENSE_STATUS_PATH_PREFIX . strtolower($moduleName);
        $licenseStatusDb = $this->hubHelper->getStoreConfig($path);
        $status['license_status'] = $licenseStatusDb;

        $trialVersionStartDate = $this->hubHelper->getStoreConfig(\Makewebbetter\HubIntegration\Block\Extensions::HASH_PATH_EXTENSION_TRIAL);
        $trialVersionEndDate = $this->dateTime->date('Y-m-d');
        $flag = '';

         $datetime1 = new \DateTime($trialVersionStartDate);
        $datetime2 = new \DateTime($trialVersionEndDate);
        $interval = $datetime1->diff($datetime2);
        
        if($interval->days <= 15 && $interval->invert !=1){
            $flag = 'Yes';
        }else{
            $flag = 'No';
        }
        $status['trail_status'] = $flag;
        return $status;
    }

    public function removeConnectorFromHubSpot()
    {
            $url = 'https://api.hubspot.com/appinstalls/v3/external-install';
            $this->hubspotValidateOauthToken();
            $access_token = $this->getHubConfig('access_token');
            $headers = [
                'Authorization: Bearer ' . $access_token
            ];
            
            return $this->_delete($url, $headers);
    }
}
