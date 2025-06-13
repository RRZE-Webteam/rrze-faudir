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
    $available_fields = $config->getFieldsByFormat('compact');
    $opt = $config->getOptions();        
    $lang = FAUdirUtils::getLang();

    
    $dbopt = get_option('rrze_faudir_options', []);
     
     
    //echo "DB OPTIONS:<br>";
    //echo Debug::get_html_var_dump($dbopt);
    //echo "<hr>";
    
    if (!empty($persons)) { ?>
    <div class="format-compact">
    <?php foreach ($persons as $persondata) {
        if (isset($persondata['error'])) {  
            if ($opt['show_error_message']) {
            ?>
            <div class="faudir-error">
                <?php echo esc_html($persondata['message']); ?>
            </div>
            <?php }
        } else { 
            if (!empty($persondata)) {

                $person = new Person($persondata);
                $formatstring = '';
                if (!empty($format_displayname)) {
                    $formatstring = $format_displayname;
                }
                $displayname = $person->getDisplayName(true, false,$formatstring);
                $mailadresses= $person->getEMail();
                $phonenumbers = $person->getPhone();                        
                if (!empty($url)) {
                    $final_url = $url;
                } else {
                    $final_url = $person->getTargetURL($opt['fallback_link_faudir']);
                }
                $contact = $person->getPrimaryContact();
                $workplaces = [];
                
               
                if (!empty($contact)) { 
                    $workplaces = $contact->getWorkplaces();                    
                }
                $aria_id = $person->getRandomId("section-title-");
                ?>

                <section class="format-compact-container" aria-labelledby="<?php echo $aria_id;?>" itemscope itemtype="https://schema.org/Person">
                    <?php if (in_array('image', $show_fields)) { ?>
                    <div class="profile-image-section">
                        <?php echo $person->getImage(); ?>
                    </div>
                    <?php } ?>
                    <header class="profile-header">
                       <?php 

                        $value = '';
                        if (!empty($final_url)) {
                            $value .= '<a itemprop="url" href="'.esc_url($final_url).'">';     
                        }
                        $value .= $displayname;
                        if (!empty($final_url)) {
                            $value .= '</a>';
                        }                        
                        echo '<h1 id="'.$aria_id.'">'.$value.'</h1>';
                        
                        if (in_array('organization', $show_fields)) {
                            echo '<p class="organisation_name">'. $contact->getOrganizationName($lang).'</p>';
                        }
                        if (in_array('jobTitle', $show_fields)) {
                            $jobtitleformat = '#functionlabel#';
                            if (!empty($opt['jobtitle_format'])) {
                                $jobtitleformat = $opt['jobtitle_format'];
                            }                           
                            echo '<p class="jobtitle">'. $contact->getJobTitle($lang,$jobtitleformat).'</p>';
                        }
                        ?>
                
                     </header>
                     <div class="profile-details">   
                        <?php
                        $address = '';

                        if (in_array('address', $show_fields)) {
                            if (!empty($workplaces)) {
                                
                                if ((in_array('room', $show_fields)) || (in_array('floor', $show_fields))) {
                                    $roomfloor = true;
                                } else {
                                    $roomfloor = false;
                                }
                                
                                
                                $wval = '';
                                foreach ($workplaces as $w => $wdata) {
                                    $wval .= $contact->getAddressByWorkplace($wdata, false, $lang, $roomfloor);
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
                        
                        
                        if (in_array('officehours', $show_fields) || (in_array('consultationhours', $show_fields) )) {

                            if (!empty($workplaces)) {
                                
                                if (count($workplaces) > 1) {
                                    $showaddress = true;
                                    $roompos = true;
                                } else {
                                    $showaddress = false;
                                    $roompos = false;
                                }
                                
                                
                                $hours = $cons =  '';
                                if (in_array('consultationhours', $show_fields)) {
                                    foreach ($workplaces as $w => $wdata) {
                                        if (!empty($wdata['consultationHours'])) {
                                            $hours .= $contact->getConsultationsHours($wdata, 'consultationHours', $showaddress, $lang, $roompos );
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
                        
                        if (in_array('phone', $show_fields)) {
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
                        
                        if (in_array('fax', $show_fields)) { 
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
                        if (in_array('url', $show_fields) ) {
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
                            echo '<ul class="icon">';
                            echo $contactlist;
                            echo '</ul>';
                            echo '</div>';
                        }
                        
                        if (in_array('socialmedia', $show_fields) ) {
                            $some = $contact->getSocialMedia('span');
                            if (!empty($some)) {
                                echo '<div class="profile-socialmedia">';
                                echo $some;
                                echo '</div>';
                            }
                        }
                        ?>
                    
                    <?php
                     if (in_array('link', $show_fields) ) {                          
                            if (!empty($final_url)) {
                                $link = '<div class="profile-link">';
                                $link .= '<a class="buttonlink" itemprop="sameAs" href="'.esc_url($final_url).'">';  
                                
                                $opt = $config->getOptions();                       
                                $linkttitle = $opt['business_card_title'];
                                if (empty($linkttitle)) {
                                     $linkttitle  = __('User profil', 'rrze-faudir');
                                }
                                
                                $link .= $linkttitle;
                                $link .= '</a>';
                                $link .= '</div>';
                                
                                
                                echo $link;
                            }
                    }
                    
                    
                    $profilcontent = '';
                    if (in_array('teasertext', $show_fields)) {    
                        
                            $wval = $person->getTeasertext($lang);
                            if (!empty($wval)) {
                                $profilcontent .= '<div class="teasertext">';
                                $profilcontent .= wp_kses_post($wval);
                                $profilcontent .= '</div>';
                            }
                    }
                    
                    if (in_array('content', $show_fields)) {                          
                            $wval = $person->getContent($lang);
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