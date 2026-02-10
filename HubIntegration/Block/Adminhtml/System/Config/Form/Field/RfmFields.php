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
 * @author      Makewebbetter Core Team <connect@Makewebbetter.com>
 * @copyright   Copyright Makewebbetter (http://makewebbetter.com/)
 * @license     https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Block\Adminhtml\System\Config\Form\Field;

use Makewebbetter\HubIntegration\Helper\Data as HubHelper;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RfmFields extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $value = $this->_scopeConfig->getValue(HubHelper::SYSTEM_CONFIG_RFM_FIELDS);
        $data = json_decode($value, true);
        $html = "<tr name='rfm_fields'><td class='label'>Rfm Settings</td><td class='value'>
               <table class='data-grid'>
                   <thead>
                        <tr><th class='data-grid-th'><span>Rating</span></th>
                            <th class='data-grid-th'><span>Recency</span><p><span>(days since last order)</span></p>
                            </th>
                            <th class='data-grid-th'><span>Frequency</span><p><span>(total orders placed)</span></p>
                            </th>
                            <th class='data-grid-th'><span>Monetary</span><p><span>(total money spent)</span></p></th>
                            </tr>
                   </thead>
                   <tbody>
                       <tr class='data-row'>
                           <td style='vertical-align: middle;text-align: center'><b>5</b></td>
                           <td>
                           Less Than:<input type='number' value='" . $data['rfm_at_5']['recency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][rfm_at_5][recency]'/>
                           </td>
                           <td>
                           More Than:<input type='number' value='" . $data['rfm_at_5']['frequency'] . "'  
                           name='groups[rfm_settings][fields][rfm_fields][value][rfm_at_5][frequency]'/>
                           </td>
                           <td>
                           More Than:<input type='number' value='" . $data['rfm_at_5']['monetary'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][rfm_at_5][monetary]'/>
                           </td>
                       </tr>
                       
                       <tr class='data-row _odd-row'>
                           <td style='vertical-align: middle;text-align: center'><b>4</b></td>
                           <td>
                           From:
                           <input type='number' value='" . $data['from_rfm_4']['recency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][from_rfm_4][recency]'/>
                           <br>
                            To:
                            <input type='number' value='" . $data['to_rfm_4']['recency'] . "' 
                            name='groups[rfm_settings][fields][rfm_fields][value][to_rfm_4][recency]'/>
                           </td>
                           <td>
                           From:
                           <input type='number' value='" . $data['from_rfm_4']['frequency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][from_rfm_4][frequency]'/>
                           <br>
                           To:
                           <input type='number' value='" . $data['to_rfm_4']['frequency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][to_rfm_4][frequency]'/>
                           </td>
                           <td>
                           From:
                           <input type='number' value='" . $data['from_rfm_4']['monetary'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][from_rfm_4][monetary]'/>
                           <br>
                           To:
                           <input type='number' value='" . $data['to_rfm_4']['monetary'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][to_rfm_4][monetary]'/>
                           </td>
                        </tr>
                       
                       <tr class='data-row'>
                            <td style='vertical-align: middle;text-align: center'><b>3</b></td>
                           <td>
                           From:<input type='number' value='" . $data['from_rfm_3']['recency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][from_rfm_3][recency]'/>
                           <br>
                            To:<input type='number' value='" . $data['to_rfm_3']['recency'] . "' 
                            name='groups[rfm_settings][fields][rfm_fields][value][to_rfm_3][recency]'/>
                            </td>
                           <td>
                           From:<input type='number' value='" . $data['from_rfm_3']['frequency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][from_rfm_3][frequency]'/>
                           <br>
                            To:<input type='number' value='" . $data['to_rfm_3']['frequency'] . "' 
                            name='groups[rfm_settings][fields][rfm_fields][value][to_rfm_3][frequency]'/>
                            </td>
                           <td>
                           From:<input type='number' value='" . $data['from_rfm_3']['monetary'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][from_rfm_3][monetary]'/>
                           <br>
                            To:<input type='number' value='" . $data['to_rfm_3']['monetary'] . "' 
                            name='groups[rfm_settings][fields][rfm_fields][value][to_rfm_3][monetary]'/>
                            </td>
                        </tr>
                       
                       <tr class='data-row _odd-row'>
                           <td style='vertical-align: middle;text-align: center'><b>2</b></td>
                           <td>From:<input type='number' value='" . $data['from_rfm_2']['recency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][from_rfm_2][recency]'/><br>
                                To:<input type='number' value='" . $data['to_rfm_2']['recency'] . "' 
                                name='groups[rfm_settings][fields][rfm_fields][value][to_rfm_2][recency]'/></td>
                           <td>From:<input type='number' value='" . $data['from_rfm_2']['frequency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][from_rfm_2][frequency]'/><br>
                                To:<input type='number' value='" . $data['to_rfm_2']['frequency'] . "' 
                              name='groups[rfm_settings][fields][rfm_fields][value][to_rfm_2][frequency]'/></td>
                           <td>From:<input type='number' value='" . $data['from_rfm_2']['monetary'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][from_rfm_2][monetary]'/><br>
                                To:<input type='number' value='" . $data['to_rfm_2']['monetary'] . "' 
                                name='groups[rfm_settings][fields][rfm_fields][value][to_rfm_2][monetary]'/></td>
                            </tr>
                           
                           <tr>
                           <td style='vertical-align: middle;text-align: center'><b>1</b></td>
                           <td>
                           More Than:<input type='number' value='" . $data['rfm_at_1']['recency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][rfm_at_1][recency]'/></td>
                           <td>
                           Less Than:<input type='number' value='" . $data['rfm_at_1']['frequency'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][rfm_at_1][frequency]'/></td>
                           <td>
                           Less Than:<input type='number' value='" . $data['rfm_at_1']['monetary'] . "' 
                           name='groups[rfm_settings][fields][rfm_fields][value][rfm_at_1][monetary]'/></td>
                       </tr>
                   </tbody>
               </table>
               </td></tr>";
        return $html;
    }
}
