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
namespace Makewebbetter\HubIntegration\Controller\Adminhtml\ErrorLog;

use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Makewebbetter\HubIntegration\Model\ResourceModel\ErrorLog\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class MassDelete
 * @package Makewebbetter\HubIntegration\Controller\Adminhtml\ErrorLog
 */
class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Makewebbetter_HubIntegration::error_log_mass_delete';

    /**
     * @var string
     */
    protected $redirectUrl = 'hubintegration/errorlog/index';

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var object
     */
    protected $collectionFactory;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Helper $helper
    )
    {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->helper = $helper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            if (!$this->helper->isModuleEnable()) {
                $this->messageManager->addWarningMessage(__('Please enable HubSpot Extension.'));
                $this->_redirect('admin/dashboard/index');
            }
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $totalCount = $collection->getSize();
            $collection->walk('delete');
            $this->messageManager->addSuccessMessage(__("%1 Error Log(s) have been deleted.", $totalCount));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath($this->redirectUrl);
    }
}
