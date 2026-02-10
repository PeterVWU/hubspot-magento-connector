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

namespace Makewebbetter\HubIntegration\Block;
use Magento\Framework\Serialize\SerializerInterface;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;

/**
 * @method \Magento\Config\Block\System\Config\Form getForm()
 */
class Extensions extends \Magento\Config\Block\System\Config\Form\Fieldset
{
	protected $_dummyElement;
	protected $_fieldRenderer;
	protected $_values;
	protected $_licenseUrl;
	const LICENSE_USE_HTTPS_PATH = 'web/secure/use_in_adminhtml';
	const LICENSE_VALIDATION_URL_PATH = 'https://makewebbetter.com/';
	const HASH_PATH_PREFIX = 'mwb/extensions/extension_';

    const HASH_PATH_EXTENSION_TRIAL = 'mwb/extensions/extension_trial_start_time';
    const LICENSE_STATUS_PATH_PREFIX = 'mwb/extensions/extension_license_status_';
    const LICENSE_STATUS_DOMAIN = 'mwb/extensions/extension_license_status_domain';

	/**
	 * @var SerializerInterface
	 */
	private $serializer;
	/**
     * Application Object Manager
     *
     * @var \Magento\Framework\App\CacheInterface
     */
	protected $_objectManager;

    /**
     * @var \Makewebbetter\HubIntegration\Helper\Data
     */
    protected $helper;

    /**
     * @var \Makewebbetter\HubIntegration\Helper\Feed
     */
    protected $feedHelper;

    /**
     * @var \Magento\Config\Block\System\Config\Form\Field
     */
    protected $formField;

