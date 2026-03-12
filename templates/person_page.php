<?php
// Template file for RRZE FAUDIR
use RRZE\FAUdir\FAUdirUtils;
use RRZE\FAUdir\Person;
use RRZE\FAUdir\Config;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="faudir">
<?php
    $available_fields = $this->config->getFieldsByFormat('page');
    $normalize_titles = $this->config->get('default_normalize_honorificPrefix');
    $lang = FAUdirUtils::getLang();


    do_action( 'rrze.log.notice',"FAUdir\Template (person_page.php). Pre render with template {$templatefile}: ", $show_fields);

    
    if (!empty($persons)) { ?>
    <div class="format-page">
    <?php foreach ($persons as $persondata) {
       if (isset($persondata['error'])) {  
            if ($this->config->get('show_error_message')) {
            ?>
            <div class="faudir-error">
                <?php echo esc_html($persondata['message']); ?>
            </div>
            <?php }
        } else { 
            if (!empty($persondata)) {
                // do_action('rrze.log.info', "person-page.php: Preparing data for person: ", $persondata);
                $person = new Person($persondata);
                $person->setConfig($this->config);
                $formatstring = '';
                if (!empty($format_displayname)) {
                    $formatstring = $format_displayname;
                }
                $displayname = $person->getDisplayName(true, $normalize_titles,$formatstring);
                $mailadresses= $person->getEMail();
                $phonenumbers = $person->getPhone();                        
                if (!empty($url) && ($url !== '#')) {
                    $final_url = $url;
                } elseif ($url == '#') {
                    $final_url = '';
                } else {
                    $final_url = $person->getTargetURL($this->config->get('fallback_link_faudir'));
                }
                
      
                // do_action('rrze.log.info', "person-page.php: Getting contact data with role {$role}: ", $person);

                $contact = $person->getPrimaryContact($role);
                // do_action('rrze.log.info', "person-page.php: Results.. ", $contact);

                $workplaces = [];
                if (!empty($contact)) { 
                    $workplaces = $contact->getWorkplaces();                    
                }
                $aria_id = $person->getRandomId("section-title-");
        //           do_action('rrze.log.info', "person-page.php:Workplaces set", $workplaces);
                ?>

                <section class="format-page-container" aria-labelledby="<?php echo $aria_id;?>" itemscope itemtype="https://schema.org/Person">
                    <?php if (in_array('image', $show_fields)) {
                    
                     $image_content = $person->getImage('',false);
                        if (!empty($image_content)) { ?>
                        <div class="profile-image-section"> 
                            <?php echo $image_content;?>
                        </div>
                        <?php }
                    } ?>
                    <header class="profile-header">
                       <?php 

                        $value = '';
                        if ((!empty($final_url)) && (in_array('link', $show_fields))) {
                            $value .= '<a itemprop="url" href="'.esc_url($final_url).'">';     
                        }
                        $value .= $displayname;
                        if ((!empty($final_url)) && (in_array('link', $show_fields))) {
                            $value .= '</a>';
                        }                        
                        echo '<h1 id="'.$aria_id.'">'.$value.'</h1>';
                        
                        if (in_array('organization', $show_fields) ) {
                            echo '<p class="organisation_name">'. $contact->getOrganizationName($lang).'</p>';
                        }
                        if (in_array('jobTitle', $show_fields)) {
                            $jobtitleformat = '#functionlabel#';
                            if (!empty($this->config->get('jobtitle_format'))) {
                                $jobtitleformat = $this->config->get('jobtitle_format');
                            }                           
                            echo '<p class="jobtitle">'. $contact->getJobTitle($lang,$jobtitleformat).'</p>';
                        }
                        ?>
                
                     </header>
                     <div class="profile-details">   
                        <?php
                        $address = '';
 
                        if (in_array('address', $show_fields) ) {
                            if (!empty($workplaces)) {
                                $wval = '';
                                $room = $floor =  false;
                                if (in_array('room', $show_fields)) {
                                    $room = true;
                                }
                                if (in_array('floor', $show_fields))  {
                                    $floor = true;
                                }
                                
                                $seen      = [];
                                foreach ($workplaces as $w => $wdata) {
                                    $html = (string) $contact->getAddressByWorkplace($wdata, false, $lang, $room, $floor);
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

                                
                                $address .= $wval;      
                            }
                           
                        }
                        if (!empty($address)) {
                            echo '<div class="profile-address">';
                            echo '<h2 class="address-title">'.__('Address', 'rrze-faudir').'</h2>';
                            echo $address;
                            echo '</div>';
                        }
                        
                        
                        
                        
                        $contactlist = '';
                        if (in_array('email', $show_fields) ) {
                            $mailadresses= $person->getEMail();
                            $wval = '';
                            foreach ($mailadresses as $mail) {
                                if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                                    $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a>';
                                    $wval .= '<span class="value"><span class="screen-reader-text">'.__('Email','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                }                 
                            }
                            if (!empty($wval)) {
                                $contactlist .=  '<li class="email listcontent">'.$wval.'</li>';
                            }
                        }
                        
                        if (in_array('phone', $show_fields) ) {
                            $wval = '';                                    
                            foreach ($phonenumbers as $phone) {
                                $formattedPhone = FaudirUtils::format_phone_number($phone);
                                $cleanTel = preg_replace('/[^\+\d]/', '', $phone);
                                $formattedValue = '<a itemprop="telephone" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                                $wval .= '<span class="value"><span class="screen-reader-text">'.__('Phone','rrze-faudir').': </span>'.$formattedValue.'</span>';

                            }
                            if (!empty($wval)) {
                                $contactlist .= '<li class="phone listcontent">'.$wval.'</li>';
                            }
                        }
                        if (in_array('fax', $show_fields) ) { 
                           if (!empty($workplaces)) {
                                $wval = '';
                                foreach ($workplaces as $w => $wdata) {
                                    if (!empty($wdata['fax'])) {
                                        $formattedPhone = FaudirUtils::format_phone_number($wdata['fax']);
                                        $cleanTel = preg_replace('/[^\+\d]/', '', $wdata['fax']);

                                        $formattedValue = '<a itemprop="fax" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                                        $wval .= '<span class="value"><span class="screen-reader-text">'.__('Fax','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                    }
                                }
                                if (!empty($wval)) {
                                     $contactlist .= '<li class="fax listcontent">'.$wval.'</li>';
                                } 
                            }
                        }
                        if (in_array('url', $show_fields)) {
                            if (!empty($workplaces)) {
                                $wval = '';
                                foreach ($workplaces as $w => $wdata) {
                                    if (!empty($wdata['url'])) {
                                        $displayValue = preg_replace('/^https?:\/\//i', '', $wdata['url']);     
                                        $formattedValue = '<a href="' . esc_url($wdata['url']) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                                        $wval .= '<span class="value"><span class="screen-reader-text">'.__('URL','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                    }
                                }
                                if (!empty($wval)) {
                                    $contactlist .= '<li class="url listcontent">'.$wval.'</li>';
                                }
                            }
                        }
                        
                        
                        
                        if (!empty($contactlist)) {                       
                            echo '<div class="profile-contact">';
                            echo '<h2 class="contact-title">'.__('Contact', 'rrze-faudir').'</h2>';
                            echo '<ul class="icon list-icons">';
                            echo $contactlist;
                            echo '</ul>';
                            echo '</div>';
                        }
                        
                        if (in_array('socialmedia', $show_fields) ) {
                            $some = $contact->getSocialMedia();
                            if (!empty($some)) {
                                echo '<div class="profile-socialmedia">';
                                echo '<h2 class="screen-reader-text">'.__('Social Media and Websites', 'rrze-faudir').'</h2>';
                                echo $some;
                                echo '</div>';
                            }
                        }
                        if (in_array('officehours', $show_fields)  || in_array('consultationhours', $show_fields) ) {
                            if (!empty($workplaces)) {
                                 if (count($workplaces) > 1) {
                                    $showaddress = true;
                                    $roompos = true;
                                } else {
                                    $showaddress = false;
                                    $roompos = false;
                                }
                                
                                
                                $hours = $cons =  '';
                                if (in_array('consultationhours', $show_fields) ) {
                                    foreach ($workplaces as $w => $wdata) {
                                        if (!empty($wdata['consultationHours'])) {
                                            $hours .= $contact->getConsultationsHours($wdata, 'consultationHours', $showaddress, $lang,$roompos );
                                        }                                  
                                        $hours .= $contact->getConsultationbyAggreement($wdata);
                                        
                                    }
                 
                                    if (!empty($hours)) {
                                            $cons .=  '<h2 class="consultation-title">'.__('Consultation Hours', 'rrze-faudir').'</h2>';
                                            $cons .= $hours;
                                    }
                                }
                                $hours = '';
                                if (in_array('officehours', $show_fields)) {
                                    foreach ($workplaces as $w => $wdata) {
                                        if (!empty($wdata['officeHours'])) { 
                                            $hours .= $contact->getConsultationsHours($wdata, 'officeHours', $showaddress, $lang, $roompos);
                                        }
                                    } 
                                    if (!empty($hours)) {
                                            $cons .=  '<h2 class="consultation-title">'. __('Office Hours', 'rrze-faudir').'</h2>';
                                            $cons .= $hours;
                                    }
                                }
                                
                                
                                
                               if (!empty($cons)) {   
                                   echo '<div class="profile-consultation">';
                                   echo $cons;
                                   echo '</div>';
                               }
                            }   
                           
                        }
            
                        
                        ?>
                     </div>
                    <div class="profile-content-region">
                    <?php
                   
                    
                    $profilcontent = '';
                    if (in_array('teasertext', $show_fields) ) {    
                        
                            $wval = $person->getTeasertext();
                            if (!empty($wval)) {
                                $profilcontent .= '<div class="teasertext">';
                                $profilcontent .= wp_kses_post($wval);
                                $profilcontent .= '</div>';
                            }
                    }
                    
                    if (in_array('content', $show_fields)) {                          
                            $wval = $person->getContent();
                            if (!empty($wval)) {
                                $profilcontent .= '<div class="content">';
                                $profilcontent .= $wval;
                                $profilcontent .= '</div>';
                            }
                    }
                        
                    if (!empty($profilcontent)) {
                        echo '<div class="profile-content">';             
                        echo $profilcontent;
                        echo '</div>';
                    }
                    ?>
                    </div>
                </section>    
            <?php } 
        } 
    } ?>
    </div>
<?php } else { ?>
    <div class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?></div>
<?php } ?>

</div>