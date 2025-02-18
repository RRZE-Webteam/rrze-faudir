<?php
// Template file for RRZE FAUDIR

use RRZE\FAUdir\Debug;
use RRZE\FAUdir\FAUdirUtils;
use RRZE\FAUdir\Person;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="faudir">
    <table class="format-table">
        <tbody>
            <?php foreach ($persons as $persondata) { 
                if (isset($persondata['error'])) { ?>
                    <div class="faudir-error">
                        <?php echo esc_html($persondata['message']); ?>
                    </div>
                <?php } else { 
                     if (!empty($persondata)) { 
                        
                        $output = '';
                        $output .=  Debug::get_html_var_dump($persondata);
                        $output .= '<tr itemscope itemtype="https://schema.org/Person">';
    
                 //       $options = get_option('rrze_faudir_options');
                        $person = new Person($persondata);
                        $displayname = $person->getDisplayName(true, false, $show_fields, $hide_fields);
                        $final_url = $person->getTargetURL();


                        if ($displayname) {
                            $output .= '<th class="displayname">';
                            if (!empty($final_url)) {
                                $output .= '<a itemprop="url" href="'.esc_url($final_url).'">';     
                            }
                            $output .= $displayname;
                            if (!empty($final_url)) {
                                 $output .= '</a>';
                            }
                            $output .= '</th>';
                        }
                            
                        $contact = $person->getPrimaryContact();
                        $workplaces = $contact->getWorkplaces();
                        
                        $output .=  Debug::get_html_var_dump($workplaces);
                        
                        
                            
                            
                        $unique_emails = [];
                        $unique_phones = [];
                        $url_displayed = false;

      
                        $workplaces = [];
                        $socials = [];
                        $org = '';
                        $function = '';
                         
                                    
                        // Collect emails and phones from workplaces, falling back to person email/phone if necessary
                        if (!empty($contact)) {
                            
                            $contactdata = $contact->toArray();
                            
 
                                $workplaces = $contactdata['workplaces'];
                                $socials = $contactdata['socials'];
                                
                                if (!empty($contactdata['socials'])) {
                                    $socials = [];

                                    foreach ($contactdata['socials'] as $item) {
                                        if (isset($item['platform']) && isset($item['url'])) {
                                            $socials[$item['platform']] = $item['url'];
                                        }
                                    }
                                }
   
                                
                                if (!empty($contactdata['workplaces'])) {
                                    foreach ($contactdata['workplaces'] as $workplace) {
                                        // Handle emails
                                        if (!empty($workplace['mails'])) {
                                            foreach ($workplace['mails'] as $email) {
                                                if (!in_array($email, $unique_emails)) {
                                                    $unique_emails[] = $email;
                                                }
                                            }
                                        }
                                    
                                        // Handle phones
                                        if (!empty($workplace['phones'])) {
                                            foreach ($workplace['phones'] as $phone) {
                                                if (!in_array($phone, $unique_phones)) {
                                                    $unique_phones[] = $phone;
                                                }
                                            }
                                        }
                                    
                                        // Check if a URL exists
                                        if (!empty($workplace['url'])) {
                                            $url_displayed = true;
                                        }
                                    }
                                }
                            
                  //          if (empty($unique_emails) && !empty($person['email'])) {
                  //              $workplaces['mails'][] = $person['email'];
                  //          }
                        
                  //          if (empty($unique_phones) && !empty($person['telephone'])) {
                  //              $workplaces['phones'][] = $person['telephone'];
                  //          }
                        }
                        if (!empty($contactdata['function'])) {
                            $workplaces['function'] = $contactdata['function'];
                        }
             //         $output .= Debug::get_html_var_dump($workplaces);
             //           $output .= Debug::get_html_var_dump($show_fields);
             //           $output .= Debug::get_html_var_dump($hide_fields);

                        $reihenfolge = ['function', 'mails', 'phones', 'url', 'street', 'zip', 'city', 'roompos', 'room', 'floor', 'address','faumap'];
                        // Output Workplace Data
                        $tablecells = FaudirUtils::getTableCellOutput($workplaces,$show_fields,$hide_fields,$reihenfolge);
$tablecells = '';
                        $socialoutput = '';
                        if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)) {                            
                            if (!empty($socials)) {        
                                $socialoutput = FaudirUtils::getListOutput($socials,'span',__('Social Media and Websites', 'rrze-faudir'),'icon-list icon');
                            }

                        }
                        if (!empty($tablecells)) {
                             $output .= $tablecells;
                        }
                        if (!empty($socialoutput)) {
                             $output .= '<td>'.$socialoutput.'</td>';
                        }
                        
                        
                        // Output optional teasertext
                        if (in_array('teasertext', $show_fields) && !in_array('teasertext', $hide_fields)) {
                            if (!empty($teaser_lang)) {  
                               $output .= '<td><span class="teasertext">'.wp_kses_post($teaser_lang).'</span></td>';
                            }
                        } 
                        
                            
                            
                            
                           

                        $output .= '</tr>';
                        echo $output;
                            
                    } else { ?>
                        <div class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </div>
                    <?php }
                }
                } ?>
        </tbody>
    </table>
</div>