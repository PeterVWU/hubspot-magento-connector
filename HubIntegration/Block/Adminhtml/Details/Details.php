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

namespace Makewebbetter\HubIntegration\Block\Adminhtml\Details;

class Details extends \Magento\Backend\Block\Widget
{
    /**
     * @var string
     */

    protected $_template = 'Makewebbetter_HubIntegration::details/tab/details.phtml';

    /**
     * @inheritdoc
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     *
     * @return void
     */

    protected function _construct()
    {
        parent::_construct();

        $this->setId('details_content');
    }

    /**
     * Return page header.
     *
     * @return string
     */
    
    public function getHeader()
    {
        $header = __('Dashboard');
        return $header;
    }
}
