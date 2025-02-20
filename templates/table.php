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
    <?php
    
    
     echo "<h2>Show</h2>". Debug::get_html_var_dump($show_fields);
     echo "<hr><h2>Hide</h2>". Debug::get_html_var_dump($hide_fields);
     $noout = '';
    ?>    
    
    <table class="format-table">
        <tbody>
            <?php
                $lang = FAUdirUtils::getLang();
                
                foreach ($persons as $persondata) { 
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
                        $mailadresses= $person->getEMail();
                        $phonenumbers = $person->getPhone();                        
                        $final_url = $person->getTargetURL();
                        $contact = $person->getPrimaryContact();
                        if (!empty($contact)) {
                            
                            $contactdata = $contact->toArray();     
                            $jobtitle = $contact->getJobTitle($lang);
                            $workplaces = $contact->getWorkplaces();
                            $workplaces['mails'] = $mailadresses;
                            $workplaces['phones'] = $phonenumbers;                         
                            $socials = $contact->getSocialArray();
 
                            $output .= '<td>'. Debug::get_html_var_dump($workplaces).'</td>';
                        }
                        
                        
                        /*
     *         
        'image'             => __('Image', 'rrze-faudir'),
        'display_name'      => __('Display Name', 'rrze-faudir'),
        'academic_title'    => __('Academic Title', 'rrze-faudir'),
        'first_name'        => __('First Name', 'rrze-faudir'),
        'nobility_title'    => __('Nobility Title', 'rrze-faudir'),
        'last_name'         => __('Last Name', 'rrze-faudir'),
        'academic_suffix'   => __('Academic Suffix', 'rrze-faudir'),
        'email'             => __('Email', 'rrze-faudir'),
        'phone'             => __('Phone', 'rrze-faudir'),
        'organization'      => __('Organization', 'rrze-faudir'),
        'function'          => __('Function', 'rrze-faudir'),
        'url'               => __('URL', 'rrze-faudir'),
        'kompaktButton'     => __('Kompakt Button', 'rrze-faudir'),
        'content'           => __('Content', 'rrze-faudir'),
        'teasertext'        => __('Teasertext', 'rrze-faudir'),
        'socialmedia'       => __('Social Media and Websites', 'rrze-faudir'),
        'workplaces'        => __('Workplaces', 'rrze-faudir'),
        'room'              => __('Room', 'rrze-faudir'),
        'floor'             => __('Floor', 'rrze-faudir'),
        'address'           => __('Address', 'rrze-faudir'),
        'street'            => __('Street', 'rrze-faudir'),
        'zip'               => __('ZIP Code', 'rrze-faudir'),
        'city'              => __('City', 'rrze-faudir'),
        'faumap'            => __('FAU Map', 'rrze-faudir'),
        'officehours'       => __('Office Hours', 'rrze-faudir'),
        'consultationhours' => __('Consultation Hours', 'rrze-faudir'),
     */
                        
                        $reihenfolge = ['image', 'display_name', 'function', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext'];
                        
                     
                        
                        $show_fields_lower = array_map('strtolower', $show_fields);
                        $hide_fields_lower = array_map('strtolower', $hide_fields);

                        foreach ($reihenfolge as $key) {
                            $key_lower = strtolower($key);
                            if (in_array($key_lower, $show_fields_lower) && !in_array($key_lower, $hide_fields_lower)) {
                                $output .= '<td>';
                                $value = '';
                                if (($key === 'displayname') || ($key === 'display_name')) {
                                    if ($displayname) {
                                        if (!empty($final_url)) {
                                             $value .= '<a itemprop="url" href="'.esc_url($final_url).'">';     
                                        }
                                        $value .= $displayname;
                                        if (!empty($final_url)) {
                                             $value .= '</a>';
                                        }
                                    }     
                               } elseif ($key === 'room')  {
                                    if ($jobtitle) {
                                         $value = $jobtitle;                                      
                                    }       
                                } elseif (($key === 'function') || ($key === 'jobtitle')) {
                                    if ($jobtitle) {
                                         $value = $jobtitle;                                      
                                    }  
                                } elseif (($key === 'socialmedia') || ($key === 'socials')) { 
                                        $value = FaudirUtils::getListOutput($socials,'span',__('Social Media and Websites', 'rrze-faudir'),'icon-list icon');
                                       
                                } else {
                                        $value = $key;
                                        if (isset($contactdata[$key])) {
                                            $value = $contactdata[$key];
                                        } elseif ($workplaces[$key]) {
                                             $value = $workplaces[$key];
                                        }
                                        
                                }
                                $output .= $value;
                                $output .= '</td>';
                             
                            } else {
                                $noout .= $key_lower. " ";
                            }
                        }
                        
                        
                        
              

                    
             //         $output .= Debug::get_html_var_dump($workplaces);
             //           $output .= Debug::get_html_var_dump($show_fields);
             //           $output .= Debug::get_html_var_dump($hide_fields);

                     
                        

                            
                            
                           

                        $output .= '</tr>';
                        echo $output;
                        $noout .= '<br>';
                            
                    } else { ?>
                        <div class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </div>
                    <?php }
                }
                } ?>
        </tbody>
    </table>
    
    <?php
    
    echo "NOT OUTPUTTET: $noout";
    ?>
</div>