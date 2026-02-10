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

define(
    [
        'jquery',
        'Magento_Ui/js/modal/confirm'
    ], function ($, confirmation) {
        return function (config) {
            /**
             * Export click
             */
            $('#hubspot-export').on('click', function () {
                confirmation({
                    title: $.mage.__('Confirmation'),
                    content: $.mage.__('You also want to export Abandoned Cart(s) ?'),
                    modalClass: 'hubs-confirm',
                    actions: {
                        close: function () {
                            this.closeModal(event);
                        }
                    },
                    buttons: [{
                        text: $.mage.__('Yes'),
                        class: 'action-primary action-accept',
                        click: function (event) {
                            window.location.href = config.exportUrlWithAbandonedCart;
                            closeWindow();
                        }
                    },{
                        text: $.mage.__('No'),
                        class: 'action-primary action-accept',
                        click: function (event) {
                            window.location.href = config.exportUrlWithoutAbandonedCart;
                            closeWindow();
                        }
                    }]
                });
            });
            function closeWindow(){
                setTimeout(function () {
                    window.close();
                }, 2000);
            }
        }
    });



