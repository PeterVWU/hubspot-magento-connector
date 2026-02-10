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

namespace Makewebbetter\HubIntegration\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;
use Magento\Framework\UrlInterface;


class Hubproduct extends Column
{
	protected $productRepositoryInterface;
	protected $_searchCriteria;
	protected $urlBuilder;
    public $resourceConfig;

    public function __construct(
		HubConfig $resourceConfig,
		ProductRepositoryInterface $ProductRepositoryInterface,
		UrlInterface $urlBuilder,
		ContextInterface $context,
		UiComponentFactory $uiComponentFactory,
		SearchCriteriaBuilder $criteria,
		array $components = [], array $data = [])
		{
			$this->urlBuilder = $urlBuilder;
			$this->resourceConfig = $resourceConfig;
			$this->productRepositoryInterface = $ProductRepositoryInterface;
			$this->_searchCriteria = $criteria;
			parent::__construct($context, $uiComponentFactory, $components, $data);
		}

	public function prepareDataSource(array $dataSource){
		 if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $productHubID = $this->productRepositoryInterface->getById($item['entity_id'])->getCustomAttribute('hub_product_id');
            	if ($productHubID != null) {
            		$url = 'https://app.hubspot.com/contacts/';
					$item[$this->getData('name')]['hub_product_id'] = [
	                    'href' => $url.$this->getHubspotPortalId()."/objects/0-7/views/all/list",
	                    'label' => __('Check on HubSpot'),
	                    'target' => '_blank',
	                    'hidden' => false,
	                ];
				}else{
					$item[$this->getData('name')]['hub_product_id'] = [
	                    'href' => $this->urlBuilder->getUrl(
	                        'hubintegration/export/',
	                        [
	                        	'id' => $item['entity_id'],
	                        	'export' => "PRODUCT"
	                        ]
	                    ),
	                    'label' => __('Sync with HubSpot'),
	                    'hidden' => false,
	                ];
				}
            }
        }
        return $dataSource;
	}

	public function getHubspotPortalId(){
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/hub_id');
    }
}
