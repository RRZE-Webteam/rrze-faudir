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
  //   echo "<hr><h2>Hide</h2>". Debug::get_html_var_dump($hide_fields);
 //    echo "<hr><h2>All fields</h2>". Debug::get_html_var_dump($available_fields);
     $noout = '';
     
         $reihenfolge = ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia', 'organization','address', 'room', 'floor', 'faumap', 'teasertext'];

        // Zuerst die Schlüssel aus $reihenfolge (nur die, die in $available_fields existieren)
        $ordered_keys = array_merge(
            array_intersect($reihenfolge, array_keys($available_fields)),
            // Dann alle übrigen Schlüssel aus $available_fields, die nicht in $reihenfolge enthalten sind
            array_diff(array_keys($available_fields), $reihenfolge)
        );
   //  echo "<hr><h2>Reihenfolge alle:</h2>". Debug::get_html_var_dump($ordered_keys);
        echo "<h2>Personendata</h2>";
         foreach ($persons as $persondata) { 
              echo  Debug::get_html_var_dump($persondata);
         }
        
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
                        $output .= '<tr itemscope itemtype="https://schema.org/Person">';
         
                        $person = new Person($persondata);
                        $displayname = $person->getDisplayName(true, false);
                        $mailadresses= $person->getEMail();
                        $phonenumbers = $person->getPhone();                        
                        $final_url = $person->getTargetURL();
                        $contact = $person->getPrimaryContact();
                        $jobtitle = '';
                        $workplaces = [];
                        
                        if (!empty($contact)) { 
                            $contactdata = $contact->toArray();     
                            $workplaces = $contact->getWorkplaces();
                            $workplaces['mails'] = $mailadresses;
                            $workplaces['phones'] = $phonenumbers;                         
                            $socials = $contact->getSocialArray();
 
                        }
                        

                        $show_fields_lower = array_map('strtolower', $show_fields);
                        $hide_fields_lower = array_map('strtolower', $hide_fields);

                        foreach ($ordered_keys as $key) {
                            $key_lower = strtolower($key);
                            if (in_array($key_lower, $show_fields_lower) && !in_array($key_lower, $hide_fields_lower)) {
                                $output .= '<td>';
                                $value = '';
                                if ($key_lower === 'displayname')  {
                                    if ($displayname) {
                                        if (!empty($final_url)) {
                                             $value .= '<a itemprop="url" href="'.esc_url($final_url).'">';     
                                        }
                                        $value .= $displayname;
                                        if (!empty($final_url)) {
                                             $value .= '</a>';
                                        }
                                    }     

                                    
                                } elseif ($key_lower === 'jobtitle') {
                                    $jobtitle = $contact->getJobTitle($lang);
                                    if ($jobtitle) {
                                         $value = $jobtitle;                                      
                                    }  
                                } elseif (($key_lower === 'socialmedia') || ($key_lower === 'socials')) { 
                                        $value = FaudirUtils::getListOutput($socials,'span',__('Social Media and Websites', 'rrze-faudir'),'icon-list icon');
                                
                                        
                                } elseif ($key_lower === 'room')  {
                                    if (!empty($workplaces)) {
                                            $room = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['room'])) {
                                                    $room .= '<span class="room"><span class="screen-reader-text">'.__('Room','rrze-faudir').': </span>'.esc_html($wdata['room']).'</span>';
                                                }
                                            }
                                            $value = $room;      
                                    }
                                } elseif ($key_lower === 'email')  {     
                                        $wval = '';                                    
                                        foreach ($mailadresses as $mail) {
                                            if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                                                $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a>';
                                                $wval .= '<span><span class="screen-reader-text">'.__('Email','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                            }                 
                                        }

                                    $value = $wval;
                                } elseif ($key_lower === 'phone')  {     
                                   
                                    $wval = '';                                    
                                    foreach ($phonenumbers as $phone) {
                                        $formattedPhone = FaudirUtils::format_phone_number($phone);
                                        $telLink = preg_replace('/\s+/', '', $formattedPhone); // Entferne Leerzeichen für den `tel:`-Link
                                        $formattedValue = '<a itemprop="telephone" href="tel:' . esc_attr($telLink) . '">' . esc_html($formattedPhone) . '</a>';
                                        $wval .= '<span class="phone"><span class="screen-reader-text">'.__('Phone','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                        
                                    }
                                    $value = $wval;      
                                    
                                } elseif ($key_lower === 'organization')  {    
                                    $value = $contact->getOrganizationName($lang);
                                    
                                  
                                } elseif ($key_lower === 'url')  {      
                                   if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['url'])) {
                                                    $displayValue = preg_replace('/^https?:\/\//i', '', $wdata['url']);     
                                                    $formattedValue = '<a href="' . esc_url($wdata['url']) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                                                    $wval .= '<span class="url"><span class="screen-reader-text">'.__('URL','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                    
                                    
                                } elseif ($key_lower === 'content')  {      
                                    $wval = $person->getContent($lang);
                                    if (!empty($wval)) {
                                        $value = '<div class="content">'.$wval.'</div>';
                                    }
                                } elseif ($key_lower === 'teasertext')  {     
                                    $wval = $person->getTeasertext($lang);
                                    if (!empty($wval)) {
                                        $value = '<div class="teasertext">'.wp_kses_post($wval).'</div>';
                                    }
                                } elseif ($key_lower === 'workplaces')  {             
                                } elseif ($key_lower === 'floor')  {      
                                     if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['floor'])) {
                                                    $wval .= '<span class="floor"><span class="screen-reader-text">'.__('Floor','rrze-faudir').': </span>'.esc_html($wdata['floor']).'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                } elseif ($key_lower === 'address')  {     
                                    if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                               
                                                $wval .= $contact->getAddressByWorkplace($wdata, false);
                                            }
                                            $value = $wval;      
                                    }
                                } elseif ($key_lower === 'street')  {        
                                    if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['street'])) {
                                                    $wval .= '<span class="street"><span class="screen-reader-text">'.__('Street','rrze-faudir').': </span>'.esc_html($wdata['street']).'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                } elseif ($key_lower === 'zip')  {   
                                     if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['zip'])) {
                                                    $wval .= '<span class="zip"><span class="screen-reader-text">'.__('Postal Code','rrze-faudir').': </span>'.esc_html($wdata['zip']).'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                } elseif ($key_lower === 'city')  {        
                                     if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['city'])) {
                                                    $wval .= '<span class="city"><span class="screen-reader-text">'.__('City','rrze-faudir').': </span>'.esc_html($wdata['city']).'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                } elseif ($key_lower === 'faumap')  {     
                                        if (!empty($workplaces)) {
                                            $faumap = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['faumap'])) {
                                                    if (preg_match('/^https?:\/\/karte\.fau\.de/i', $wdata['faumap'])) {
                                                        $formattedValue = '<a href="' . esc_url($wdata['faumap']) . '" itemprop="url">' . esc_html($wdata['faumap']) . '</a>';
                                                        $faumap .= '<span class="faumap"><span class="screen-reader-text">'.__('FAU Map','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                                    }
                                                }
                                            }
                                            $value = $faumap;
                                        }
                               
                                } elseif ($key_lower === 'officehours')  {             
                                } elseif ($key_lower === 'consultationhours')  {             
                                        
                                } else {
                                        $value = $key;
                                        if (isset($contactdata[$key])) {
                                            $value = $contactdata[$key];
                                        } elseif ($workplaces[$key]) {
                                             $value = $workplaces[$key];
                                        }
                                        
                                }
                                if (empty($value)) {
                                    $output .= "Empty ".$key_lower;
                                }
                                $output .= $value;
                                $output .= '</td>';
                             
                            } else {
                                $noout .= $key_lower. " ";
                            }
                        }
                        
                            
                            
                           

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
    

</div>