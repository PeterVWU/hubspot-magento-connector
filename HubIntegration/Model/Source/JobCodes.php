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

namespace Makewebbetter\HubIntegration\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class CronTime
 * @package Makewebbetter\HubIntegration\Model\Source
 */
class JobCodes implements OptionSourceInterface
{
    const EXPORT_OLD_DATA_JOB_CODE  = 'makewebbetter_hub_export_old_data';
    const EXPORT_TO_HUBSPOT_JOB_CODE = 'makewebbetter_hub_export_to_hubspot';
    const DELETE_ERROR_LOG_JOB_CODE = 'makewebbetter_hub_delete_error_log';

    /**
     * @var array
     */
    public $_options = [];

    /**
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = [
            ['value' => self::EXPORT_OLD_DATA_JOB_CODE, 'label' => __('Export Old Data') ],
            ['value' => self::EXPORT_TO_HUBSPOT_JOB_CODE, 'label' => __('Export To HubSpot') ],
            ['value' => self::DELETE_ERROR_LOG_JOB_CODE, 'label' => __('Delete Error Log') ]
        ];
        return $this->_options;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

    /**
     * @param $optionId
     * @return mixed|null
     */
    public function getOptionText($optionId)
    {
        $options = $this->getOptionArray();
        return isset($options[$optionId]) ? $options[$optionId] : null;
    }

    /**
     * @return array
     */
    public function getOptionArray()
    {
        $options = [];
        foreach ($this->getAllOptions() as $option) {
            $options[$option['value']] = (string)$option['label'];
        }
        return $options;
    }
}