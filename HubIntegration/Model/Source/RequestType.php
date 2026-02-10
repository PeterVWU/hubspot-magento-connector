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
 * Class RequestType
 * @package Makewebbetter\HubIntegration\Model\Source
 */
class RequestType implements OptionSourceInterface
{
    const REQUEST_TYPE_GET = "GET";
    const REQUEST_TYPE_PUT = "PUT";
    const REQUEST_TYPE_POST = "POST";
    const REQUEST_TYPE_EXCEPTION = "EXCEPTION";
    const REQUEST_TYPE_OTHER = "OTHER";

    const REQUEST_TYPE_DELETE = "DELETE";

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
            ['value' => self::REQUEST_TYPE_GET, 'label' => self::REQUEST_TYPE_GET ],
            ['value' => self::REQUEST_TYPE_PUT, 'label' => self::REQUEST_TYPE_PUT ],
            ['value' => self::REQUEST_TYPE_POST, 'label' => self::REQUEST_TYPE_POST ],
            ['value' => self::REQUEST_TYPE_EXCEPTION, 'label' => self::REQUEST_TYPE_EXCEPTION ],
            ['value' => self::REQUEST_TYPE_OTHER, 'label' => self::REQUEST_TYPE_OTHER ]
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
