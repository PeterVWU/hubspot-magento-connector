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

namespace Makewebbetter\HubIntegration\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;

class Properties extends AbstractHelper
{
    /**
     * @var HubConfig
     */
    private $resourceConfig;

    /**
     * @var array
     */
    private $workflowsId = [];

    /**
     * Properties constructor.
     * @param Context $context
     * @param HubConfig $resourceConfig
     */
    public function __construct(
        Context $context,
        HubConfig $resourceConfig
    )
    {
        $this->resourceConfig = $resourceConfig;
        parent::__construct($context);
    }

    /**
     * Get Additional contact group to create
     * @return array
     */
    public function getContactGroups()
    {
        $group = [];
        $group[] = ['name' => 'customer_group', 'displayName' => 'Customer Group'];
        $group[] = ['name' => 'shopping_cart_fields', 'displayName' => 'Shopping Cart Information'];
        $group[] = ['name' => 'order', 'displayName' => 'Order'];
        $group[] = ['name' => 'last_products_bought', 'displayName' => 'Last Products Bought'];
        $group[] = ['name' => 'categories_bought', 'displayName' => 'Categories Bought'];
        $group[] = ['name' => 'rfm_fields', 'displayName' => 'RFM Information'];
        $group[] = ['name' => 'skus_bought', 'displayName' => 'SKUs Bought'];
        $group[] = ['name' => 'roi_tracking', 'displayName' => 'ROI Tracking'];
        return $group;
    }

    /**
     * get user actions for marketing
     *
     * @return array  marketing actions for users
     * @since 1.0.0
     */
    public function getUserMarketing()
    {
        $user_actions = [];
        $user_actions[] = ['label' => 'Yes', 'value' => 'yes'];
        $user_actions[] = ['label' => 'No', 'value' => 'no'];
        return $user_actions;
    }

    /**
     * customer new order status
     * @return array
     * @since 1.0.0
     */
    public function newOrderStatus()
    {
        $values = [];
        $values[] = ['label' => 'Yes', 'value' => 'yes'];
        $values[] = ['label' => 'No', 'value' => 'no'];
        return $values;
    }

    /**
     * get ratings for RFM analysis
     *
     * @return  array ratings for RFM analysis
     * @since 1.0.0
     */
    public function getRfmRating()
    {
        $rating = [];
        $rating[] = ['label' => '5', 'value' => 5];
        $rating[] = ['label' => '4', 'value' => 4];
        $rating[] = ['label' => '3', 'value' => 3];
        $rating[] = ['label' => '2', 'value' => 2];
        $rating[] = ['label' => '1', 'value' => 1];
        return $rating;
    }

    /**
     * conversion options for campaigns on HubSpot
     * @return  array campaign conversion
     * @since 1.0.0
     */
    public function getCampaignConversionOption()
    {
        $values = [];
        $values[] = ['label' => 'Yes', 'value' => 'true'];
        $values[] = ['label' => 'No', 'value' => 'false'];
        return $values;
    }

    /**
     * get all campaigns names for HubSpot
     * @since 1.0.0
     */
    public function getAllCampaigns()
    {
        $all_names = [];
        $all_names[] = ["label" => "MQL Nurture & Conversion", "value" => "MQL Nurture & Conversion"];
        $all_names[] = [
            "label" => "New Customer Welcome & Get a 2nd Order",
            "value" => "New Customer Welcome & Get a 2nd Order"
        ];
        $all_names[] = [
            "label" => "2nd Order Thank You & Get a 3rd Order",
            "value" => "2nd Order Thank You & Get a 3rd Order"
        ];
        $all_names[] = ["label" => "3rd Order Thank You", "value" => "3rd Order Thank You"];
        $all_names[] = ["label" => "Customer Reengagement", "value" => "Customer Reengagement"];
        $all_names[] = ["label" => "Customer Rewards", "value" => "Customer Rewards"];
        $all_names[] = ["label" => "Abandoned Cart Recovery", "value" => "Abandoned Cart Recovery"];
        $all_names[] = ["label" => "None", "value" => "None"];
        return $all_names;
    }

