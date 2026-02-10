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

namespace Makewebbetter\HubIntegration\Model\System\Config\Backend;

class HubConfig extends \Magento\Framework\App\Config\Value
{

    /**
     * @return \Magento\Framework\App\Config\Value|void
     */
    public function beforeSave()
    {
        $value=$this->getValue();
        $json_data=json_encode($value);
        $this->setValue($json_data);
    }

    /**
     * @return \Magento\Framework\App\Config\Value|void
     */
    public function _afterLoad()
    {
        $data=$this->getValue();
        $value=json_decode($data, true);
        $this->setValue($value);
    }
}
