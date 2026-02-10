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
    'jquery'
], function ($) {
    return function (config) {
        $(document).ready(function () {
            $.ajax({
                url: config.PropertiesAjaxUrl,
                data: {form_key: window.FORM_KEY},
                type: 'POST'
            }).done(function (transport) {
                $('.installation-progress .group-and-property').removeClass('inprogress');
                $('.installation-progress .group-and-property,.list-wrapper .group-and-property-list').addClass('active');
                $('.installation-progress .contact-lists').addClass('inprogress');
                $.ajax({
                    url: config.ListsAjaxUrl,
                    data: {form_key: window.FORM_KEY},
                    type: 'POST'
                }).done(function (transport) {
                    $('.installation-progress .contact-lists, .list-wrapper .contact-lists-list').addClass('active');
                    $('.installation-progress .contact-lists').removeClass('inprogress');
                    $('.installation-progress .work-flows').addClass('inprogress');
                    $.ajax({
                        url: config.WorkflowAjaxUrl,
                        data: {form_key: window.FORM_KEY},
                        type: 'POST'
                    }).done(function (transport) {
                        $('.installation-progress .work-flows').removeClass('inprogress');
                        $('.installation-progress .work-flows, .list-wrapper .work-flows-list').addClass('active');
                        nextWindow();
                    });
                });
            });
            function nextWindow() {
                $(".mapping_next >  button").removeAttr('disabled');
            }
        });
    }
});
