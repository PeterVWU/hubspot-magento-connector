<?php
/**
 * Makewebbetter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement(EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://makewebbetter.com/license-agreement.txt
 *
 * @category  Makewebbetter
 * @package   Makewebbetter_HubIntegration
 * @author    Makewebbetter Core Team <connect@makewebbetter.com>
 * @copyright Copyright Makewebbetter (http://makewebbetter.com/)
 * @license   https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Model\ResourceModel\HubItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Makewebbetter\HubIntegration\Model\ResourceModel\Cron
 */
class Collection extends AbstractCollection
{
    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _construct()
    {
        $this->_init('Makewebbetter\HubIntegration\Model\HubItem', 'Makewebbetter\HubIntegration\Model\ResourceModel\HubItem');
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }
}
