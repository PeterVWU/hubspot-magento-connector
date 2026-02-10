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
namespace Makewebbetter\HubIntegration\Controller\Adminhtml\Export;

use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Makewebbetter\HubIntegration\Model\DataSync;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Magento\Framework\App\Response\RedirectInterface;

class Orderexport extends \Magento\Backend\App\Action
{

   /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Makewebbetter_HubIntegration::sync_export_to_hubspot';

    private $helper;
    public  $redirect;
    public  $dataSync;
    public  $connectionManager;
    public $redirectUrlOrder = 'sales/order/index';

    /**
     * Customer constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param DataSync $dataSync
     * @param Helper $helper
     * @param ConnectionManager $connectionManager
     */
    public function __construct(
        RedirectInterface $redirect,
        Context $context,
        DataSync $dataSync,
        Helper $helper,
        ConnectionManager $connectionManager
    )
    {
        parent::__construct($context);
        $this->redirect = $redirect;
        $this->dataSync = $dataSync;
        $this->helper = $helper;
        $this->connectionManager = $connectionManager;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page|
     * \Magento\Framework\App\ResponseInterface|
     * \Magento\Framework\Controller\ResultInterface
     */
    public function execute(){
       try {
            if (!$this->helper->isModuleEnable()) {
                $this->messageManager->addWarningMessage(__('Please enable HubSpot Extension.'));
                $this->_redirect('admin/dashboard/index');
            }
            if (!$this->connectionManager->getHubConfig('connection_established')){
                $configUrl = $this->_url->getUrl('adminhtml/system_config/edit/section/hub_integration');
                $this->messageManager->addNoticeMessage(__('Please Authorize first before export.'));
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath($this->redirect->getRefererUrl());
            }

            $id = array();
            $params = $this->getRequest()->getParams();

            if (isset($params['entity_id'])) {
                $id[] = $params['entity_id'];
            }

            if ($this->dataSync->massExportToHubSpot("DEAL",$id)) {
               $this->messageManager->addSuccessMessage(__("The Order have been exported to HubSpot."));
            }else {
                $this->messageManager->addWarningMessage(__("The please try after some time(Check whether module is enable or not! If it is enable then check for license activation!)"));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath($this->redirectUrlOrder);
    }
}
