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

define([
    'jquery',
    'prototype'
], function(jQuery){
    return function (config) {
        function generateCode() {
           let left = (jQuery(window).width() / 2) - (800 / 2);
           let top = (jQuery(window).height() / 2) - (550 / 2);
            window.open(config.AuthorizeUrl, 'popup', "width=800, height=450, top=" + top + ", left=" + left);
        }

        jQuery('#authorize_button').click(function () {
            generateCode();
        });
    }
});
