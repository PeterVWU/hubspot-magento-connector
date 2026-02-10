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
 * Class HubSpotErrorType
 * @package Makewebbetter\HubIntegration\Model\Source
 */
class HubSpotErrorType implements OptionSourceInterface
{
    const INACTIVE_PORTAL = "INACTIVE_PORTAL";
    const NO_SYNC_SETTINGS = "NO_SYNC_SETTINGS";
    const SETTINGS_NOT_ENABLED = "SETTINGS_NOT_ENABLED";
    const NO_MAPPINGS_DEFINED = "NO_MAPPINGS_DEFINED";
    const MISSING_REQUIRED_PROPERTY = "MISSING_REQUIRED_PROPERTY";
    const NO_PROPERTIES_DEFINED = "NO_PROPERTIES_DEFINED";
    const INVALID_ASSOCIATION_PROPERTY = "INVALID_ASSOCIATION_PROPERTY";
    const INVALID_DEAL_STAGE = "INVALID_DEAL_STAGE";
    const INVALID_EMAIL_ADDRESS = "INVALID_EMAIL_ADDRESS";
    const INVALID_ENUM_PROPERTY = "INVALID_ENUM_PROPERTY";
    const UNKNOWN_ERROR = "UNKNOWN_ERROR";

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
            ['value' => "", 'label' => __('Please Select') ],
            ['value' => self::INACTIVE_PORTAL, 'label' => self::INACTIVE_PORTAL ],
            ['value' => self::NO_SYNC_SETTINGS, 'label' => self::NO_SYNC_SETTINGS ],
            ['value' => self::SETTINGS_NOT_ENABLED, 'label' => self::SETTINGS_NOT_ENABLED ],
            ['value' => self::NO_MAPPINGS_DEFINED, 'label' => self::NO_MAPPINGS_DEFINED ],
            ['value' => self::MISSING_REQUIRED_PROPERTY, 'label' => self::MISSING_REQUIRED_PROPERTY ],
            ['value' => self::NO_PROPERTIES_DEFINED, 'label' => self::NO_PROPERTIES_DEFINED ],
            ['value' => self::INVALID_ASSOCIATION_PROPERTY, 'label' => self::INVALID_ASSOCIATION_PROPERTY ],
            ['value' => self::INVALID_DEAL_STAGE, 'label' => self::INVALID_DEAL_STAGE ],
            ['value' => self::INVALID_EMAIL_ADDRESS, 'label' => self::INVALID_EMAIL_ADDRESS ],
            ['value' => self::INVALID_ENUM_PROPERTY, 'label' => self::INVALID_ENUM_PROPERTY ],
            ['value' => self::UNKNOWN_ERROR, 'label' => self::UNKNOWN_ERROR ],
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
