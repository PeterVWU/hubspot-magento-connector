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


namespace Makewebbetter\HubIntegration\Model\Menu\Item;

use Magento\Backend\Model\Menu\Item;

class HelpguideUrl
{
    /**
     * @param Item $subject
     * @param string $url
     * @return string
     */
    public function afterGetUrl(Item $subject, $url)
    {
        if ($subject->getId() === 'Makewebbetter_HubIntegration::hub_support') {
            return 'https://makewebbetter.com/contact-us/';
        }
        if ($subject->getId() === 'Makewebbetter_HubIntegration::hub_userguide') {
            return 'https://docs.makewebbetter.com/hubspot-magento-integration/';
        }

        return $url;
    }
}