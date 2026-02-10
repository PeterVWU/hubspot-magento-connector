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
use Magento\Framework\UrlInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;

class Hubcustomer extends Column
{
	protected $customerRepository;

	/**
     * @var UrlInterface
     */
    protected $urlBuilder;
    public $resourceConfig;

	public function __construct(
		CustomerRepositoryInterface $customerRepository,
		HubConfig $resourceConfig,
		ContextInterface $context,
		UiComponentFactory $uiComponentFactory,
		UrlInterface $urlBuilder,
		array $components = [],
		array $data = [])
		{
			$this->resourceConfig = $resourceConfig;
			$this->customerRepository = $customerRepository;
			$this->urlBuilder = $urlBuilder;
			parent::__construct($context, $uiComponentFactory, $components, $data);
		}

	public function prepareDataSource(array $dataSource){
		 if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $customerHubID = $this->customerRepository->getById($item['entity_id'])->getCustomAttribute('hub_contact_id');
                if($customerHubID != null){
                    if ($customerHubID->getValue() != null) {
                        $url = 'https://app.hubspot.com/contacts/';
                        $item[$this->getData('name')] = [
                            'hub_contact_id' => [
                                'href' => $url.$this->getHubspotPortalId()."/contact/".$customerHubID->getValue(),
                                'label' => __('Check on HubSpot'),
                                'target' => '_blank',
                                'hidden' => false,
                            ]
                        ];
                    }
                    else {
                        $item[$this->getData('name')] = $this->getItem($item);
                    }
                }
                else {
                    $item[$this->getData('name')] = $this->getItem($item);
                }
            }
        }
        return $dataSource;
	}

	public function getHubspotPortalId(){
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/hub_id');
    }
    public function getItem($item){
        $itemData = [
            'hub_contact_id' => [
                'href' => $this->urlBuilder->getUrl(
                    'hubintegration/export/',
                    [
                        'id' => $item['entity_id'],
                        'export' => "CONTACT"
                    ]
                ),
                'label' => __('Sync with HubSpot'),
                'hidden' => false,
            ]
        ];
        return $itemData;
    }
}
