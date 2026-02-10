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

use Makewebbetter\HubIntegration\Helper\SimpleXMLElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Url\DecoderInterface;
class Feed extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_allowedFeedType = array();
    protected $_backendConfig;
    protected $_loader;
    protected $_objectManager;
    protected $parser;
    private $moduleList;
    private $moduleResource;
    private $driver;
    protected $_storeManager;
    protected $_scopeConfigManager;
    protected $_configValueManager;
    protected $_transaction;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    /**
     * @param \Magento\Framework\App\Helper\Context            $context
     * @param \Magento\Framework\Registry                      $coreRegistry
     * @param \Magento\Framework\ObjectManager\ConfigInterface $config
     */
    public function __construct(\Magento\Framework\App\Helper\Context $context ,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\ObjectManager\ConfigInterface $config,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Framework\Module\ModuleList\Loader $loader,
        \Magento\Framework\Xml\Parser $parser,
        DecoderInterface $urlDecoder,
        \Magento\Framework\Filesystem\Driver\File $driver,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context);
	$this->_backendConfig=$backendConfig;
        $this->moduleList = $moduleList;
        $this->moduleResource = $moduleResource;
        $this->_loader = $loader;
        $this->urlDecoder = $urlDecoder;
        $this->parser = $parser;
        $this->driver = $driver;
        $this->_objectManager = $objectManager;
        $this->urlBuilder = $this->_urlBuilder;
        $this->productMetadata   = $productMetadata;
        $this->_storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $this->_scopeConfigManager = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $this->_configValueManager = $this->_objectManager->get('Magento\Framework\App\Config\ValueInterface');
        $this->_transaction = $this->_objectManager->get('Magento\Framework\DB\Transaction');
    }

    /**
     * Returns module config data and a path to the module.xml file.
     *
     * Example of data returned by generator:
     * <code>
     *     [ 'vendor/module/etc/module.xml', '<xml>contents</xml>' ]
     * </code>
     *
     * @return \Traversable
     *
     * @author Josh Di Fabio <joshdifabio@gmail.com>
     */
    private function getModuleConfigs()
    {
        $modulePaths = $this->_objectManager->get('Magento\Framework\Component\ComponentRegistrar')->getPaths(\Magento\Framework\Component\ComponentRegistrar::MODULE);
        foreach ($modulePaths as $modulePath) {
            $filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, "$modulePath/etc/module.xml");
            yield [$filePath, $this->driver->fileGetContents($filePath)];
        }
    }

    public function getAllModules($exclude=array())
    {
        $result = [];
        foreach ($this->getModuleConfigs() as list($file, $contents)) {
            try {

                $this->parser->loadXML($contents);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    new \Magento\Framework\Phrase(
                        'Invalid Document: %1%2 Error: %3',
                        [$file, PHP_EOL, $e->getMessage()]
                    ),
                    $e
                );
            }

            $data = $this->convert($this->parser->getDom());
            if( count($data) ){
                $name = key($data);
                if (!in_array($name, $exclude)) {
                    if(isset($data[$name]) && isset($data[$name]['release_version'])) {
                        $result[$name] = $data[$name];
                    }
                }
             }
        }

        return $result;

    }
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function convert($source)
    {
        $modules = [];
        $xpath = new \DOMXPath($source);
        /**
         *
         *
         * @var $moduleNode \DOMNode
         */
        foreach ($xpath->query('/config/module') as $moduleNode) {

            $moduleData = [];
            $moduleAttributes = $moduleNode->attributes;
            $nameNode = $moduleAttributes->getNamedItem('name');
            if (strpos($nameNode->nodeValue, 'Makewebbetter') === false) {
                continue;
            }
            if ($nameNode === null) {
                throw new LocalizedException(
                    __('Attribute "name" is required for module node.')
                );
            }
           $moduleData['name'] = 'Magento2_'.$nameNode->nodeValue;
            $name = $moduleData['name'];
            $versionNode = $moduleAttributes->getNamedItem('setup_version');
            if ($versionNode === null) {
                throw new LocalizedException(
                    __("Attribute 'setup_version' is missing for module '{$name}'.")
                );
            }
            $moduleData['setup_version'] = $versionNode->nodeValue;
            if($moduleAttributes->getNamedItem('release_version')) {
                $moduleData['release_version'] = $moduleAttributes->getNamedItem('release_version')->nodeValue;
            }
            if($moduleAttributes->getNamedItem('parent_product_name')) {
                $moduleData['parent_product_name'] = $moduleAttributes->getNamedItem('parent_product_name')->nodeValue;
            }
            $moduleData['sequence'] = [];
            /**
             *
             *
             * @var $csChildNode \DOMNode
             */
            foreach ($moduleNode->childNodes as $csChildNode) {
                switch ($csChildNode->nodeName) {
                case 'sequence':
                    $moduleData['sequence'] = $this->_readModules($csChildNode);
                    break;
                }
            }
            // Use module name as a key in the result array to allow quick access to module configuration
            $modules['Magento2_'.$nameNode->nodeValue] = $moduleData;

        }
        return $modules;
    }
    /**
     * Convert module depends node into assoc array
     *
     * @param  \DOMNode $node
     * @return array
     * @throws \Exception
     */
    protected function _readModules(\DOMNode $node)
    {
        $result = [];
        /**
         *
         *
         * @var $csChildNode \DOMNode
         */
        foreach ($node->childNodes as $csChildNode) {
            switch ($csChildNode->nodeName) {
            case 'module':
                $nameNode = $csChildNode->attributes->getNamedItem('name');
                if ($nameNode === null) {
                    throw new LocalizedException(
                        __('Attribute "name" is required for module node.')
                    );
                }
                $result[] = $nameNode->nodeValue;
                break;
            }
        }
        return $result;
    }

    /**
     * Function for setting Config value of current store
     *
     * @param string $path,
     * @param string $value,
     */
    public function setDefaultStoreConfig($path, $value, $storeId=null)
    {
        $store=$this->_storeManager->getStore($storeId);
        $pathDetails = explode('/',$path);
        $configData = [
                        'section' => $pathDetails[0] ,
                        'website' => null,
                        'store'   => null,
                        'groups'  => [
                            $pathDetails[1] => [
                                'fields' => [
                                    $pathDetails[2] => [
                                        'value' => $value,
                                    ],
                                ],
                            ],
                        ],
                    ];

        /** @var \Magento\Config\Model\Config $configModel */
        $configModel = $this->_objectManager->get('Magento\Config\Model\Config\Factory')->create(['data' => $configData]);
        $configModel->save();
    }

    /**
     * Function for setting Config value of current store
     *
     * @param string $path,
     * @param string $value,
     */
    public function setStoreConfig($path, $value, $storeId=null)
    {
        $store=$this->_storeManager->getStore($storeId);
        $data = [
                    'path' => $path,
                    'scope' =>  'stores',
                    'scope_id' => $storeId,
                    'scope_code' => $store->getCode(),
                    'value' => $value,
                ];
        $this->_configValueManager->addData($data);
        $this->_transaction->addObject($this->_configValueManager);
        $this->_transaction->save();
    }

    /**
     * Function for getting Config value of current store
     *
     * @param string $path,
     */
    public function getStoreConfig($path,$storeId=0)
    {

        $store=$this->_storeManager->getStore($storeId);
        return $this->_scopeConfigManager->getValue($path, 'store', $store->getCode());
    }


}
