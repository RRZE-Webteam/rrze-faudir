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
    $lang = FAUdirUtils::getLang();


    if (!empty($persons)) { ?>
    <div class="format-compact-container">
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

                <section class="format-compact" itemscope itemtype="https://schema.org/Person">
                    <?php if (in_array('image', $show_fields) && !in_array('image', $hide_fields)) { ?>
                    <div class="profile-image-section">
                        <?php echo $person->getImage(); ?>
                    </div>
                    <?php } ?>
                    <div class="profile-details">
                        <?php
                        $value = '';
                        if (!empty($final_url)) {
                            $value .= '<a itemprop="url" href="'.esc_url($final_url).'">';     
                        }
                        $value .= $displayname;
                        if (!empty($final_url)) {
                            $value .= '</a>';
                        }
                        echo '<header class="profile-header">';
                        
                        echo '<h1>'.$value.'</h1>';
                        
                        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields)) {
                            echo '<p class="organisation_name">'. $contact->getOrganizationName($lang).'</p>';
                        }
                        if (in_array('jobTitle', $show_fields) && !in_array('jobTitle', $hide_fields)) {
                             echo '<p class="jobtitle">'. $contact->getJobTitle($lang).'</p>';
                        }
                        echo '</header>';    
                        
                        
                        $address = '';
 
                        if (in_array('address', $show_fields) && !in_array('address', $hide_fields)) {
                            if (!empty($workplaces)) {
                                    $wval = '';
                                    foreach ($workplaces as $w => $wdata) {
                                        $wval .= $contact->getAddressByWorkplace($wdata, false, $lang, true);
                                    }
                                    $address .= $wval;      
                            }
                           
                        }
                        if (!empty($address)) {
                            echo '<div class="profile-address">';
                            echo $address;
                            echo '</div>';
                        }
                        
                        
                        if ((in_array('officehours', $show_fields) && !in_array('officehours', $hide_fields))
                         || (in_array('consultationhours', $show_fields) && !in_array('consultationhours', $hide_fields))) {
                            echo '<div class="profile-consultation">';
                            if (!empty($workplaces)) {
                                $hours = '';
                                foreach ($workplaces as $w => $wdata) {
                                    if (!empty($wdata['consultationHours'])) {
                                        $hours .= $contact->getConsultationsHours($wdata, 'consultationHours', true, $lang );
                                    }
                                }
                                echo $hours;
                                
                                $hours = '';
                                foreach ($workplaces as $w => $wdata) {
                                    if (!empty($wdata['officeHours'])) { 
                                        $hours .= $contact->getConsultationsHours($wdata, 'officeHours', true, $lang);
                                    }
                                } 
                                  echo $hours;
                            }   
                            echo '</div>';
                        }
            
                        
                        $contactlist = '';
                        if (in_array('email', $show_fields) && !in_array('email', $hide_fields)) {
                            $mailadresses= $person->getEMail();
                            $wval = '';
                            foreach ($mailadresses as $mail) {
                                if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                                    $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a>';
                                    $wval .= '<span class="value"><span class="screen-reader-text">'.__('Email','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                }                 
                            }
                            if (!empty($wval)) {
                                $contactlist .=  '<li>'.$wval.'</li>';
                            }
                        }
                        
                        if (in_array('phone', $show_fields) && !in_array('phone', $hide_fields)) {
                            $wval = '';                                    
                            foreach ($phonenumbers as $phone) {
                                $formattedPhone = FaudirUtils::format_phone_number($phone);
                                $cleanTel = preg_replace('/[^\+\d]/', '', $phone);
                                $formattedValue = '<a itemprop="telephone" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                                $wval .= '<span class="value"><span class="screen-reader-text">'.__('Phone','rrze-faudir').': </span>'.$formattedValue.'</span>';

                            }
                            if (!empty($wval)) {
                                $contactlist .= '<li>'.$wval.'</li>';
                            }
                        }
                        if (in_array('url', $show_fields) && !in_array('url', $hide_fields)) {
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
                                    $contactlist .= '<li>'.$wval.'</li>';
                                }
                            }
                        }
                         if (in_array('faumap', $show_fields) && !in_array('faumap', $hide_fields)) {
                             if (!empty($workplaces)) {
                                $faumap = '';
                                foreach ($workplaces as $w => $wdata) {
                                    if (!empty($wdata['faumap'])) {
                                        if (preg_match('/^https?:\/\/karte\.fau\.de/i', $wdata['faumap'])) {
                                            $displayValue = preg_replace('/^https?:\/\//i', '', $wdata['faumap']);     
                                            $formattedValue = '<a href="' . esc_url($wdata['faumap']) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                                            $faumap .= '<span class="value"><span class="screen-reader-text">'.__('FAU Map','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                        }
                                    }
                                }
                                if (!empty($faumap)) {
                                    $contactlist .= '<li>'.$faumap.'</li>';
                                }
                            }
                        }
                        
                        
                        if (!empty($contactlist)) {                       
                            echo '<div class="profile-contact">';
                            echo '<ul>';
                            echo $contactlist;
                            echo '</ul>';
                            echo '</div>';
                        }
                        
                        if (in_array('socialmedia', $show_fields) && !in_array('socialmedia', $hide_fields)) {
                            echo '<div class="profile-socialmedia">';
                            echo $contact->getSocialMedia('span');
                            echo '</div>';
                        }
                        
                        if (in_array('teasertext', $show_fields) && !in_array('teasertext', $hide_fields)) {                        
                            $wval = $person->getTeasertext($lang);
                            if (!empty($wval)) {
                                echo '<div class="teasertext">';
                                echo '<div class="value">'.wp_kses_post($wval).'</div>';
                                echo '</div>';
                            }
                            
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