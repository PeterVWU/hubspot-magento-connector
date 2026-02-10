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

class Ecomdetails extends AbstractHelper
{
    /**
     * @var HubConfig
     */
    public $resourceConfig;

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
     * Get get_ecomm_deal_stages to create
     * @return array
     */
    public function getEcommDealStages()
    {
        return array(
                array(
                    "label" => "Checkout Abandoned",
                    "displayOrder" => 0,
                    "metadata" => array(
                            "isClosed" => false,
                            "probability" => 0.1
                        )
                    ),
                array(
                    "label" => "Checkout Completed",
                    "displayOrder" => 1,
                    "metadata" => array(
                            "isClosed" => false,
                            "probability" => 0.2
                        )
                    ),
                array(
                    "label" => "Processing",
                    "displayOrder" => 3,
                    "metadata" => array(
                            "isClosed" => true,
                            "probability" => 1.0
                        )
                    ),
                array(
                    "label" => "Completed",
                    "displayOrder" => 4,
                    "metadata" => array(
                            "isClosed" => true,
                            "probability" => 1.0
                        )
                    ),
                array(
                    "label" => "Refunded/Cancelled",
                    "displayOrder" => 5,
                    "metadata" => array(
                            "isClosed" => true,
                            "probability" => 0.0
                        )
                    )

        );
    }

    /**
     * customer new order status
     * @return array
     * @since 1.0.0
     */

    public function dealsMapping() {

        $default_dealstages = array(
            'checkout_abandoned'=> 'checkout_abandoned',
            'processing'        => 'processed',
            'new'               => 'checkout_completed',
            'complete'          => 'shipped',
            'canceled'         => 'cancelled',
            'payment_review'    => 'cancelled',
            'holded'            => 'cancelled',
            'refunded'          => 'cancelled',
            'pending_payment'   => 'cancelled',
            'closed'            => 'cancelled',
            'failed'            => 'cancelled'
        );


        if($this->resourceConfig->getConfigValue('hubwoo_ecomm_pipeline_created')) {
            $new_stages = $this->resourceConfig->getConfigValue( 'hubwoo_ecomm_deal_stage_ids');
            foreach( $default_dealstages as $key => $value ) {
                $default_dealstages[$key] = $new_stages[$value];
            }
        }

        return $default_dealstages;

    }

    public function getPipelineDetails(){
        return array(
            "label" => "Ecommerce Pipeline",
            "displayOrder" => 0,
            "stages" => $this->getEcommDealStages()
        );

    }


    public function hubDealStageModel() {
        return array(
            'checkout_abandoned' => array(
                "label" => esc_html__( "Checkout Abandoned", 'makewebbetter-hubspot-for-magento' ),
                "metadata" => array(
                    "isClosed" => false,
                    "probability" => 0.1
                ),
            ),
            'processed'          => array(
                'label'    => esc_html__( 'Processing', 'makewebbetter-hubspot-for-magento' ),
                'metadata' => array(
                    'isClosed'    => true,
                    'probability' => 1,
                ),
            ),
            'checkout_completed' => array(
                'label'    => esc_html__( 'Checkout Completed', 'makewebbetter-hubspot-for-magento' ),
                'metadata' => array(
                    'isClosed'    => true,
                    'probability' => 1,
                ),
            ),
            'shipped'            => array(
                'label'    => esc_html__( 'Shipped', 'makewebbetter-hubspot-for-magento' ),
                'metadata' => array(
                    'isClosed'    => true,
                    'probability' => 1,
                ),
            ),
            'cancelled'          => array(
                'label'    => esc_html__( 'Refunded/Cancelled', 'makewebbetter-hubspot-for-magento' ),
                'metadata' => array(
                    'isClosed'    => true,
                    'probability' => 0,
                ),
            ),
        );
    }


    public function magentoOrderStatus() {
        return array(
            'processing'      => 'Processing',
            'pending_payment' => 'Pending Payment',
            'payment_review'  => 'Payment Review',
            'new'             => 'Pending',
            'holded'          => 'On  Hold',
            'complete'        => 'Complete',
            'closed'          => 'Closed',
            'canceled'        => 'Canceled',
        );
    }
}
