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

namespace Makewebbetter\HubIntegration\Controller\Config;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Mapping extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Makewebbetter\HubIntegration\Helper\ConnectionManager
     */
    public $connectionManager;

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Makewebbetter\HubIntegration\Helper\ConnectionManager $connectionManager
    ) {
        $this->connectionManager = $connectionManager;
        $this->resultPageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set('HubSpot Magento Pipeline and Deal Stage');
        return $resultPage;
    }
}
