<?php
/**
 * makewebbetter
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
 * @copyright   Copyright makewebbetter (http://makewebbetter.com/)
 * @license     https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Block\Authorize;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;

class Config extends Template
{
    /**
     * @var ConnectionManager
     */
    public $connectionManager;

    /**
     * Config constructor.
     * @param ConnectionManager $connectionManager
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        ConnectionManager $connectionManager,
        Template\Context $context,
        array $data = []
    ) {
        $this->connectionManager = $connectionManager;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getCode()
    {
        $params = $this->getRequest()->getParams();
        return $params;
    }

    /**
     * @return mixed|null
     */
    public function getConnectionStatus()
    {
        return $this->connectionManager->connectionEstablished;
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function baseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
