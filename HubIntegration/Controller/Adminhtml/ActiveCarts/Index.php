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
namespace Makewebbetter\HubIntegration\Controller\Adminhtml\ActiveCarts;

use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Makewebbetter_HubIntegration::active_carts';

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
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
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
        $resultPage->setActiveMenu('Makewebbetter_HubIntegration::hub_active_carts');
        $resultPage->getConfig()->getTitle()->prepend(__('Login Customer\'s Abandoned Cart(s)'));
        return $resultPage;
    }
}
