<?php
// Template file for RRZE FAUDIR

use RRZE\FAUdir\Debug;
use RRZE\FAUdir\FAUdirUtils;
use RRZE\FAUdir\Person;
use RRZE\FAUdir\Config;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="faudir">
    <?php
    
     $config = new Config;
     $available_fields = $config->get('avaible_fields');
    
     echo "<h2>Show</h2>". Debug::get_html_var_dump($show_fields);
     echo "<hr><h2>Hide</h2>". Debug::get_html_var_dump($hide_fields);
     echo "<hr><h2>All fields</h2>". Debug::get_html_var_dump($available_fields);
     $noout = '';
     
         $reihenfolge = ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext'];

        // Zuerst die Schlüssel aus $reihenfolge (nur die, die in $available_fields existieren)
        $ordered_keys = array_merge(
            array_intersect($reihenfolge, array_keys($available_fields)),
            // Dann alle übrigen Schlüssel aus $available_fields, die nicht in $reihenfolge enthalten sind
            array_diff(array_keys($available_fields), $reihenfolge)
        );
     echo "<hr><h2>Reihenfolge alle:</h2>". Debug::get_html_var_dump($ordered_keys);
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
               //         $output .=  Debug::get_html_var_dump($persondata);
                        $output .= '<tr itemscope itemtype="https://schema.org/Person">';
    
                        
                        $person = new Person($persondata);
                        $displayname = $person->getDisplayName(true, false);
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
 
             //               $output .= '<td>'. Debug::get_html_var_dump($workplaces).'</td>';
                        }
                        
                        
                        /*
     *         
        'image'             => __('Image', 'rrze-faudir'),
        'displayname'      => __('Display Name', 'rrze-faudir'),
        'honorificPrefix'    => __('Academic Title', 'rrze-faudir'),
        'givenName'        => __('First Name', 'rrze-faudir'),
        'nobility_title'    => __('Nobility Title', 'rrze-faudir'),
        'familyName'         => __('Last Name', 'rrze-faudir'),
        'honorificSuffix'   => __('Academic Suffix', 'rrze-faudir'),
        'email'             => __('Email', 'rrze-faudir'),
        'phone'             => __('Phone', 'rrze-faudir'),
        'organization'      => __('Organization', 'rrze-faudir'),
        'function'          => __('Function', 'rrze-faudir'),
        'url'               => __('URL', 'rrze-faudir'),
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
                        



                        $show_fields_lower = array_map('strtolower', $show_fields);
                        $hide_fields_lower = array_map('strtolower', $hide_fields);

                        foreach ($ordered_keys as $key) {
                            $key_lower = strtolower($key);
                            if (in_array($key_lower, $show_fields_lower) && !in_array($key_lower, $hide_fields_lower)) {
                                $output .= '<td>';
                                  $output .= $key_lower. ': ';
                                $value = '';
                                if ($key === 'displayname')  {
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
                                } elseif ($key === 'jobtitle') {
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