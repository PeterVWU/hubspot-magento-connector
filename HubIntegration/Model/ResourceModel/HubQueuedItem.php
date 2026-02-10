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
 * @copyright Copyright Makewebbetter(http://makewebbetter.com/)
 * @license   https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class HubQueuedItem extends AbstractDb
{
    /**
     * @return void
     */
    public function _construct()
    {
        //hub_makewebbetter_items is table and id is primary key of this table
        $this->_init('hub_makewebbetter_queued_items', 'id');
    }
}