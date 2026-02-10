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
 * @author 		Makewebbetter Core Team <connect@makewebbetter.com>
 * @copyright   Copyright Makewebbetter (http://makewebbetter.com/)
 * @license     https://makewebbetter.com/license-agreement.txt
 */
namespace Makewebbetter\HubIntegration\Controller\Adminhtml\Cron;

use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;

/**
 * Class Index
 * @package Makewebbetter\HubIntegration\Controller\Adminhtml\Cron
 */
class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Makewebbetter_HubIntegration::cron_status';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Helper $helper
    ){
        $this->helper = $helper;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page|
     * \Magento\Framework\App\ResponseInterface|
     * \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->helper->isModuleEnable()) {
            $this->messageManager->addWarningMessage(__('Please enable HubSpot Extension.'));
            $this->_redirect('admin/dashboard/index');
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Makewebbetter_HubIntegration::cron_status');
        $resultPage->getConfig()->getTitle()->prepend(__('Cron Status'));
        return $resultPage;
    }


}
