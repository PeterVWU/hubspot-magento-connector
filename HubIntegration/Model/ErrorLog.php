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

namespace Makewebbetter\HubIntegration\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class ErrorLog
 * @package Makewebbetter\HubIntegration\Model
 */
class ErrorLog extends AbstractModel
{

    public function _construct()
    {
        $this->_init('Makewebbetter\HubIntegration\Model\ResourceModel\ErrorLog');
    }
}
