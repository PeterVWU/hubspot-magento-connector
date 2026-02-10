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

namespace Makewebbetter\HubIntegration\Model;

class Properties
{
    /**
     * @param $group
     * @return array
     */
    public function getGroupProperty($group)
    {
        $property = [];
        switch ($group) {
            case 'PRODUCT':
                $property = $this->getDefaultProductProperties();
                break;

            case 'CONTACT':
                $property = $this->getDefaultContactProperties();
                break;

            case 'DEAL':
                $property = $this->getDefaultDealProperties();
                break;

            case 'LINE_ITEM':
                $property = $this->getDefaultLineItemProperties();
                break;
        }

        return $property;
    }

    public function getDefaultContactProperties(){
        $defaultContactProperties = [
            "email" => "email",
            "firstname" => "firstname",
            "lastname" => "lastname",
            "company" => "company",
            "phone" => "phone",
            "address" => "address",
            "city" => "city",
            "state" => "state",
            "country" => "country",
            "zip" => "zip",
            "lifecyclestage" => "lifecyclestage",
            "customer_group" => "customer_group",
            'newsletter_subscription' => "newsletter_subscription",
            'shopping_cart_customer_id' => "shopping_cart_customer_id",
            'shipping_address_line_1' => "shipping_address_line_1",
            'shipping_address_line_2' => "shipping_address_line_2",
            'shipping_city' => "shipping_city",
            'shipping_state' => "shipping_state",
            'shipping_postal_code' => "shipping_postal_code",
            'shipping_country' => "shipping_country",
            'billing_address_line_1' => "billing_address_line_1",
            'billing_address_line_2' => "billing_address_line_2",
            'billing_city' => "billing_city",
            'billing_state' => "billing_state",
            'billing_postal_code' => "billing_postal_code",
            'billing_country' => "billing_country",
            'last_product_bought' => "last_product_bought",
            'last_product_types_bought' => "last_product_types_bought",
            'last_products_bought' => "last_products_bought",
            'last_products_bought_html' => "last_products_bought_html",
            'last_total_number_of_products_bought' => "last_total_number_of_products_bought",
            'product_types_bought' => "product_types_bought",
            'products_bought' => "products_bought",
            'total_number_of_products_bought' => "total_number_of_products_bought",
            'last_products_bought_product_1_image_url' => "last_products_bought_product_1_image_url",
            'last_products_bought_product_1_name' => "last_products_bought_product_1_name",
            'last_products_bought_product_1_price' => "last_products_bought_product_1_price",
            'last_products_bought_product_1_url' => "last_products_bought_product_1_url",
            'last_products_bought_product_2_image_url' => "last_products_bought_product_2_image_url",
            'last_products_bought_product_2_name' => "last_products_bought_product_2_name",
            'last_products_bought_product_2_price' => "last_products_bought_product_2_price",
            'last_products_bought_product_2_url' => "last_products_bought_product_2_url",
            'last_products_bought_product_3_image_url' => "last_products_bought_product_3_image_url",
            'last_products_bought_product_3_name' => "last_products_bought_product_3_name",
            'last_products_bought_product_3_price' => "last_products_bought_product_3_price",
            'last_products_bought_product_3_url' => "last_products_bought_product_3_url",
            'last_order_status' => "last_order_status",
            'last_order_fulfillment_status' => "last_order_fulfillment_status",
            'last_order_tracking_number' => "last_order_tracking_number",
            'last_order_tracking_url' => "last_order_tracking_url",
            'last_order_shipment_date' => "last_order_shipment_date",
            'last_order_order_number' => "last_order_order_number",
            'total_number_of_current_orders' => "total_number_of_current_orders",
            'categories_bought' => "categories_bought",
            'last_categories_bought' => "last_categories_bought",
            'last_skus_bought' => "last_skus_bought",
            'skus_bought' => "skus_bought",
            'total_value_of_orders' => "total_value_of_orders",
            'average_order_value' => "average_order_value",
            'total_number_of_orders' => "total_number_of_orders",
            'first_order_value' => "first_order_value",
            'first_order_date' => "first_order_date",
            'last_order_value' => "last_order_value",
            'last_order_date' => "last_order_date",
            'average_days_between_orders' => "average_days_between_orders",
            'account_creation_date' => "account_creation_date",
            'monetary_rating' => "monetary_rating",
            'order_frequency_rating' => "order_frequency_rating",
            'order_recency_rating' => "order_recency_rating"
        ];
        return $defaultContactProperties;
    }


    public function getDefaultProductProperties(){
        $property = [
            "name" => 'name',
            "hs_images" => 'hs_images',
            "hs_sku" => 'hs_sku',
            "price" => 'price',
            "description" => 'description'
        ];
        return $property;
    }

    public function getDefaultDealProperties(){
        $property = [
            "dealstage" => 'dealstage',
            "dealname" => 'dealname',
            "amount" => 'amount',
            "pipeline" => 'pipeline',
            "abandoned_cart_url" => 'abandoned_cart_url',
            "discount_amount" => 'discount_amount',
            "order_number" => 'order_number',
            "tax_amount" => 'tax_amount',
            "shipment_ids" => 'shipment_ids',
            "contact_ids" => 'contact_ids',
            "order_creation_date" => 'order_creation_date',
            "order_shipped_date" => 'order_shipped_date',
            "contact_is_guest" => 'contact_is_guest',
            "contact_email" => 'contact_email'
        ];
        return $property;
    }
    public function getDefaultLineItemProperties(){
        $property = [
            "hs_product_id" => 'hs_product_id',
            //"discount_amount" => 'discount_amount',
            "quantity" => 'quantity',
            "price" => 'price',
            "name" => 'name',
            "hs_sku" => 'hs_sku'
        ];
        return $property;
    }
}