    public $date;
    private $connectionManager;
    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
		\Magento\Backend\Model\Auth\Session $authSession,
		SerializerInterface $serializer,
        \Magento\Framework\ObjectManagerInterface $objectInterface,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Makewebbetter\HubIntegration\Helper\Data $helper,
        \Makewebbetter\HubIntegration\Helper\Feed $feedHelper,
        \Magento\Config\Block\System\Config\Form\Field $formField,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        ConnectionManager $connectionManager,
        array $data = []
    ) {
        $this->_jsHelper = $jsHelper;
		$this->serializer = $serializer;
        $this->_authSession = $authSession;
        $this->_objectManager = $objectInterface;
        $this->helper = $helper;
        $this->feedHelper = $feedHelper;
        $this->formField = $formField;
        $this->date = $date;
        $this->connectionManager = $connectionManager;
        parent::__construct($context, $authSession, $jsHelper);
	    $this->_cacheManager = $this->_cache;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $trialVersionTime = $this->helper->getStoreConfig(self::HASH_PATH_EXTENSION_TRIAL);

        if(empty($trialVersionTime)){
            $presentDate = $this->date->date('Y-m-d');
            $this->feedHelper->setDefaultStoreConfig(self::HASH_PATH_EXTENSION_TRIAL, $presentDate);
        }
        $trialVersionTime = $this->helper->getStoreConfig(self::HASH_PATH_EXTENSION_TRIAL);

		$header = $html = $footer = $script = '';
		$header = $this->_getHeaderHtml($element);
		$modules =  $this->feedHelper->getAllModules();
		$field = $element->addField('extensions_heading', 'note', array(
            'name'  => 'extensions_heading',
            'label' => '<a href="javascript:;"><b>Extension Name (version)</b></a>',
            'text' => '<a href="javascript:;"><b>License Information</b></a>',
            'style'		=> 'padding-top:0;',
		))->setRenderer($this->_getFieldRenderer());
		$html.= $field->toHtml();
        foreach ($modules as $moduleName => $releaseVersion) {
            if (preg_match('/HubCustomisation/i', $moduleName) || preg_match('/HubEmailSubscriberSync/i', $moduleName)) {
                continue;
            }
			$moduleProductName = isset($releaseVersion['parent_product_name']) ? $releaseVersion['parent_product_name'] : '';
			if(!is_array($releaseVersion))
				$releaseVersion = isset($releaseVersion['release_version']) ? $releaseVersion['release_version'] : trim($releaseVersion);
			else
				$releaseVersion = isset($releaseVersion['release_version']) ? $releaseVersion['release_version'] : '';
			$html.= $this->_getFieldHtml($element, $moduleName,$releaseVersion,$moduleProductName);
		}
		if (strlen($html) == 0) {
			$html = '<p>'.$this->__('No records found.').'</p>';
		}
        $footer .= $this->_getFooterHtml($element);
		$script .= $this->_getScriptHtml();
        return $header. $html . $footer . $script;
    }

    protected function _getFieldRenderer()
    {
    	if (empty($this->_fieldRenderer)) {
    		$this->_fieldRenderer = $this->formField;
    	}
    	return $this->_fieldRenderer;
    }

	protected function _getDummyElement()
    {
        if (empty($this->_dummyElement)) {
            $this->_dummyElement = new \Magento\Framework\DataObject(array('show_in_default'=>1, 'show_in_website'=>1));
        }
        return $this->_dummyElement;
    }

	protected function _getFieldHtml($fieldset, $moduleName,$currentVersion = '0.0.1',$moduleProductName = '')
    {
        $data = '';//(string)$this->getForm()->getConfigRoot()->descend($path);
		$name = strlen($moduleProductName) > 0 ? $moduleProductName : $moduleName;
		$releaseVersion = $name.'-'.$currentVersion;
        $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlagPerModule($moduleName);
        $licenseStatusDb = $checkStatus['license_status'];
        $flag = $checkStatus['trail_status'];

        if ($licenseStatusDb == 'ACTIVATED') {
            $keyPath = self::HASH_PATH_PREFIX.strtolower($moduleName);
            $licenseKey = $this->helper->getStoreConfig($keyPath);
            $buttonHtml = '<div><span style="float: left;min-width: 200px;">'.$licenseKey.'</span><span  style="font-family:\'Admin Icons\'; margin-left: 10%;" class="message-success"></span></div>';
            $type = 'label';
            $title = 'License Number';
        }
        else{
            $buttonHtml = '<div style="float: right;"><div style="font-family:\'Admin Icons\';" class="message-success"></div></div>';
            $type = 'label';
            $title = 'License Number';
            if(strlen($data) == 0) {
                $title = __('Enter the valid license after that you have to click on Save Config button.');
                $buttonHtml = '<div style="clear: both; height:0; width:0; ">&nbsp;</div>';
                $buttonHtml .='<p class="note"><span><span style="color:red">You are using trial plan, which has been expired soon! </span>Please fill the valid license number in above field. If you don\'t have license number please <a href="https://makewebbetter.com/product/hubspot-magento-integration/" target="_blank">Get a license number from MakeWebBetter.com</a></span></p>';
                $type = 'text';
            }
        }
        if($flag == 'No' && $licenseStatusDb != 'ACTIVATED'){
            $buttonHtml = '<div style="float: right;"><div style="font-family:\'Admin Icons\';" class="message-success"></div></div>';
            $type = 'label';
            $title = 'License Number';
            if(strlen($data) == 0) {
                $title = __('Enter the valid license after that you have to click on Save Config button.');
                $buttonHtml = '<div style="clear: both; height:0; width:0; ">&nbsp;</div>';
                $buttonHtml .='<p class="note"><span><span style="color:red">Your Trial has been expired!</span> Please fill the valid license number in above field. If you don\'t have license number please <a href="https://makewebbetter.com/product/hubspot-magento-integration/" target="_blank">Get a license number from MakeWebBetter.com</a></span></p>';
                $type = 'text';
            }
        }
        
		$field = $fieldset->addField($moduleName, $type,//this is the type of the element (can be text, textarea, select, multiselect, ...)
			array(
				'name'          => 'groups[extensions][fields][extension_'.strtolower($moduleName).'][value]',//this is groups[group name][fields][field name][value]
				'label'         => $name.' ('.$currentVersion.')',//this is the label of the element
				'value'         => $data,//this is the current value
				'title'			=> $title,
				'class'			=>'validate-mwb-license',
				'style'		=> 'float:left;',

				'after_element_html' => $buttonHtml,
			))->setRenderer($this->_getFieldRenderer());
		return $field->toHtml();
    }

	/**
     * Retrieve local license url
     *
     * @return string
     */
    public function getLicenseUrl()
    {
        if ($this->_licenseUrl===NULL) {
			$secure = false;
			if($this->helper->getStoreConfig(self::LICENSE_USE_HTTPS_PATH)) {
				$secure = true;
			}
			$this->_licenseUrl = $this->getUrl($this->helper->getStoreConfig(self::LICENSE_VALIDATION_URL_PATH),array('_secure'=>$secure));
        }
        return $this->geturl('hubintegration/main/license');
    }

	protected function _getScriptHtml() {
		$script = '<script type="text/javascript">';
		$script.="require([
            'jquery', // jquery Library
            'jquery/ui', // Jquery UI Library
            'jquery/validate', // Jquery Validation Library
            'mage/translate' // Magento text translate (Validation message translte as per language)
            ], function($){
            $.mage.message =  $.mage.__('Please enter a valid License Number.');
            $.validator.addMethod(
            'validate-mwb-license', function (value,licenseElement) {";
            $script .= 'var url = "'.$this->getLicenseUrl().'?"+licenseElement.id+"="+licenseElement.value;';
            $script .= 'var formId = configForm.formId;';
            $script .= 'var ok = false;console.log(licenseElement.id);' ;
            $script .= "jQuery.ajax( {
                url: url,
                type: 'POST',
                showLoader: true,
                data: {form_key:jQuery('input[name=form_key]').val()},
                async:false
            }).done(function(a) {
                var response = a.evalJSON();
                validateTrueEmailMsg = response.message;
                if (response.success == 0) {
                    $.mage.message = validateTrueEmailMsg;
                    alert(validateTrueEmailMsg);
                    ok = false;
                } else {
                    ok = true;
                }
            });
            return ok;
            ";
        $script.="},$.mage.message);});</script>";
        return $script;
	}
}
