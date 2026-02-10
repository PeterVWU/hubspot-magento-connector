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

namespace Makewebbetter\HubIntegration\Block\Adminhtml\Details\Details;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */

     protected function _construct()
    {
        parent::_construct();
        $this->setId('details_tabs');
        $this->setDestElementId('hub_details_tabs');
        $this->setTitle(__('Dashboard'));
    }

    /**
     * To add tabs on the details page
     */

    protected function _prepareLayout()
    {

        $this->addTab(
            'general_details',
            [
                'label' => __('General Details'),
                'title' => __('General Details'),
                'content' => $this->getLayout()->createBlock(
                    \Makewebbetter\HubIntegration\Block\Adminhtml\Details\Details\Tabs\General::class
                )->toHtml()
                ,'active' => true
            ]
        );
        $this->addTab(
            'pipelines_and_deal_stages',
            [
                'label' => __('Pipelines And Deal Stages'),
                'title' => __('Pipelines And Deal Stages'),
                'content' => $this->getLayout()->createBlock(
                    \Makewebbetter\HubIntegration\Block\Adminhtml\Details\Details\Tabs\Dashboardmapping::class
                )->toHtml()
            ]
        );
        $this->addTab(
            'groups_and_properties_details',
            [
                'label' => __('Groups and Properties Details'),
                'title' => __('Groups and Properties Details'),
                'content' => $this->getLayout()->createBlock(
                    \Makewebbetter\HubIntegration\Block\Adminhtml\Details\Details\Tabs\Groupsproperties::class
                )->toHtml(),
            ]
        );
        $this->addTab(
            'contact_lists',
            [
                'label' => __('Contact Lists'),
                'title' => __('Contact Lists'),
                'content' => $this->getLayout()->createBlock(
                    \Makewebbetter\HubIntegration\Block\Adminhtml\Details\Details\Tabs\Contactlists::class
                )->toHtml()
            ]
        );

        $this->addTab(
            'Workflows',
            [
                'label' => __('Workflows'),
                'title' => __('Workflows'),
                'content' => $this->getLayout()->createBlock(
                    \Makewebbetter\HubIntegration\Block\Adminhtml\Details\Details\Tabs\Workflow::class
                )->toHtml()
            ]
        );
        $this->addTab(
            'add_ons',
            [
                'label' => __('Our Add-ons'),
                'title' => __('Our Add-ons'),
                'content' => $this->getLayout()->createBlock(
                    \Makewebbetter\HubIntegration\Block\Adminhtml\Details\Details\Tabs\Addons::class
                )->toHtml()
            ]
        );

        return parent::_prepareLayout();
    }
}