    /**
     * @param $group_name
     * @return array
     */
    public function getContactProperty($group_name)
    {
        $group_properties = [];
        if (!empty($group_name)) {
            if ($group_name == "customer_group") {
                $group_properties[] = [
                    "name" => "customer_group",
                    "label" => 'Customer Group/ User role',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "newsletter_subscription",
                    "label" => 'Accepts Marketing',
                    "type" => "enumeration",
                    "fieldType" => "select",
                    "formField" => false,
                    "options" => $this->getUserMarketing()
                ];
                $group_properties[] = [
                    "name" => "shopping_cart_customer_id",
                    "label" => 'Shopping Cart Customer ID',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false
                ];
            } elseif ($group_name == "shopping_cart_fields") {
                $group_properties[] = [
                    "name" => "shipping_address_line_1",
                    "label" => 'Shipping Address Line 1',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "shipping_address_line_2",
                    "label" => 'Shipping Address Line 2',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "shipping_city",
                    "label" => 'Shipping City',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "shipping_state",
                    "label" => 'Shipping State',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "shipping_postal_code",
                    "label" => 'Shipping Postal Code',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "shipping_country",
                    "label" => 'Shipping Country',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "billing_address_line_1",
                    "label" => 'Billing Address Line 1',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "billing_address_line_2",
                    "label" => 'Billing Address Line 2',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "billing_city",
                    "label" => 'Billing City',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "billing_state",
                    "label" => 'Billing State',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "billing_postal_code",
                    "label" => 'Billing Postal Code',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "billing_country",
                    "label" => 'Billing Country',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
            } elseif ($group_name == "last_products_bought") {
                $group_properties[] = [
                    "name" => "last_product_bought",
                    "label" => 'Last Product Bought',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_product_types_bought",
                    "label" => 'Last Product Types Bought',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought",
                    "label" => 'Last Products Bought',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_html",
                    "label" => 'Last Products Bought HTML',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_total_number_of_products_bought",
                    "label" => 'Last Total Number Of Products Bought',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "product_types_bought",
                    "label" => 'Product Types Bought',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "products_bought",
                    "label" => 'Products Bought',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "total_number_of_products_bought",
                    "label" => 'Total Number Of Products Bought',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_1_image_url",
                    "label" => 'Last Products Bought Product 1 Image URL',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_1_name",
                    "label" => 'Last Products Bought Product 1 Name',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_1_price",
                    "label" => 'Last Products Bought Product 1 Price',
                    "type" => "string",
                    "fieldType" => "text",
                    "showCurrencySymbol" => true,
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_1_url",
                    "label" => 'Last Products Bought Product 1 Url',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_2_image_url",
                    "label" => 'Last Products Bought Product 2 Image URL',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_2_name",
                    "label" => 'Last Products Bought Product 2 Name',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_2_price",
                    "label" => 'Last Products Bought Product 2 Price',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false,
                    "showCurrencySymbol" => true,
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_2_url",
                    "label" => 'Last Products Bought Product 2 Url',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_3_image_url",
                    "label" => 'Last Products Bought Product 3 Image URL',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_3_name",
                    "label" => 'Last Products Bought Product 3 Name',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_3_price",
                    "label" => 'Last Products Bought Product 3 Price',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false,
                    "showCurrencySymbol" => true,
                ];
                $group_properties[] = [
                    "name" => "last_products_bought_product_3_url",
                    "label" => 'Last Products Bought Product 3 Url',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
            } elseif ($group_name == "order") {
                $group_properties[] = [
                    "name" => "last_order_status",
                    "label" => 'Last Order Status',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_order_fulfillment_status",
                    "label" => 'Last Order Fulfillment Status',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_order_tracking_number",
                    "label" => 'Last Order Tracking Number',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_order_tracking_url",
                    "label" => 'Last Order Tracking URL',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_order_shipment_date",
                    "label" => 'Last Order Shipment Date',
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_order_order_number",
                    "label" => 'Last Order Number',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "total_number_of_current_orders",
                    "label" => 'Total Number of Current Orders',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false
                ];
            } elseif ($group_name == "rfm_fields") {
                $group_properties[] = [
                    "name" => "total_value_of_orders",
                    "label" => 'Total Value of Orders',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "average_order_value",
                    "label" => 'Average Order Value',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "total_number_of_orders",
                    "label" => 'Total Number of Orders',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "first_order_value",
                    "label" => 'First Order Value',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "first_order_date",
                    "label" => 'First Order Date',
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_order_value",
                    "label" => 'Last Order Value',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "last_order_date",
                    "label" => 'Last Order Date',
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "average_days_between_orders",
                    "label" => 'Average Days Between Orders',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "account_creation_date",
                    "label" => 'Account Creation Date',
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "monetary_rating",
                    "label" => 'Monetary Rating',
                    "type" => "enumeration",
                    "fieldType" => "select",
                    "formField" => false,
                    "options" => $this->getRfmRating()
                ];
                $group_properties[] = [
                    "name" => "order_frequency_rating",
                    "label" => 'Order Frequency Rating',
                    "type" => "enumeration",
                    "fieldType" => "select",
                    "formField" => false,
                    "options" => $this->getRfmRating()
                ];
                $group_properties[] = [
                    "name" => "order_recency_rating",
                    "label" => 'Order Recency Rating',
                    "type" => "enumeration",
                    "fieldType" => "select",
                    "formField" => false,
                    "options" => $this->getRfmRating()
                ];
            } elseif ($group_name == "categories_bought") {
                $group_properties[] = [
                    "name" => "categories_bought",
                    "label" => 'Categories Bought',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "last_categories_bought",
                    "label" => 'Last Categories Bought',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
            } elseif ($group_name == "skus_bought") {
                $group_properties[] = [
                    "name" => "last_skus_bought",
                    "label" => 'Last SKUs Bought',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "skus_bought",
                    "label" => 'SKUs Bought',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
            } elseif ($group_name == "roi_tracking") {
                $group_properties[] = [
                    "name" => "customer_new_order",
                    "label" => "Customer New Order",
                    "type" => "enumeration",
                    "fieldType" => "select",
                    "options" => $this->newOrderStatus()
                ];
                $group_properties[] = [
                    "name" => "abandoned_cart_recovery_workflow_conversion",
                    "label" => "Abandoned Cart Recovery Workflow Conversion",
                    "type" => "enumeration",
                    "fieldType" => "booleancheckbox",
                    "formField" => false,
                    "options" => $this->getCampaignConversionOption()
                ];
                $group_properties[] = [
                    "name" => "abandoned_cart_recovery_workflow_conversion_amount",
                    "label" => "Abandoned Cart Recovery Workflow Conversion Amount",
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "abandoned_cart_recovery_workflow_conversion_date",
                    "label" => "Abandoned Cart Recovery Workflow Conversion Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "abandoned_cart_recovery_workflow_start_date",
                    "label" => "Abandoned Cart Recovery Workflow Start Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "current_roi_campaign",
                    "label" => "Current ROI Campaign",
                    "type" => "enumeration",
                    "fieldType" => "select",
                    "formField" => false,
                    "options" => $this->getAllCampaigns()
                ];
                $group_properties[] = [
                    "name" => "customer_reengagement_workflow_conversion",
                    "label" => "Customer Reengagement Workflow Conversion",
                    "type" => "enumeration",
                    "fieldType" => "booleancheckbox",
                    "formField" => false,
                    "options" => $this->getCampaignConversionOption()
                ];
                $group_properties[] = [
                    "name" => "customer_reengagement_workflow_conversion_amount",
                    "label" => "Customer Reengagement Workflow Conversion Amount",
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "customer_reengagement_workflow_conversion_date",
                    "label" => "Customer Reengagement Workflow Conversion Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "customer_reengagement_workflow_start_date",
                    "label" => "Customer Reengagement Workflow Start Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "customer_rewards_workflow_conversion",
                    "label" => "Customer Rewards Workflow Conversion",
                    "type" => "enumeration",
                    "fieldType" => "booleancheckbox",
                    "formField" => false,
                    "options" => $this->getCampaignConversionOption()
                ];
                $group_properties[] = [
                    "name" => "customer_rewards_workflow_conversion_amount",
                    "label" => "Customer Rewards Workflow Conversion Amount",
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "customer_rewards_workflow_conversion_date",
                    "label" => "Customer Rewards Workflow Conversion Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "customer_rewards_workflow_start_date",
                    "label" => "Customer Rewards Workflow Start Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "mql_capture_nurture_conversion_conversion",
                    "label" => "MQL Capture, Nurture & Conversion Conversion",
                    "type" => "enumeration",
                    "fieldType" => "booleancheckbox",
                    "formField" => false,
                    "options" => $this->getCampaignConversionOption()
                ];
                $group_properties[] = [
                    "name" => "mql_capture_nurture_conversion_conversion_amount",
                    "label" => "MQL Capture, Nurture & Conversion Conversion Amount",
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "mql_capture_nurture_conversion_conversion_date",
                    "label" => "MQL Capture, Nurture & Conversion Conversion Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "mql_capture_nurture_conversion_start_date",
                    "label" => "MQL Capture, Nurture & Conversion Start date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "new_customer_workflow_conversion",
                    "label" => "New Customer Workflow Conversion",
                    "type" => "enumeration",
                    "fieldType" => "booleancheckbox",
                    "formField" => false,
                    "options" => $this->getCampaignConversionOption()
                ];
                $group_properties[] = [
                    "name" => "new_customer_workflow_conversion_amount",
                    "label" => "New Customer Workflow Conversion Amount",
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "new_customer_workflow_conversion_date",
                    "label" => "New Customer Workflow Conversion Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "new_customer_workflow_start_date",
                    "label" => "New Customer Workflow Start Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "second_purchase_workflow_conversion",
                    "label" => "Second Purchase Workflow Conversion",
                    "type" => "enumeration",
                    "fieldType" => "booleancheckbox",
                    "formField" => false,
                    "options" => $this->getCampaignConversionOption()
                ];
                $group_properties[] = [
                    "name" => "second_purchase_workflow_conversion_amount",
                    "label" => "Second Purchase Workflow Conversion Amount",
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "second_purchase_workflow_conversion_date",
                    "label" => "Second Purchase Workflow Conversion Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "second_purchase_workflow_start_date",
                    "label" => "Second Purchase Workflow Start Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "third_purchase_workflow_conversion",
                    "label" => "Third Purchase Workflow Conversion",
                    "type" => "enumeration",
                    "fieldType" => "booleancheckbox",
                    "formField" => false,
                    "options" => $this->getCampaignConversionOption()
                ];
                $group_properties[] = [
                    "name" => "third_purchase_workflow_conversion_amount",
                    "label" => "Third Purchase Workflow Conversion Amount",
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false,
                    "showCurrencySymbol" => true
                ];
                $group_properties[] = [
                    "name" => "third_purchase_workflow_conversion_date",
                    "label" => "Third Purchase Workflow Conversion Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "third_purchase_workflow_start_date",
                    "label" => "Third Purchase Workflow Start Date",
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
            }
        }
        return $group_properties;
    }

    /**
     * Get List of all work flows
     * @param $name
     * @return array
     */
    public function getAllWorkFlows($name)
    {
        $workFlows = [];
        switch ($name) {
            case "Makewebbetter: MQL to Customer lifecycle stage Conversion":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: MQL to Customer lifecycle stage Conversion",
                    "actions" => [
                        [
                            "type" => "DATE_STAMP_PROPERTY",
                            "propertyName" => "mql_capture_nurture_conversion_start_date",
                            "name" => "MQL Capture, Nurture & Conversion Start Date"
                        ],
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "MQL Nurture & Conversion",
                            "propertyName" => "current_roi_campaign",
                            "name" => "Current ROI Campaign"
                        ],
                        [
                            "type" => "EMAIL",
                            "name" => "MQL Nurturing Email Series - 1"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => 72000000
                        ],
                        [
                            "type" => "EMAIL",
                            "name" => "MQL Nurturing Email Series - 2"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => 432000000
                        ],
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "None",
                            "propertyName" => "current_roi_campaign",
                            "name" => "Current ROI Campaign"
                        ]
                    ],
                    "goalCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "type" => "enumeration",
                                "property" => "lifecyclestage",
                                "value" => "customer",
                                "operator" => "SET_ANY"
                            ]
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: Welcome New Customer & Get a 2nd Order":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: Welcome New Customer & Get a 2nd Order",
                    "actions" => [
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "New Customer Welcome & Get a 2nd Order",
                            "propertyName" => "current_roi_campaign",
                            "name" => "Current ROI Campaign"
                        ],
                        [
                            "type" => "DATE_STAMP_PROPERTY",
                            "propertyName" => "second_purchase_workflow_start_date",
                            "name" => "Second Purchase Workflow Start Date"
                        ],
                        [
                            "type" => "EMAIL",
                            "name" => "Customer Re-engagement Series - 1"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => 172800000
                        ],
                        [
                            "type" => "EMAIL",
                            "name" => "Customer Re-engagement Series - 2"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => 172800000
                        ],
                        [
                            "type" => "EMAIL",
                            "name" => "Customer Re-engagement Series - 3"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => 604800000
                        ],
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "None",
                            "propertyName" => "current_roi_campaign",
                            "name" => "Current ROI Campaign"
                        ]
                    ],
                    "goalCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "type" => "number",
                                "property" => "total_number_of_orders",
                                "value" => 1,
                                "operator" => "GT"
                            ]
                        ]
                    ],
                    "onlyExecOnBizDays" => true
                ];
                break;

            case "Makewebbetter: 2nd Order Thank You & Get a 3rd Order":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: 2nd Order Thank You & Get a 3rd Order",
                    "actions" => [
                        [
                            "type" => "DELAY",
                            "delayMillis" => 172800000
                        ],
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "2nd Order Thank You & Get a 3rd Order",
                            "propertyName" => "current_roi_campaign",
                            "name" => "Current ROI Campaign"
                        ],
                        [
                            "type" => "DATE_STAMP_PROPERTY",
                            "propertyName" => "third_purchase_workflow_start_date",
                            "name" => "Third Purchase Workflow Start Date"
                        ],
                        [
                            "type" => "EMAIL",
                            "name" => "Third Purchase Email Series - 1"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => 172800000
                        ],
                        [
                            "type" => "EMAIL",
                            "name" => "Third Purchase Email Series - 2"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => 172800000
                        ],
                        [
                            "type" => "EMAIL",
                            "name" => "Third Purchase Email Series - 3"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => 432000000
                        ],
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "None",
                            "propertyName" => "current_roi_campaign",
                            "name" => "Current ROI Campaign"
                        ]
                    ],
                    "goalCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "type" => "number",
                                "property" => "total_number_of_orders",
                                "value" => 2,
                                "operator" => "GT"
                            ]
                        ]
                    ],
                    "onlyExecOnBizDays" => true
                ];
                break;

            case "Makewebbetter: 3rd Order Thank You":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: 3rd Order Thank You",
                    "actions" => [
                        [
                            "type" => "DELAY",
                            "delayMillis" => 172800000
                        ],
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "3rd Order Thank You",
                            "propertyName" => "current_roi_campaign",
                            "name" => "Current ROI Campaign"
                        ],
                        ["type" => "EMAIL",
                            "name" => "Third Purchase ThankYou Mail"
                        ],
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "None",
                            "propertyName" => "current_roi_campaign",
                            "name" => "Current ROI Campaign"
                        ]
                    ],
                    "onlyExecOnBizDays" => true
                ];
                break;

            case "Makewebbetter: ROI Calculation":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: ROI Calculation",
                    "enabled" => true,
                    "enrollOnCriteriaUpdate" => true,
                    "actions" => [
                        [
                            "type" => "BRANCH",
                            "filters" => [
                                [
                                    [
                                        "withinTimeMode" => "PAST",
                                        "type" => "enumeration",
                                        "property" => "current_roi_campaign",
                                        "value" => "MQL Nurture & Conversion",
                                        "operator" => "SET_ANY"
                                    ]
                                ]
                            ],
                            "acceptActions" => [
                                [
                                    "type" => "SET_CONTACT_PROPERTY",
                                    "newValue" => "true",
                                    "propertyName" => "mql_capture_nurture_conversion_conversion",
                                    "name" => "Set Property"
                                ],
                                [
                                    "type" => "COPY_PROPERTY",
                                    "sourceProperty" => "last_order_value",
                                    "targetProperty" => "mql_capture_nurture_conversion_conversion_amount",
                                    "targetModel" => "CONTACT",
                                    "name" => "Copy property"
                                ],
                                [
                                    "type" => "DATE_STAMP_PROPERTY",
                                    "propertyName" => "mql_capture_nurture_conversion_conversion_date",
                                    "model" => "CONTACT",
                                    "name" => "MQL Capture, Nurture & Conversion Conversion Date"
                                ],
                                [
                                    "type" => "SET_CONTACT_PROPERTY",
                                    "newValue" => "None",
                                    "propertyName" => "current_roi_campaign",
                                    "name" => "Set Property"
                                ]
                            ],
                            "rejectActions" => [
                                [
                                    "type" => "BRANCH",
                                    "filters" => [
                                        [
                                            [
                                                "withinTimeMode" => "PAST",
                                                "type" => "enumeration",
                                                "property" => "current_roi_campaign",
                                                "value" => "New Customer Welcome & Get a 2nd Order",
                                                "operator" => "SET_ANY"
                                            ]
                                        ]
                                    ],
                                    "acceptActions" => [
                                        [
                                            "type" => "SET_CONTACT_PROPERTY",
                                            "newValue" => "true",
                                            "propertyName" => "new_customer_workflow_conversion",
                                            "name" => "Set Property"
                                        ],
                                        [
                                            "type" => "COPY_PROPERTY",
                                            "sourceProperty" => "last_order_value",
                                            "targetProperty" => "new_customer_workflow_conversion_amount",
                                            "targetModel" => "CONTACT",
                                            "name" => "Copy property"
                                        ],
                                        [
                                            "type" => "DATE_STAMP_PROPERTY",
                                            "propertyName" => "new_customer_workflow_conversion_date",
                                            "model" => "CONTACT",
                                            "name" => "New Customer Workflow Conversion Date"
                                        ],
                                        [
                                            "type" => "SET_CONTACT_PROPERTY",
                                            "newValue" => "None",
                                            "propertyName" => "current_roi_campaign",
                                            "name" => "Set Property"
                                        ]
                                    ],
                                    "rejectActions" => [
                                        [
                                            "type" => "BRANCH",
                                            "filters" => [
                                                [
                                                    [
                                                        "withinTimeMode" => "PAST",
                                                        "type" => "enumeration",
                                                        "property" => "current_roi_campaign",
                                                        "value" => "2nd Order Thank You & Get a 3rd Order",
                                                        "operator" => "SET_ANY"
                                                    ]
                                                ]
                                            ],
                                            "acceptActions" => [
                                                [
                                                    "type" => "SET_CONTACT_PROPERTY",
                                                    "newValue" => "true",
                                                    "propertyName" => "second_purchase_workflow_conversion",
                                                    "name" => "Set Property"
                                                ],
                                                [
                                                    "type" => "COPY_PROPERTY",
                                                    "sourceProperty" => "last_order_value",
                                                    "targetProperty" => "second_purchase_workflow_conversion_amount",
                                                    "targetModel" => "CONTACT",
                                                    "name" => "Copy property"
                                                ],
                                                [
                                                    "type" => "DATE_STAMP_PROPERTY",
                                                    "propertyName" => "second_purchase_workflow_conversion_date",
                                                    "model" => "CONTACT",
                                                    "name" => "Second Purchase Workflow Conversion Date"
                                                ],
                                                [
                                                    "type" => "SET_CONTACT_PROPERTY",
                                                    "newValue" => "None",
                                                    "propertyName" => "current_roi_campaign",
                                                    "name" => "Set Property"
                                                ]
                                            ],
                                            "rejectActions" => [
                                                [
                                                    "type" => "BRANCH",
                                                    "filters" => [
                                                        [
                                                            [
                                                                "withinTimeMode" => "PAST",
                                                                "type" => "enumeration",
                                                                "property" => "current_roi_campaign",
                                                                "value" => "3rd Order Thank You",
                                                                "operator" => "SET_ANY"
                                                            ]
                                                        ]
                                                    ],
                                                    "acceptActions" => [
                                                        [
                                                            "type" => "SET_CONTACT_PROPERTY",
                                                            "newValue" => "true",
                                                            "propertyName" => "third_purchase_workflow_conversion",
                                                            "name" => "Set Property"
                                                        ],
                                                        [
                                                            "type" => "COPY_PROPERTY",
                                                            "sourceProperty" => "last_order_value",
                                                            "targetProperty" =>
                                                                "third_purchase_workflow_conversion_amount",
                                                            "targetModel" => "CONTACT",
                                                            "name" => "COPY_PROPERTY"
                                                        ],
                                                        [
                                                            "type" => "DATE_STAMP_PROPERTY",
                                                            "propertyName" => "third_purchase_workflow_conversion_date",
                                                            "model" => "CONTACT",
                                                            "name" => "Third Purchase Workflow Conversion Date"
                                                        ],
                                                        [
                                                            "type" => "SET_CONTACT_PROPERTY",
                                                            "newValue" => "None",
                                                            "propertyName" => "current_roi_campaign",
                                                            "name" => "Set Property"
                                                        ]
                                                    ],
                                                    "rejectActions" => [
                                                        [
                                                            "type" => "BRANCH",
                                                            "filters" => [
                                                                [
                                                                    [
                                                                        "withinTimeMode" => "PAST",
                                                                        "type" => "enumeration",
                                                                        "property" => "current_roi_campaign",
                                                                        "value" => "Customer Reengagement",
                                                                        "operator" => "SET_ANY"
                                                                    ]
                                                                ]
                                                            ],
                                                            "acceptActions" => [
                                                                [
                                                                    "type" => "SET_CONTACT_PROPERTY",
                                                                    "newValue" => "true",
                                                                    "propertyName" =>
                                                                        "customer_reengagement_workflow_conversion",
                                                                    "name" => "Set Property"
                                                                ],
                                                                [
                                                                    "type" => "COPY_PROPERTY",
                                                                    "sourceProperty" => "last_order_value",
                                                                    "targetProperty" =>
                                                                        "customer_reengagement_workflow_conversion_amount",
                                                                    "targetModel" => "CONTACT",
                                                                    "name" => "Copy property"
                                                                ],
                                                                [
                                                                    "type" => "DATE_STAMP_PROPERTY",
                                                                    "propertyName" =>
                                                                        "customer_reengagement_workflow_conversion_date",
                                                                    "name" =>
                                                                        "Customer Reengagement Workflow Conversion Date"
                                                                ],
                                                                [
                                                                    "type" => "SET_CONTACT_PROPERTY",
                                                                    "newValue" => "None",
                                                                    "propertyName" => "current_roi_campaign",
                                                                    "name" => "Set Property"
                                                                ]
                                                            ],
                                                            "rejectActions" => [
                                                                [
                                                                    "type" => "BRANCH",
                                                                    "filters" => [
                                                                        [
                                                                            [
                                                                                "withinTimeMode" => "PAST",
                                                                                "type" => "enumeration",
                                                                                "property" => "current_roi_campaign",
                                                                                "value" => "Customer Rewards",
                                                                                "operator" => "SET_ANY"
                                                                            ]
                                                                        ]
                                                                    ],
                                                                    "acceptActions" => [
                                                                        [
                                                                            "type" => "SET_CONTACT_PROPERTY",
                                                                            "newValue" => "true",
                                                                            "propertyName" =>
                                                                                "customer_rewards_workflow_conversion",
                                                                            "name" => "Set Property"
                                                                        ],
                                                                        [
                                                                            "type" => "COPY_PROPERTY",
                                                                            "sourceProperty" => "last_order_value",
                                                                            "targetProperty" =>
                                                                                "customer_rewards_workflow_conversion_amount",
                                                                            "targetModel" => "CONTACT",
                                                                            "name" => "Copy property"
                                                                        ],
                                                                        [
                                                                            "type" => "DATE_STAMP_PROPERTY",
                                                                            "propertyName" =>
                                                                                "customer_rewards_workflow_conversion_date",
                                                                            "model" => "CONTACT",
                                                                            "name" =>
                                                                                "Customer Rewards Workflow Conversion Date"
                                                                        ],
                                                                        [
                                                                            "type" => "SET_CONTACT_PROPERTY",
                                                                            "newValue" => "None",
                                                                            "propertyName" => "current_roi_campaign",
                                                                            "name" => "Set Property"
                                                                        ]
                                                                    ],
                                                                    "rejectActions" => [
                                                                        [
                                                                            "type" => "BRANCH",
                                                                            "filters" => [
                                                                                [
                                                                                    [
                                                                                        "withinTimeMode" => "PAST",
                                                                                        "type" => "enumeration",
                                                                                        "property" =>
                                                                                            "current_roi_campaign",
                                                                                        "value" =>
                                                                                            "Abandoned Cart Recovery",
                                                                                        "operator" => "SET_ANY"
                                                                                    ]
                                                                                ]
                                                                            ],
                                                                            "acceptActions" => [
                                                                                [
                                                                                    "type" => "SET_CONTACT_PROPERTY",
                                                                                    "newValue" => "true",
                                                                                    "propertyName" =>
                                                                                        "abandoned_cart_recovery_workflow_conversion",
                                                                                    "name" => "Set Property"
                                                                                ],
                                                                                [
                                                                                    "type" => "COPY_PROPERTY",
                                                                                    "sourceProperty" =>
                                                                                        "last_order_value",
                                                                                    "targetProperty" =>
                                                                                        "abandoned_cart_recovery_workflow_conversion_amount",
                                                                                    "targetModel" => "CONTACT",
                                                                                    "name" => "Copy Property"
                                                                                ],
                                                                                [
                                                                                    "type" => "DATE_STAMP_PROPERTY",
                                                                                    "propertyName" =>
                                                                                        "abandoned_cart_recovery_workflow_conversion_date",
                                                                                    "model" => "CONTACT",
                                                                                    "name" =>
                                                                                        "Abandoned Cart Recovery Workflow Conversion Date"
                                                                                ],
                                                                                [
                                                                                    "type" => "SET_CONTACT_PROPERTY",
                                                                                    "newValue" => "None",
                                                                                    "propertyName" =>
                                                                                        "current_roi_campaign",
                                                                                    "name" => "Set Property"
                                                                                ]
                                                                            ]
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: Order Workflow":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: Order Workflow",
                    "enabled" => true,
                    "actions" => [
                        [
                            "type" => "DELAY",
                            "delayMillis" => 300000
                        ],
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "",
                            "propertyName" => "lifecyclestage",
                            "name" => "Lifecycle stage"
                        ],
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => "customer",
                            "propertyName" => "lifecyclestage",
                            "name" => "Lifecycle stage"
                        ],
                        [
                            "type" => "WORKFLOW_ENROLLMENT",
                            "workflowId" => $this->getUserOption("hubspot/workflow/roi_calculation"),
                            "name" => "Makewebbetter: ROI Calculation"
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: set Order Recency 1 Ratings":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: set Order Recency 1 Ratings",
                    "enabled" => true,
                    "actions" => [
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => 1,
                            "propertyName" => "order_recency_rating",
                            "name" => "Order Recency Rating"
                        ]
                    ],
                    "goalCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "filterFamily" => "Workflow",
                                "workflowId" => $this->getUserOption("hubspot/workflow/order_workflow"),
                                "operator" => "ACTIVE_IN_WORKFLOW"
                            ]
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: set Order Recency 2 Ratings":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: set Order Recency 2 Ratings",
                    "enabled" => true,
                    "actions" => [
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => 2,
                            "propertyName" => "order_recency_rating",
                            "name" => "Order Recency Rating"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => "31104000000"
                        ],
                        [
                            "type" => "WORKFLOW_ENROLLMENT",
                            "name" => "Makewebbetter: set Order Recency 1 Ratings",
                            "workflowId" => $this->getUserOption("hubspot/workflow/order_recency1")
                        ]
                    ],
                    "goalCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "filterFamily" => "Workflow",
                                "workflowId" => $this->getUserOption("hubspot/workflow/order_workflow"),
                                "operator" => "ACTIVE_IN_WORKFLOW"
                            ]
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: set Order Recency 3 Ratings":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: set Order Recency 3 Ratings",
                    "enabled" => true,
                    "actions" => [
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => 3,
                            "propertyName" => "order_recency_rating",
                            "name" => "Order Recency Rating"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => "15552000000"
                        ],
                        [
                            "type" => "WORKFLOW_ENROLLMENT",
                            "name" => "Makewebbetter: set Order Recency 2 Ratings",
                            "workflowId" => $this->getUserOption("hubspot/workflow/order_recency2")
                        ]
                    ],
                    "goalCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "filterFamily" => "Workflow",
                                "workflowId" => $this->getUserOption("hubspot/workflow/order_workflow"),
                                "operator" => "ACTIVE_IN_WORKFLOW"
                            ]
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: set Order Recency 4 Ratings":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: set Order Recency 4 Ratings",
                    "enabled" => true,
                    "actions" => [
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => 4,
                            "propertyName" => "order_recency_rating",
                            "name" => "Order Recency Rating"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => "7776000000"
                        ],
                        [
                            "type" => "WORKFLOW_ENROLLMENT",
                            "name" => "Makewebbetter: set Order Recency 3 Ratings",
                            "workflowId" => $this->getUserOption("hubspot/workflow/order_recency3")
                        ]
                    ],
                    "goalCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "filterFamily" => "Workflow",
                                "workflowId" => $this->getUserOption("hubspot/workflow/order_workflow"),
                                "operator" => "ACTIVE_IN_WORKFLOW"
                            ]
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: set Order Recency 5 Ratings":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: set Order Recency 5 Ratings",
                    "enabled" => true,
                    "actions" => [
                        [
                            "type" => "SET_CONTACT_PROPERTY",
                            "newValue" => 5,
                            "propertyName" => "order_recency_rating",
                            "name" => "Order Recency Rating"
                        ],
                        [
                            "type" => "DELAY",
                            "delayMillis" => "2592000000"
                        ],
                        [
                            "type" => "WORKFLOW_ENROLLMENT",
                            "name" => "Makewebbetter: set Order Recency 4 Ratings",
                            "workflowId" => $this->getUserOption("hubspot/workflow/order_recency4")
                        ]
                    ],
                    "goalCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "filterFamily" => "Workflow",
                                "workflowId" => $this->getUserOption("hubspot/workflow/order_workflow"),
                                "operator" => "ACTIVE_IN_WORKFLOW"
                            ]
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: Update Historical Order Recency Rating":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: Update Historical Order Recency Rating",
                    "enabled" => true,
                    "actions" => [
                        [
                            "type" => "BRANCH",
                            "filters" => [
                                [
                                    [
                                        "withinLastTime" => 31,
                                        "withinLastTimeUnit" => "DAYS",
                                        "withinLastDays" => 31,
                                        "withinTimeMode" => "PAST",
                                        "type" => "date",
                                        "property" => "last_order_date",
                                        "operator" => "WITHIN_TIME"
                                    ]
                                ]
                            ],
                            "acceptActions" => [
                                [
                                    "type" => "WORKFLOW_ENROLLMENT",
                                    "name" => "Makewebbetter: set Order Recency 5 Ratings",
                                    "workflowId" => $this->getUserOption(
                                        "hubspot/workflow/order_recency5"
                                    )
                                ]
                            ],
                            "rejectActions" => [
                                [
                                    "type" => "BRANCH",
                                    "filters" => [
                                        [
                                            [
                                                "withinLastTime" => 30,
                                                "withinLastTimeUnit" => "DAYS",
                                                "reverseWithinTimeWindow" => true,
                                                "withinLastDays" => 30,
                                                "withinTimeMode" => "PAST",
                                                "type" => "date",
                                                "property" => "last_order_date",
                                                "operator" => "WITHIN_TIME"
                                            ],
                                            [
                                                "withinLastTime" => 91,
                                                "withinLastTimeUnit" => "DAYS",
                                                "withinLastDays" => 91,
                                                "withinTimeMode" => "PAST",
                                                "type" => "date",
                                                "property" => "last_order_date",
                                                "operator" => "WITHIN_TIME"
                                            ]
                                        ]
                                    ],
                                    "acceptActions" => [
                                        [
                                            "type" => "WORKFLOW_ENROLLMENT",
                                            "name" => "Makewebbetter: set Order Recency 4 Ratings",
                                            "workflowId" => $this->getUserOption(
                                                "hubspot/workflow/order_recency4"
                                            )
                                        ]
                                    ],
                                    "rejectActions" => [
                                        [
                                            "type" => "BRANCH",
                                            "filters" => [
                                                [
                                                    [
                                                        "withinLastTime" => 90,
                                                        "withinLastTimeUnit" => "DAYS",
                                                        "reverseWithinTimeWindow" => true,
                                                        "withinLastDays" => 90,
                                                        "withinTimeMode" => "PAST",
                                                        "type" => "date",
                                                        "property" => "last_order_date",
                                                        "operator" => "WITHIN_TIME"
                                                    ],
                                                    [
                                                        "withinLastTime" => 181,
                                                        "withinLastTimeUnit" => "DAYS",
                                                        "withinLastDays" => 181,
                                                        "withinTimeMode" => "PAST",
                                                        "type" => "date",
                                                        "property" => "last_order_date",
                                                        "operator" => "WITHIN_TIME"
                                                    ]
                                                ]
                                            ],
                                            "acceptActions" => [
                                                [
                                                    "type" => "WORKFLOW_ENROLLMENT",
                                                    "name" => "Makewebbetter: set Order Recency 3 Ratings",
                                                    "workflowId" => $this->getUserOption(
                                                        "hubspot/workflow/order_recency3"
                                                    )
                                                ]
                                            ],
                                            "rejectActions" => [
                                                [
                                                    "type" => "BRANCH",
                                                    "filters" => [
                                                        [
                                                            [
                                                                "withinLastTime" => 180,
                                                                "withinLastTimeUnit" => "DAYS",
                                                                "reverseWithinTimeWindow" => true,
                                                                "withinLastDays" => 180,
                                                                "withinTimeMode" => "PAST",
                                                                "type" => "date",
                                                                "property" => "last_order_date",
                                                                "operator" => "WITHIN_TIME"
                                                            ],
                                                            [
                                                                "withinLastTime" => 365,
                                                                "withinLastTimeUnit" => "DAYS",
                                                                "withinLastDays" => 365,
                                                                "withinTimeMode" => "PAST",
                                                                "type" => "date",
                                                                "property" => "last_order_date",
                                                                "operator" => "WITHIN_TIME"
                                                            ]
                                                        ]
                                                    ],
                                                    "acceptActions" => [
                                                        [
                                                            "type" => "WORKFLOW_ENROLLMENT",
                                                            "name" => "Makewebbetter: set Order Recency 2 Ratings",
                                                            "workflowId" => $this->getUserOption(
                                                                "hubspot/workflow/order_recency2"
                                                            )
                                                        ]
                                                    ],
                                                    "rejectActions" => [
                                                        [
                                                            "type" => "WORKFLOW_ENROLLMENT",
                                                            "name" => "Makewebbetter: set Order Recency 1 Ratings",
                                                            "workflowId" => $this->getUserOption(
                                                                "hubspot/workflow/order_recency1"
                                                            )
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: Enroll Customers for Recency Settings":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: Enroll Customers for Recency Settings",
                    "enabled" => true,
                    "actions" => [
                        [
                            "type" => "WORKFLOW_ENROLLMENT",
                            "workflowId" => $this->getUserOption(
                                "hubspot/workflow/historical_order_recency"
                            ),
                            "name" => "Makewebbetter: Update Historical Order Recency Rating"
                        ]
                    ],
                    "enrollOnCriteriaUpdate" => true,
                    "segmentCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "filterFamily" => "PropertyValue",
                                "type" => "enumeration",
                                "property" => "lifecyclestage",
                                "value" => "customer",
                                "operator" => "SET_ANY"
                            ]
                        ]
                    ],
                    "reEnrollmentTriggerSets" => [
                        [
                            [
                                "type" => "CONTACT_PROPERTY_NAME",
                                "id" => "lifecyclestage"
                            ],
                            [
                                "type" => "CONTACT_PROPERTY_VALUE",
                                "id" => "customer"
                            ]
                        ]
                    ]
                ];
                break;

            case "Makewebbetter: After order Workflow":
                $workFlows = [
                    "type" => "DRIP_DELAY",
                    "name" => "Makewebbetter: After order Workflow",
                    "actions" => [
                        [
                            "type" => "BRANCH",
                            "filters" => [
                                [
                                    [
                                        "withinTimeMode" => "PAST",
                                        "type" => "number",
                                        "property" => "total_number_of_orders",
                                        "value" => 3,
                                        "operator" => "EQ"
                                    ]
                                ]
                            ],
                            "acceptActions" => [
                                [
                                    "type" => "WORKFLOW_ENROLLMENT",
                                    "name" => "Makewebbetter: 3rd Order Thank You",
                                    "workflowId" => $this->getUserOption("hubspot/workflow/3rd_order_thank")
                                ],
                                [
                                    "type" => "SET_CONTACT_PROPERTY",
                                    "newValue" => "",
                                    "propertyName" => "customer_new_order",
                                    "name" => "Customer New Order"
                                ]
                            ],
                            "rejectActions" => [
                                [
                                    "type" => "BRANCH",
                                    "filters" => [
                                        [
                                            [
                                                "withinTimeMode" => "PAST",
                                                "type" => "number",
                                                "property" => "total_number_of_orders",
                                                "value" => 2,
                                                "operator" => "EQ"
                                            ]
                                        ]
                                    ],
                                    "acceptActions" => [
                                        [
                                            "type" => "WORKFLOW_ENROLLMENT",
                                            "name" => "Makewebbetter: 2nd Order Thank You & Get a 3rd Order",
                                            "workflowId" => $this->getUserOption(
                                                "hubspot/workflow/2nd_order_thank_3rd_order"
                                            )
                                        ],
                                        [
                                            "type" => "SET_CONTACT_PROPERTY",
                                            "newValue" => "",
                                            "propertyName" => "customer_new_order",
                                            "name" => "Customer New Order"
                                        ]
                                    ],
                                    "rejectActions" => [
                                        [
                                            "type" => "BRANCH",
                                            "filters" => [
                                                [
                                                    [
                                                        "withinTimeMode" => "PAST",
                                                        "type" => "number",
                                                        "property" => "total_number_of_orders",
                                                        "value" => 1,
                                                        "operator" => "EQ"
                                                    ]
                                                ]
                                            ],
                                            "acceptActions" => [
                                                [
                                                    "type" => "WORKFLOW_ENROLLMENT",
                                                    "name" => "Makewebbetter: Welcome New Customer & Get a 2nd Order",
                                                    "workflowId" => $this->getUserOption(
                                                        "hubspot/workflow/new_customer_2nd_order"
                                                    )
                                                ],
                                                [
                                                    "type" => "SET_CONTACT_PROPERTY",
                                                    "newValue" => "",
                                                    "propertyName" => "customer_new_order",
                                                    "name" => "Customer New Order"
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "enrollOnCriteriaUpdate" => true,
                    "segmentCriteria" => [
                        [
                            [
                                "withinTimeMode" => "PAST",
                                "type" => "enumeration",
                                "property" => "customer_new_order",
                                "value" => "yes",
                                "operator" => "SET_ANY"
                            ]
                        ]
                    ],
                    "reEnrollmentTriggerSets" => [
                        [
                            [
                                "type" => "CONTACT_PROPERTY_NAME",
                                "id" => "customer_new_order"
                            ],
                            [
                                "type" => "CONTACT_PROPERTY_VALUE",
                                "id" => "yes"
                            ]
                        ]
                    ]
                ];
                break;
        }
        return $workFlows;
    }

    /**
     * get all active lists for HubSpot
     * @since 1.0.0
     * @return array of active lists
     */
    public function getAllLists()
    {
        $lists = [];
        $lists[] = [
            "name" => "Best Customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 5,
                        "property" => "monetary_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "EQ",
                        "value" => 5,
                        "property" => "order_frequency_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "EQ",
                        "value" => 5,
                        "property" => "order_recency_rating",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Big Spenders",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 5,
                        "property" => "monetary_rating",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Loyal Customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 5,
                        "property" => "order_frequency_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "EQ",
                        "value" => 5,
                        "property" => "order_recency_rating",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Churning Customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 5,
                        "property" => "monetary_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "EQ",
                        "value" => 5,
                        "property" => "order_frequency_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "EQ",
                        "value" => 1,
                        "property" => "order_recency_rating",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Low Value Lost Customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 1,
                        "property" => "monetary_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "EQ",
                        "value" => 1,
                        "property" => "order_frequency_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "EQ",
                        "value" => 1,
                        "property" => "order_recency_rating",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "New Customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 1,
                        "property" => "order_frequency_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "EQ",
                        "value" => 1,
                        "property" => "order_recency_rating",
                        "type" => "enumeration"
                    ]
                ],
            ]
        ];
        $lists[] = [
            "name" => "Customers needing attention",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 3,
                        "property" => "monetary_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "EQ",
                        "value" => 3,
                        "property" => "order_frequency_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "SET_ANY",
                        "value" => implode(';', [1, 2]),
                        "property" => "order_recency_rating",
                        "type" => "enumeration"
                    ]
                ],
            ]
        ];
        $lists[] = [
            "name" => "About to Sleep",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "SET_ANY",
                        "value" => implode(';', [1, 2]),
                        "property" => "monetary_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "SET_ANY",
                        "value" => implode(';', [1, 2]),
                        "property" => "order_frequency_rating",
                        "type" => "enumeration"
                    ],
                    [
                        "operator" => "SET_ANY",
                        "value" => implode(';', [1, 2]),
                        "property" => "order_recency_rating",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Mid Spenders",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 3,
                        "property" => "monetary_rating",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Low Spenders",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 1,
                        "property" => "monetary_rating",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Newsletter Subscriber",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 'yes',
                        "property" => "newsletter_subscription",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "One time purchase customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 1,
                        "property" => "total_number_of_orders",
                        "type" => "number"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Two time purchase customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 2,
                        "property" => "total_number_of_orders",
                        "type" => "number"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Three time purchase customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 3,
                        "property" => "total_number_of_orders",
                        "type" => "number"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Bought four or more times",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => 4,
                        "property" => "total_number_of_orders",
                        "type" => "number"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Leads",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => "lead",
                        "property" => "lifecyclestage",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Marketing Qualified Leads",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => "marketingqualifiedlead",
                        "property" => "lifecyclestage",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "EQ",
                        "value" => "customer",
                        "property" => "lifecyclestage",
                        "type" => "enumeration"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Engaged Customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "operator" => "WITHIN_TIME",
                        "withinLastTime" => 60,
                        "withinLastTimeUnit" => "DAYS",
                        "withinLastDays" => 60,
                        "withinTimeMode" => "PAST",
                        "property" => "last_order_date",
                        "type" => "date"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "DisEngaged Customers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "withinLastTime" => 60,
                        "withinLastTimeUnit" => "DAYS",
                        "reverseWithinTimeWindow" => true,
                        "withinLastDays" => 60,
                        "withinTimeMode" => "PAST",
                        "type" => "date",
                        "operator" => "WITHIN_TIME",
                        "property" => "last_order_date"
                    ],
                    [
                        "withinLastTime" => 180,
                        "withinLastTimeUnit" => "DAYS",
                        "withinLastDays" => 180,
                        "withinTimeMode" => "PAST",
                        "type" => "date",
                        "operator" => "WITHIN_TIME",
                        "property" => "last_order_date"
                    ]
                ]
            ]
        ];
        $lists[] = [
            "name" => "Repeat Buyers",
            "dynamic" => true,
            "filters" => [
                [
                    [
                        "type" => "number",
                        "operator" => "GTE",
                        "property" => "total_number_of_orders",
                        "value" => 5
                    ],
                    [
                        "type" => "number",
                        "operator" => "LTE",
                        "property" => "average_days_between_orders",
                        "value" => 30
                    ]
                ]
            ]
        ];
        return $lists;
    }

    /**
     * Get code of of workflow and name
     * @return array
     */
    public function getWorkFlowNames()
    {
        return [
            'hubspot/workflow/mql_stage_conversion' => "Makewebbetter: MQL to Customer lifecycle stage Conversion",
            'hubspot/workflow/new_customer_2nd_order' => "Makewebbetter: Welcome New Customer & Get a 2nd Order",
            'hubspot/workflow/2nd_order_thank_3rd_order' => "Makewebbetter: 2nd Order Thank You & Get a 3rd Order",
            'hubspot/workflow/3rd_order_thank' => "Makewebbetter: 3rd Order Thank You",
            'hubspot/workflow/roi_calculation' => "Makewebbetter: ROI Calculation",
            'hubspot/workflow/order_workflow' => "Makewebbetter: Order Workflow",
            'hubspot/workflow/order_recency1' => "Makewebbetter: set Order Recency 1 Ratings",
            'hubspot/workflow/order_recency2' => "Makewebbetter: set Order Recency 2 Ratings",
            'hubspot/workflow/order_recency3' => "Makewebbetter: set Order Recency 3 Ratings",
            'hubspot/workflow/order_recency4' => "Makewebbetter: set Order Recency 4 Ratings",
            'hubspot/workflow/order_recency5' => "Makewebbetter: set Order Recency 5 Ratings",
            'hubspot/workflow/historical_order_recency' => "Makewebbetter: Update Historical Order Recency Rating",
            'hubspot/workflow/enroll_customer_recency' => "Makewebbetter: Enroll Customers for Recency Settings",
            'hubspot/workflow/after_order_workflow' => "Makewebbetter: After order Workflow"
        ];
    }

    /**
     * Get the user config data from the path
     * @param $key
     * @return bool
     */
    public function getUserOption($key)
    {
        if (!$key) {
            return null;
        }
        if (isset($this->workflowsId[$key])) {
            return $this->workflowsId[$key];
        }
        return $this->resourceConfig->getConfigValue($key);
    }

    /**
     * Set the user config data
     * @param $key
     * @param $value
     */
    public function setUserOption($key, $value)
    {
        if (!$key) {
            return null;
        }
        $this->workflowsId[$key] = $value;
        return $this->resourceConfig->saveConfig($key, $value);
    }

    public function getDealProperty($groupName)
    {
        $group_properties = [];
        if (!empty($groupName)) {
            if ($groupName == "dealinformation") {
                $group_properties[] = [
                    "name" => "order_creation_date",
                    "label" => 'Order Creation Date',
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "order_shipped_date",
                    "label" => 'Order Shipped Date',
                    "type" => "date",
                    "fieldType" => "date",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "contact_is_guest",
                    "label" => 'Contact Is Guest',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];
                $group_properties[] = [
                    "name" => "contact_email",
                    "label" => 'Contact Email',
                    "type" => "string",
                    "fieldType" => "text",
                    "formField" => true
                ];

                $group_properties[] = [
                    "name" => "discount_amount",
                    "label" => 'Discount Savings',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "shipment_ids",
                    "label" => 'Shipment IDs',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "order_number",
                    "label" => 'Order number',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "tax_amount",
                    "label" => 'Tax amount',
                    "type" => "number",
                    "fieldType" => "number",
                    "formField" => false
                ];
                $group_properties[] = [
                    "name" => "abandoned_cart_url",
                    "label" => 'Abandoned Cart Url',
                    "type" => "string",
                    "fieldType" => "textarea",
                    "formField" => false
                ];
            }
        }
        return $group_properties;
    }
}
