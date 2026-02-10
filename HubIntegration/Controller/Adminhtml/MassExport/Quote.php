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
namespace Makewebbetter\HubIntegration\Controller\Adminhtml\MassExport;

use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Makewebbetter\HubIntegration\Model\ResourceModel\ActiveCarts\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Makewebbetter\HubIntegration\Model\DataSync;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;

/**
 * Class Quote
 * @package Makewebbetter\HubIntegration\Controller\Adminhtml\MassExport
 */
class Quote extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Makewebbetter_HubIntegration::cart_export_to_hubspot';

    /**
     * @var string
     */
    protected $redirectUrl = 'hubintegration/activeCarts/index';

    /**
     * @var ConnectionManager
     */
    public $connectionManager;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var object
     */
    protected $collectionFactory;

    /**
     * @var DataSync
     */
    private $dataSync;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * Quote constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param DataSync $dataSync
     * @param Helper $helper
     * @param ConnectionManager $connectionManager
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        DataSync $dataSync,
        Helper $helper,
        ConnectionManager $connectionManager
    )
    {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->dataSync = $dataSync;
        $this->helper = $helper;
        $this->connectionManager = $connectionManager;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            if (!$this->helper->isModuleEnable()) {
                $this->messageManager->addWarningMessage(__('Please enable HubSpot Extension.'));
                return $this->_redirect('admin/dashboard/index');
            }
            if (!$this->connectionManager->getHubConfig('connection_established')){
                $configUrl = $this->_url->getUrl('adminhtml/system_config/edit/section/hub_integration');
                $this->messageManager->addNoticeMessage(__('Please Authorize first before export.'));
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath($this->redirectUrl);
            }
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            if($this->dataSync->massExportToHubSpot('QUOTE', $collection->getAllIds())){
                $this->messageManager->addSuccessMessage(__("%1 Cart(s) have been exported to HubSpot.", $collection->getSize()));
            }else{
                $this->messageManager->addWarningMessage(__("The please try after some time(Check whether module is enable or not! If it is enable then check for license activation!)"));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath($this->redirectUrl);
    }
}
