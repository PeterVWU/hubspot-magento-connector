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

use Makewebbetter\HubIntegration\Model\HubItemFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubItem;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class RemoveQuote
 * @package Makewebbetter\HubIntegration\Observer
 */
class RemoveQuote implements ObserverInterface
{
    /**
     * @var HubItemFactory
     */
    private $hubItem;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var HubItem
     */
    private $hubItemResource;

    /**
     * RemoveQuote constructor.
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
        try{
            $item = $observer->getEvent()->getQuoteItem();
            $hubItem = $this->hubItem->create()
                ->setData('object_type', 'LINE_ITEM')
                ->setData('object_id', $item->getId())
                ->setData('quote_id', $item->getQuoteId());
            $this->hubItemResource->save($hubItem);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
