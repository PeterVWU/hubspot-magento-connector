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
use Magento\Sales\Api\OrderRepositoryInterface;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;

class Huborder extends Column
{
	protected $orderRepositoryInterface;

	/**
     * @var UrlInterface
     */
    protected $urlBuilder;
    public $resourceConfig;

    public function __construct(
		OrderRepositoryInterface $orderRepository,
		HubConfig $resourceConfig,
		UrlInterface $urlBuilder,
		ContextInterface $context,
		UiComponentFactory $uiComponentFactory,
		array $components = [],
		array $data = [])
		{
			$this->resourceConfig = $resourceConfig;
			$this->orderRepositoryInterface = $orderRepository;
			$this->urlBuilder = $urlBuilder;
			parent::__construct($context, $uiComponentFactory, $components, $data);
		}

	public function prepareDataSource(array $dataSource)
	{
		if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $orderHubId = $this->orderRepositoryInterface->get($item['entity_id'])->getData('hub_deal_id');
                if ($orderHubId != null) {
                    $url = 'https://app.hubspot.com/contacts/';
                    $item[$this->getData('name')]['hub_deal_id'] = [
                        'href' => $url.$this->getHubspotPortalId()."/deal/".$orderHubId,
                        'label' => __('Check on HubSpot'),
                        'target' => '_blank',
                        'hidden' => false,
                    ];
                }else{
                    $item[$this->getData('name')]['hub_deal_id'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'hubintegration/export/orderexport',
                            [
                                'entity_id' => $item['entity_id'],
                                'quote_id' => $this->orderRepositoryInterface->get($item['entity_id'])->getData('quote_id')
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
