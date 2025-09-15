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
    $available_fields = $config->getFieldsByFormat('list');
    $opt = $config->getOptions(); 
    
    $normalize_titles = $opt['default_normalize_honorificPrefix'];
    $displayorder = $config->get('default_display_order');
        
    if (!empty($displayorder)) {
        $reihenfolge = $displayorder['list'];
    } else {
        $reihenfolge = ['displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia'];
    }
    // Zuerst die Schlüssel aus $reihenfolge (nur die, die in $available_fields existieren)
    $ordered_keys = array_merge(
        array_intersect($reihenfolge, array_keys($available_fields)),
        array_diff(array_keys($available_fields), $reihenfolge)
    );

         
    $lang = FAUdirUtils::getLang();
   
    if (!empty($persons)) : ?>
         <ul class="format-list">
            <?php
            $show_fields_lower = array_map('strtolower', $show_fields);
            
            
            foreach ($persons as $persondata) { 
                    if (isset($persondata['error'])) {  
                        if ($opt['show_error_message']) {
                        ?>
                        <div class="faudir-error">
                            <?php echo esc_html($persondata['message']); ?>
                        </div>
                        <?php }
                    } else { 
                     if (!empty($persondata)) { 
                        $output_escaped = '';          
                        $output_escaped .= '<li class="text-list" itemscope itemtype="https://schema.org/Person">';
         
                        $person = new Person($persondata);
                        $formatstring = '';
                        if (!empty($format_displayname)) {
                            $formatstring = $format_displayname;
                        }
                        $displayname = $person->getDisplayName(true, $normalize_titles, $formatstring);
                        $mailadresses= $person->getEMail();
                        $phonenumbers = $person->getPhone();                        
                        if (!empty($url)) {
                            $final_url = $url;
                        } else {
                            $final_url = $person->getTargetURL($opt['fallback_link_faudir']);
                        }
                        $contact = $person->getPrimaryContact($role);
                       
                        $workplaces = [];
                        if (!empty($contact)) { 
                            $workplaces = $contact->getWorkplaces($workplaces);                    
                        }
                        
             //           $output .= Debug::get_html_var_dump($workplaces);
                        
             //            $output .= Debug::get_html_var_dump($show_fields_lower);
                         
                        $output_escaped .= '<ul class="datalist">';
                        foreach ($ordered_keys as $key) {

                            $key_lower = strtolower($key);
                            if (in_array($key_lower, $show_fields_lower)) {                       
                                $value =  '';
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
                                } elseif ($key_lower === 'familyname') {    
                                    if (!empty($person->titleOfNobility))  { 
                                        $value = $person->titleOfNobility.' ';
                                    }
                                    $value .= $person->familyName;
                                } elseif ($key_lower === 'givenname') {    
                                    $value = $person->givenName;    
                                } elseif ($key_lower === 'honorificprefix') {    
                                    $value = $person->honorificPrefix;         
                                } elseif ($key_lower === 'honorificsuffix') {    
                                    $value = $person->honorificSuffix;                                          
                                } elseif ($key_lower === 'jobtitle') {
                                    $jobtitleformat = '#functionlabel#';
                                    if (!empty($opt['jobtitle_format'])) {
                                        $jobtitleformat = $opt['jobtitle_format'];
                                    }                           
                                    $value = $contact->getJobTitle($lang,$jobtitleformat);
                                } elseif (($key_lower === 'socialmedia') || ($key_lower === 'socials')) { 
                                    $value= $contact->getSocialMedia('span');
                                } elseif (($key_lower === 'room') && (!in_array('address', $show_fields) )) {
                                    if (!empty($workplaces)) {
                                            $room = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['room'])) {
                                                    $formattedValue = '<span class="texticon room">' . esc_html($wdata['room']) . '</span>';
                                                    $room .= '<span class="value icon"><span class="screen-reader-text">'.__('Room','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                                }
                                            }
                                            $value = $room;      
                                    }
                                } elseif ($key_lower === 'email')  {     
                                        $wval = '';           
                                        $mailadresses= $person->getEMail();
                                        foreach ($mailadresses as $mail) {
                                            if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                                                $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a>';
                                                $wval .= '<span class="value icon"><span class="screen-reader-text">'.__('Email','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                            }                 
                                        }

                                    $value = $wval;
                                } elseif ($key_lower === 'phone')  {     
                                   
                                    $wval = '';                                    
                                    foreach ($phonenumbers as $phone) {
                                        $formattedPhone = FaudirUtils::format_phone_number($phone);
                                        $cleanTel = preg_replace('/[^\+\d]/', '', $phone);
                                        $formattedValue = '<a itemprop="telephone" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                                        $wval .= '<span class="value icon"><span class="screen-reader-text">'.__('Phone','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                    }
                                    $value = $wval;      
                                } elseif ($key_lower === 'fax')  {     
                                   if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['fax'])) {
                                                    $formattedPhone = FaudirUtils::format_phone_number($wdata['fax']);
                                                    $cleanTel = preg_replace('/[^\+\d]/', '', $wdata['fax']);
                                                 
                                                    $formattedValue = '<a itemprop="fax" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                                                    $wval .= '<span class="value icon"><span class="screen-reader-text">'.__('Fax','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                } elseif ($key_lower === 'organization')  {    
                                    $value = $contact->getOrganizationName($lang);
                                    
                                  
                                } elseif ($key_lower === 'url')  {      
                                   if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['url'])) {
                                                    $displayValue = preg_replace('/^https?:\/\//i', '', $wdata['url']);     
                                                    $formattedValue = '<a href="' . esc_url($wdata['url']) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                                                    $wval .= '<span class="value icon"><span class="screen-reader-text">'.__('URL','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                 } elseif ($key_lower === 'link')  {          
                                                          
                                    if (!empty($final_url)) {
                                        $link = '<span class="profile-link">';
                                        $link .= '<a class="buttonlink" itemprop="sameAs" href="'.esc_url($final_url).'">';  

                                        $opt = $config->getOptions();                       
                                        $linkttitle = $opt['business_card_title'];
                                        if (empty($linkttitle)) {
                                             $linkttitle  = __('User profil', 'rrze-faudir');
                                        }

                                        $link .= $linkttitle;
                                        $link .= '</a>';
                                        $link .= '</span>';

                                        $value = $link;    
                                    }
  
                    
                                    
                                    
                                } elseif ($key_lower === 'image')  {      
                                    $value = $person->getImage();
                                  
                            
                                } elseif ($key_lower === 'teasertext')  {     
                                    $wval = $person->getTeasertext($lang);
                                    if (!empty($wval)) {
                                        $value = '<div class="value">'.wp_kses_post($wval).'</div>';
                                    }
                                } elseif (($key_lower === 'floor') && (!in_array('address', $show_fields) )) {
                                     if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['floor'])) {
                                                    $formattedValue = '<span class="texticon floor">' . esc_html($wdata['floor']) . '</span>';
                                                    $wval .= '<span class="value icon"><span class="screen-reader-text">'.__('Floor','rrze-faudir').': </span>'.$formattedValue.'</span>';
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
                                                        $formattedValue = '<a class="texticon faumap" href="' . esc_url($wdata['faumap']) . '" itemprop="url">' . __('FAU Map', 'rrze-faudir') . '</a>';
                                                        $faumap .= '<span class="value icon"><span class="screen-reader-text">'.__('Map','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                                    }
                                                }
                                            }
                                            $value = $faumap;
                                        }
                                } elseif ($key_lower === 'address')  {     
                                    if (!empty($workplaces)) {
                                            if ((in_array('room', $show_fields)) || (in_array('floor', $show_fields))) {
                                                $roomfloor = true;
                                            } else {
                                                $roomfloor = false;
                                            }
                                
                                            $wval = '';
                                            $seen      = [];
                                            foreach ($workplaces as $w => $wdata) {
                                                $html = (string) $contact->getAddressByWorkplace($wdata, false, $lang, $roomfloor);
                                                if ($html === '') {
                                                    continue;
                                                }
                                                // Kanonische Signatur: HTML → Text, Entities decodieren, trimmen, Whitespaces normalisieren, lowercasing
                                               $key = strtolower(
                                                   preg_replace('/\s+/u', ' ',
                                                       trim( wp_strip_all_tags( html_entity_decode( $html ) ) )
                                                   )
                                               );

                                               if ($key === '') {
                                                   continue;
                                               }
                                               if (!isset($seen[$key])) {
                                                   $seen[$key] = true;
                                                   $wval .= $html; 
                                               }
                                            }

                                            $value = $wval;      
                                    }
                                 
                                } elseif ($key_lower === 'street')  {        
                                    if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['street'])) {
                                                    $formattedValue = '<span class="street">' . esc_html($wdata['street']) . '</span>';
                                                    $wval .= '<span class="value"><span class="screen-reader-text">'.__('Street','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                } elseif ($key_lower === 'zip')  {   
                                     if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['zip'])) {
                                                    $formattedValue = '<span class="zip">' . esc_html($wdata['zip']) . '</span>';
                                                    $wval .= '<span class="value"><span class="screen-reader-text">'.__('Postal Code','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                } elseif ($key_lower === 'city')  {        
                                     if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['city'])) {
                                                    $formattedValue = '<span class="city">' . esc_html($wdata['city']) . '</span>';
                                                    $wval .= '<span class="value"><span class="screen-reader-text">'.__('City','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }

                                } elseif ($key_lower === 'officehours')  {  
                                    if (!empty($workplaces)) {
                                            $hours = '';
                                           
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['officeHours'])) { 
                                                    $hours .= $contact->getConsultationsHours($wdata, 'officeHours', true, $lang);
                                                }
                                            } 
                                            $value = $hours;
                                        }
                                } elseif ($key_lower === 'consultationhours')  {             
                                        if (!empty($workplaces)) {
                                            $hours = '';
                                            
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['consultationHours'])) {
                                                    $hours .= $contact->getConsultationsHours($wdata, 'consultationHours', true, $lang);
                                                }
                                                $hours .= $contact->getConsultationbyAggreement($wdata);
                                            }
                                            $value = $hours;
                                        }      
     
                                
                                }
                                
                                if (!empty($value)) {
                                    $output_escaped .= '<li class="faudir-'.esc_attr($key_lower).'">';
                                    $output_escaped .= $value;
                                    $output_escaped .= '</li>';
                                }

                            }
                        }
                        $output_escaped .= '</ul>'; 
                        $output_escaped .= '</li>'; 
                        echo $output_escaped;
                    } else { ?>
                        <div class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?> </div>
                <?php }
                    }
            } ?>            
        </ul>
    <?php else : ?>
        <div class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir') ?> </div>
    <?php endif; ?>

</div>