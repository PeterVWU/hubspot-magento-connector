<?php
/**
 * Makewebbetter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement(EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://makewebbetter.com/license-agreement.txt
 *
 * @category  Makewebbetter
 * @package   Makewebbetter_HubIntegration
 * @author    Makewebbetter Core Team <connect@makewebbetter.com>
 * @copyright Copyright Makewebbetter(http://makewebbetter.com/)
 * @license   https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Makewebbetter\HubIntegration\Model\HubItemFactory;
use Magento\Framework\Event\Observer;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubItem;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class ProductDeleteBefore
 * @package Makewebbetter\HubIntegration\Observer
 */
class ProductDeleteBefore implements ObserverInterface
{
    /**
     * @var HubItem
     */
    private $hubItemResource;

    /**
     * @var HubItemFactory
     */
    private $hubItem;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ProductDeleteBefore constructor.
     * @param HubItemFactory $hubItemFactory
     * @param HubItem $hubItemResource
     * @param Logger $logger
     */
    public function __construct(
        HubItemFactory $hubItemFactory,
        HubItem $hubItemResource,
        Logger $logger
    ) {
        $this->hubItem = $hubItemFactory;
        $this->hubItemResource = $hubItemResource;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        // try{
        //     /** @var \Magento\Catalog\Model\Product $product */
        //     $product = $observer->getEvent()->getProduct();
        //     if ($product && $product->getHubProductId()) {
        //         $hubItem = $this->hubItem->create()
        //             ->setData('object_type', 'PRODUCT')
        //             ->setData('object_id', $product->getHubProductId());
        //         $this->hubItemResource->save($hubItem);
        //     }
        // } catch (\Exception $e) {
        //     $this->logger->critical($e);
        // }
    }
}
