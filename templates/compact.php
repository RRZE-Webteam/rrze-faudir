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
    $lang = FAUdirUtils::getLang();


    
    if (!empty($persons)) { ?>
    <div class="format-compact">
    <?php foreach ($persons as $persondata) {
        if (isset($persondata['error'])) { ?>
            <div class="faudir-error">
                <?php echo esc_html($persondata['message']); ?>
            </div>
        <?php } else { 
            if (!empty($persondata)) {

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
                ?>

                <section class="format-compact-container" itemscope itemtype="https://schema.org/Person">
                    <?php if (in_array('image', $show_fields) && !in_array('image', $hide_fields) && isset($available_fields['image'])) { ?>
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
                        echo '<h1>'.$value.'</h1>';
                        
                        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields) && isset($available_fields['organization'])) {
                            echo '<p class="organisation_name">'. $contact->getOrganizationName($lang).'</p>';
                        }
                        if (in_array('jobTitle', $show_fields) && !in_array('jobTitle', $hide_fields) && isset($available_fields['jobTitle'])) {
                             echo '<p class="jobtitle">'. $contact->getJobTitle($lang).'</p>';
                        }
                        ?>
                
                     </header>
                     <div class="profile-details">   
                        <?php
                        $address = '';
 
                        if (in_array('address', $show_fields) && !in_array('address', $hide_fields) && isset($available_fields['address'])) {
                            if (!empty($workplaces)) {
                                
                                $showmap = false;
                                if (in_array('faumap', $show_fields) && !in_array('faumap', $hide_fields)) {
                                    $showmap = true;
                                }
                                $showroomfloor = false;
                                if ((in_array('room', $show_fields) && !in_array('room', $hide_fields))
                                || (in_array('floor', $show_fields) && !in_array('floor', $hide_fields))) {
                                    $showroomfloor = true;
                                }
                                
                                
                                $wval = '';
                                foreach ($workplaces as $w => $wdata) {
                                    $wval .= $contact->getAddressByWorkplace($wdata, false, $lang, $showroomfloor, $showmap);
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
                        
                        
                        if ((in_array('officehours', $show_fields) && !in_array('officehours', $hide_fields) && isset($available_fields['officehours']))
                         || (in_array('consultationhours', $show_fields) && !in_array('consultationhours', $hide_fields) && isset($available_fields['consultationhours']))) {
                           
                            
                            $showmap = false;
                            if (in_array('faumap', $show_fields) && !in_array('faumap', $hide_fields)) {
                                $showmap = true;
                            }
                            $showroomfloor = false;
                            if ((in_array('room', $show_fields) && !in_array('room', $hide_fields))
                            || (in_array('floor', $show_fields) && !in_array('floor', $hide_fields))) {
                                $showroomfloor = true;
                            }
                            
                            if (!empty($workplaces)) {
                                $hours = $cons =  '';
                                foreach ($workplaces as $w => $wdata) {
                                    if (!empty($wdata['consultationHours'])) {
                                        $hours .= $contact->getConsultationsHours($wdata, 'consultationHours', true, $lang, $showroomfloor, $showmap );
                                    }
                                }
                                if (!empty($hours)) {
                                        $cons .=  '<h2 class="consultation-title">'.__('Consultation Hours', 'rrze-faudir').'</h2>';
                                        $cons .= $hours;
                                }
                                
                                $hours = '';
                                foreach ($workplaces as $w => $wdata) {
                                    if (!empty($wdata['officeHours'])) { 
                                        $hours .= $contact->getConsultationsHours($wdata, 'officeHours', true, $lang, $showroomfloor, $showmap);
                                    }
                                } 
                                if (!empty($hours)) {
                                        $cons .=  '<h2 class="consultation-title">'. __('Office Hours', 'rrze-faudir').'</h2>';
                                        $cons .= $hours;
                                }
                  
                               if (!empty($cons)) {   
                                   echo '<div class="profile-consultation">';
                                   echo $cons;
                                   echo '</div>';
                               }
                            }   
                           
                        }
            
                        
                        $contactlist = '';
                        if (in_array('email', $show_fields) && !in_array('email', $hide_fields) && isset($available_fields['email'])) {
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
                        
                        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields) && isset($available_fields['phone'])) {
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
                        if (in_array('url', $show_fields) && !in_array('url', $hide_fields) && isset($available_fields['url'])) {
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
                        
                        if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields) && isset($available_fields['socialmedia'])) {
                            $some = $contact->getSocialMedia('span');
                            if (!empty($some)) {
                                echo '<div class="profile-socialmedia">';
                                echo $some;
                                echo '</div>';
                            }
                        }
                        ?>
                    
                    <?php
                     if (in_array('link', $show_fields) && !in_array('link', $hide_fields) && isset($available_fields['link'])) {                          
                            if (!empty($final_url)) {
                                $link = '<div class="profile-link">';
                                $link .= '<a class="buttonlink" itemprop="sameAs" href="'.esc_url($final_url).'">';     
                                $link .= __('User profil', 'rrze-faudir');
                                $link .= '</a>';
                                $link .= '</div>';
                                
                                
                                echo $link;
                            }
                    }
                    
                    
                    $profilcontent = '';
                    if (in_array('teasertext', $show_fields) && !in_array('teasertext', $hide_fields) && isset($available_fields['teasertext'])) {    
                        
                            $wval = $person->getTeasertext($lang);
                            if (!empty($wval)) {
                                $profilcontent .= '<div class="teasertext">';
                                $profilcontent .= wp_kses_post($wval);
                                $profilcontent .= '</div>';
                            }
                    }
                    
                    if (in_array('content', $show_fields) && !in_array('content', $hide_fields) && isset($available_fields['content'])) {                          
                            $wval = $person->getContent($lang);
                            if (!empty($wval)) {
                                $profilcontent .= '<div class="content">';
                                $profilcontent .= wp_kses_post($wval);
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