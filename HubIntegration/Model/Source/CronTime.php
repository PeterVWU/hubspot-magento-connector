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
class CronTime implements OptionSourceInterface
{
    const ONCE_IN_A_DAY = '*/60 */24 * * *';
    const TWICE_A_DAY  = '*/60 */12 * * *';
    const FOUR_TIMES_A_DAY = '*/60 */6 * * *';
    const EVERY_HOUR = '*/60 */1 * * *';
    const EVERY_FIVE_MINUTES = "*/5 * * * *";

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
            ['value' => self::ONCE_IN_A_DAY, 'label' => __('Once In A Day') ],
            ['value' => self::TWICE_A_DAY, 'label' => __('Twice A Day') ],
            ['value' => self::FOUR_TIMES_A_DAY, 'label' => __('Four Times A Day') ],
            ['value' => self::EVERY_HOUR, 'label' => __('Every Hour') ],
            ['value' => self::EVERY_FIVE_MINUTES, 'label' => __('Every 5 Min') ]
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