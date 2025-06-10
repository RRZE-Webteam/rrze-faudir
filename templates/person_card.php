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
    $available_fields = $config->getFieldsByFormat('card');
    $opt = $config->getOptions();        
    $lang = FAUdirUtils::getLang();


    
    if (!empty($persons)) { ?>
    <div class="format-card">
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
                $displayname = $person->getDisplayName(true, false,$formatstring,$show_fields,$hide_fields);
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

                <section class="format-card-container" aria-labelledby="<?php echo $aria_id;?>" itemscope itemtype="https://schema.org/Person">
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
                        echo '<h1 id="'.$aria_id.'">'.$value.'</h1>';
                        
                        if (in_array('organization', $show_fields) && !in_array('organization', $hide_fields) && isset($available_fields['organization'])) {
                            echo '<p class="organisation_name">'. $contact->getOrganizationName($lang).'</p>';
                        }
                        if (in_array('jobTitle', $show_fields) && !in_array('jobTitle', $hide_fields) && isset($available_fields['jobTitle'])) {
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
                        if (in_array('fax', $show_fields) && !in_array('fax', $hide_fields) && isset($available_fields['fax'])) {
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
                         if (in_array('link', $show_fields) && !in_array('link', $hide_fields) && isset($available_fields['link'])) {                          
                            if (!empty($final_url)) {
                                               
                                $displayValue = $opt['business_card_title'];
                                if (empty($displayValue)) {
                                     $displayValue = preg_replace('/^https?:\/\//i', '', $final_url);     
                                }

                                $formattedValue = '<a href="' . esc_url($final_url) . '" itemprop="sameAs">' . esc_html($displayValue) . '</a>';
                                $wval = '<span class="value"><span class="screen-reader-text">'.__('URL','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                $contactlist .= '<li class="profilurl listcontent">'.$wval.'</li>';
                                
                               
                            }
                    }
                        
                        
                        if (!empty($contactlist)) {                       
                            echo '<div class="profile-contact icon icon-list">';
                            echo '<h2 class="screen-reader-text">'.__('Contact', 'rrze-faudir').'</h2>';
                            echo '<ul>';
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