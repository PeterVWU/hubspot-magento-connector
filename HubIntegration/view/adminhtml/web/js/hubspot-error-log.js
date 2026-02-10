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

        /**
         * Apply Filter
         */
        jQuery('.get-hubspot-error-button').on('click', function () {
            var include_resolved_error = jQuery("select[name='include_resolved_error']").val();
            var error_type = jQuery("select[name='error_type']").val();
            var object_type = jQuery("select[name='object_type']").val();
            var limit = jQuery("input[name='limit']").val();
            if (!validate(limit))
                return false;
            window.location.href = prepareRedirectUrl(include_resolved_error, error_type, object_type, limit, 1);
        });

        /**
         * Pager Next button functionality
         */
        jQuery('.hubspot-next-button').on('click', function () {
            window.location.href = prepareRedirectUrl(config.oldIncludeResolvedError,
                                                      config.oldErrorType,
                                                      config.oldObjectType,
                                                      config.oldLimit,
                                                      config.nextPage);
        });

        /**
         * Pager Previous button functionality
         */
        jQuery('.hubspot-previous-button').on('click', function () {
            window.location.href = prepareRedirectUrl(config.oldIncludeResolvedError,
                config.oldErrorType,
                config.oldObjectType,
                config.oldLimit,
                config.previousPage);
        });

        /**
         * Manage filter enable disable feature
         */
        jQuery('select,input').on('change', function () {
            var include_resolved_error = jQuery("select[name='include_resolved_error']").val();
            var error_type = jQuery("select[name='error_type']").val();
            var object_type = jQuery("select[name='object_type']").val();
            var limit = jQuery("input[name='limit']").val();
            var isFilterEnable = false;
            if (include_resolved_error != config.oldIncludeResolvedError)
                isFilterEnable = true;
            if (error_type != config.oldErrorType)
                isFilterEnable = true;
            if (object_type != config.oldObjectType)
                isFilterEnable = true;
            if (limit != config.oldLimit)
                isFilterEnable = true;
            if (isFilterEnable) {
                jQuery('.get-hubspot-error-button').attr('disabled', false);
            } else {
                jQuery('.get-hubspot-error-button').attr('disabled', true);
            }
        });

        /**
         *
         * @param include_resolved_error
         * @param error_type
         * @param object_type
         * @param limit
         * @param page
         * @returns {*}
         */
        function prepareRedirectUrl(include_resolved_error, error_type, object_type, limit, page) {
            var redirectUrl = config.filterUrl;
            if (include_resolved_error) {
                redirectUrl += 'include_resolved_error/' + include_resolved_error + '/';
            }
            if (error_type) {
                redirectUrl += 'error_type/' + error_type + '/';
            }
            if (object_type) {
                redirectUrl += 'object_type/' + object_type + '/';
            }
            if (!limit) {
                limit = 200;
            }
            redirectUrl += 'limit/' + limit + '/';
            redirectUrl += 'page/' + page;
            return redirectUrl;
        }

        /**
         *
         * @param object_type
         * @param limit
         * @returns {boolean}
         */
        function validate(limit) {
            var validationPass = true;
            if (limit) {
                var numberPattern = /^[0-9]*$/.test(limit);
                if(numberPattern){
                    if (limit >= 1 && limit <= 200) {
                        jQuery('#limit-error').hide();
                    } else {
                        jQuery('#limit-error').text(config.rangeErrorMessage);
                        jQuery('#limit-error').show();
                        validationPass = false;
                    }
                } else {
                    jQuery('#limit-error').text(config.validNumberErrorMessage);
                    jQuery('#limit-error').show();
                    validationPass = false;
                }
            }
            return validationPass;
        }
    }
});