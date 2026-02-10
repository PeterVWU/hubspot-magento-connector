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
 * Class HubSpotObjectType
 * @package Makewebbetter\HubIntegration\Model\Source
 */
class HubSpotObjectType implements OptionSourceInterface
{
    const CONTACT = "CONTACT";
    const DEAL = "DEAL";
    const PRODUCT = "PRODUCT";
    const LINE_ITEM = "LINE_ITEM";

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
            ['value' => self::CONTACT, 'label' => self::CONTACT ],
            ['value' => self::DEAL, 'label' => self::DEAL ],
            ['value' => self::PRODUCT, 'label' => self::PRODUCT ],
            ['value' => self::LINE_ITEM, 'label' => self::LINE_ITEM]
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
