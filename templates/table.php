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
    $fieldsbyformat = $config->get('avaible_fields_byformat');
    $available_fields = $config->getFieldsByFormat('table');
    $opt = $config->getOptions();        
     
   
    $displayorder = $config->get('default_display_order');
    if (!empty($displayorder)) {
        $reihenfolge = $displayorder['table'];
    } else {
        $reihenfolge = ['image', 'displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia'];
    }

    $ordered_keys = array_merge(
            array_intersect($reihenfolge, array_keys($available_fields)),
            array_diff(array_keys($available_fields), $reihenfolge)
    );
    $lang = FAUdirUtils::getLang();
     

    ?>    
    <table class="format-table">
        <tbody>
            <?php
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
                        $output = '';          
                        $output .= '<tr itemscope itemtype="https://schema.org/Person">';
         
                        $person = new Person($persondata);
                        $displayname = $person->getDisplayName(true, false);
                        $mailadresses= $person->getEMail();
                        $phonenumbers = $person->getPhone();                        
                        $final_url = $person->getTargetURL();
                        $contact = $person->getPrimaryContact();
                        $workplaces = [];
                        if (!empty($contact)) { 
                            $workplaces = $contact->getWorkplaces();                    
                        }
                                               
                         
                        $show_fields_lower = array_map('strtolower', $show_fields);
                        $hide_fields_lower = array_map('strtolower', $hide_fields);

                        foreach ($ordered_keys as $key) {
                            $key_lower = strtolower($key);
                            if (in_array($key_lower, $show_fields_lower) && !in_array($key_lower, $hide_fields_lower)) {
                                
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
                                    $value = $contact->getJobTitle($lang);
                                } elseif (($key_lower === 'socialmedia') || ($key_lower === 'socials')) { 
                                    $value= $contact->getSocialMedia('span');
                                } elseif ($key_lower === 'room')  {
                                    if (!empty($workplaces)) {
                                            $room = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['room'])) {
                                                    $room .= '<span class="room">'.__('Room','rrze-faudir').': '.esc_html($wdata['room']).'</span>';
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
                                                $wval .= '<span class="email"><span class="screen-reader-text">'.__('Email','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                            }                 
                                        }

                                    $value = $wval;
                                } elseif ($key_lower === 'phone')  {     
                                   
                                    $wval = '';                                    
                                    foreach ($phonenumbers as $phone) {
                                        $formattedPhone = FaudirUtils::format_phone_number($phone);
                                        $cleanTel = preg_replace('/[^\+\d]/', '', $phone);
                                        $formattedValue = '<a itemprop="telephone" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
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
                                } elseif ($key_lower === 'image')  {      
                                    $value = $person->getImage();
                                  
                                } elseif ($key_lower === 'teasertext')  {     
                                    $wval = $person->getTeasertext($lang);
                                    if (!empty($wval)) {
                                        $value = wp_kses_post($wval);
                                    }
                                } elseif ($key_lower === 'floor')  {      
                                     if (!empty($workplaces)) {
                                            $wval = '';
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['floor'])) {
                                                    $wval .= '<span class="floor">'.__('Floor','rrze-faudir').': '.esc_html($wdata['floor']).'</span>';
                                                }
                                            }
                                            $value = $wval;      
                                    }
                                } elseif ($key_lower === 'address')  {     
                                    if (!empty($workplaces)) {
                                            $wval = '';
                                            $showmap = false;
                                            if (in_array('faumap', $show_fields) && !in_array('faumap', $hide_fields)) {
                                                $showmap = true;
                                            }
                                            $showroomfloor = false;
                                            if ((in_array('room', $show_fields) && !in_array('room', $hide_fields))
                                            || (in_array('floor', $show_fields) && !in_array('floor', $hide_fields))) {
                                                $showroomfloor = true;
                                            }
                                        
                                            foreach ($workplaces as $w => $wdata) {                                        
                                                $wval .= $contact->getAddressByWorkplace($wdata, false, $lang, $showroomfloor, $showmap);
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
                                                        $formattedValue = '<a href="' . esc_url($wdata['faumap']) . '" itemprop="url">' . __('FAU Map', 'rrze-faudir') . '</a>';
                                                        $faumap .= '<span class="faumap">'.__('Map','rrze-faudir').': '.$formattedValue.'</span>';
                                                    }
                                                }
                                            }
                                            $value = $faumap;
                                        }
                               
                                } elseif ($key_lower === 'officehours')  {  
                                    if (!empty($workplaces)) {
                                            $hours = '';
                                            $showmap = false;
                                            if (in_array('faumap', $show_fields) && !in_array('faumap', $hide_fields)) {
                                                $showmap = true;
                                            }
                                            $showroomfloor = false;
                                            if ((in_array('room', $show_fields) && !in_array('room', $hide_fields))
                                            || (in_array('floor', $show_fields) && !in_array('floor', $hide_fields))) {
                                                $showroomfloor = true;
                                            }
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['officeHours'])) { 
                                                    $hours .= $contact->getConsultationsHours($wdata, 'officeHours', true, $lang, $showroomfloor, $showmap);
                                                }
                                            } 
                                            $value = $hours;
                                        }
                                } elseif ($key_lower === 'consultationhours')  {             
                                        if (!empty($workplaces)) {
                                            $hours = '';
                                            $showmap = false;
                                            if (in_array('faumap', $show_fields) && !in_array('faumap', $hide_fields)) {
                                                $showmap = true;
                                            }
                                            $showroomfloor = false;
                                            if ((in_array('room', $show_fields) && !in_array('room', $hide_fields))
                                            || (in_array('floor', $show_fields) && !in_array('floor', $hide_fields))) {
                                                $showroomfloor = true;
                                            }
                                            foreach ($workplaces as $w => $wdata) {
                                                if (!empty($wdata['consultationHours'])) {
                                                    $hours .= $contact->getConsultationsHours($wdata, 'consultationHours', true, $lang, $showroomfloor, $showmap);
                                                }
                                            }
                                            $value = $hours;
                                        }      
                                }
                                $output .= '<td class="faudir-'.esc_attr($key_lower).'">';
                                $output .= $value;
                                $output .= '</td>';
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