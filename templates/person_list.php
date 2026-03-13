<?php
// Template file for RRZE FAUDIR

use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Person;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="faudir">
    <?php 
    
    $available_fields = $this->config->getFieldsByFormat('list');   
    $normalize_titles = this->config->get('default_normalize_honorificPrefix');
    $displayorder = $this->config->get('default_display_order');
        
    if (!empty($displayorder) && !empty($displayorder['list']) && is_array($displayorder['list'])) {
        $reihenfolge = $displayorder['list'];
    } else {
        $reihenfolge = ['displayname', 'jobTitle', 'phone', 'email', 'url', 'socialmedia'];
    }

    $ordered_keys = array_merge(
        array_intersect($reihenfolge, array_keys($available_fields)),
        array_diff(array_keys($available_fields), $reihenfolge)
    );

    $lang = FaudirUtils::getLang();
   
    if (!empty($persons)) : ?>
         <ul class="format-list">
            <?php
            $show_fields_lower = array_map('strtolower', $show_fields);
            
            
            foreach ($persons as $persondata) { 
                if (isset($persondata['error'])) {
                    if ($this->config->get('show_error_message')) {
                        ?>
                        <li class="faudir-error">
                            <?php echo esc_html($persondata['message']); ?>
                        </li>
                        <?php
                    }
                    continue;
                }
                if (empty($persondata)) {
                    ?>
                    <li class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?></li>
                    <?php
                    continue;
                }
                
                $output_escaped = '';          
                $output_escaped .= '<li class="text-list" itemscope itemtype="https://schema.org/Person">';

                $person = new Person($persondata);
                $person->setConfig($this->config);
                $formatstring = '';
                if (!empty($format_displayname)) {
                    $formatstring = $format_displayname;
                }
                $displayname = $person->getRenderedDisplayName($show_fields, $normalize_titles, $formatstring);


                if (!empty($url)) {
                    $final_url = $url;
                } else {
                    $final_url = $person->getTargetURL($this->config->get('fallback_link_faudir'));
                }
                $contact = $person->getPrimaryContact($role);

                $workplaces = [];
                if (!empty($contact)) { 
                    $workplaces = $contact->getWorkplaces($workplaces);                    
                }



                $output_escaped .= '<ul class="datalist list-icons">';
                foreach ($ordered_keys as $key) {

                    $key_lower = strtolower($key);
                    if (!in_array($key_lower, $show_fields_lower, true)) {
                        continue;
                    }
                    $value =  '';

                    
                    
                    if (in_array($key_lower, $show_fields_lower)) {                       
                       
                        if ($key_lower === 'displayname')  {
                            if ($displayname) {
                                if ((!empty($final_url)) && (in_array('link', $show_fields))) {
                                     $value .= '<a itemprop="url" href="'.esc_url($final_url).'">';     
                                }
                                $value .= $displayname;
                                if ((!empty($final_url)) && (in_array('link', $show_fields))) {
                                     $value .= '</a>';
                                }
                            } 
                        } elseif ($key_lower === 'familyname') {    
                            if (!empty($person->titleOfNobility)) {
                                $value = esc_html($person->titleOfNobility . ' ');
                            }
                            $value .= esc_html((string) $person->familyName);
                        } elseif ($key_lower === 'givenname') {    
                            $value = esc_html((string) $person->givenName);
                        } elseif ($key_lower === 'honorificprefix') {    
                            $value = esc_html((string) $person->honorificPrefix);
                        } elseif ($key_lower === 'honorificsuffix') {    
                            $value = esc_html((string) $person->honorificSuffix);                  
                        } elseif ($key_lower === 'jobtitle') {
                            if (!empty($contact)) {
                                $jobtitleformat = $this->config->get('jobtitle_format') ?: '#functionlabel#';
                                $value = esc_html($contact->getJobTitle($lang, $jobtitleformat));
                            }
                        } elseif (($key_lower === 'socialmedia') || ($key_lower === 'socials')) { 
                            if (!empty($contact)) {
                                $value = $contact->getSocialMedia('span');
                            }
                        } elseif (($key_lower === 'room') && !in_array('address', $show_fields_lower, true)) {
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
                            $phonenumbers = $person->getPhone();     
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

                                        $formattedValue = '<a itemprop="faxNumber" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                                        $wval .= '<span class="value icon"><span class="screen-reader-text">'.__('Fax','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                    }
                                }
                                $value = $wval;      
                            }
                        } elseif ($key_lower === 'organization')  {    
                            if (!empty($contact)) {
                                $value = esc_html($contact->getOrganizationName($lang));
                            }
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


                        } elseif ($key_lower === 'image')  {      
                            $value = $person->getImage();
                        } elseif ($key_lower === 'teasertext')  {     
                            $wval = $person->getTeasertext($lang);
                            if (!empty($wval)) {
                                $value = '<div class="value">'.wp_kses_post($wval).'</div>';
                            }
                        } elseif (($key_lower === 'floor') && !in_array('address', $show_fields_lower, true)) {
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
                            if (!empty($workplaces) && !empty($contact)) {
                                $room = in_array('room', $show_fields_lower, true);
                                $floor = in_array('floor', $show_fields_lower, true);

                                $wval = '';
                                $seen      = [];
                                foreach ($workplaces as $w => $wdata) {
                                    $html = (string) $contact->getAddressByWorkplace($wdata, false, $lang, $room, $floor);
                                    if ($html === '') {
                                        continue;
                                    }
                                    // Kanonische Signatur: HTML → Text, Entities decodieren, trimmen, Whitespaces normalisieren, lowercasing
                                   $dedupe_key = strtolower(
                                        preg_replace(
                                            '/\s+/u',
                                            ' ',
                                            trim(wp_strip_all_tags(html_entity_decode($html)))
                                        )
                                    );

                                    if ($dedupe_key === '') {
                                        continue;
                                    }

                                    if (!isset($seen[$dedupe_key])) {
                                        $seen[$dedupe_key] = true;
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
                            if (!empty($workplaces) && !empty($contact)) {
                                $hours = '';

                                foreach ($workplaces as $w => $wdata) {
                                    if (!empty($wdata['officeHours'])) { 
                                        $hours .= $contact->getConsultationsHours($wdata, 'officeHours', true, $lang);
                                    }
                                } 
                                $value = $hours;
                            }
                        } elseif ($key_lower === 'consultationhours')  {             
                            if (!empty($workplaces) && !empty($contact)) {
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
                    
            } ?>            
        </ul>
    <?php else : ?>
        <div class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir') ?> </div>
    <?php endif; ?>

</div>