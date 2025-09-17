<?php
// Template file for RRZE FAUDIR
use RRZE\FAUdir\Debug;
use RRZE\FAUdir\FAUdirUtils;
use RRZE\FAUdir\Organization;
use RRZE\FAUdir\Config;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="faudir">
<?php
    $config = new Config;
   // $available_fields = $config->getFieldsByFormat('org-compact');
    $opt = $config->getOptions();        
    $lang = FAUdirUtils::getLang();

    
    $dbopt = get_option('rrze_faudir_options', []);
     
     
    
    if (!empty($orgdata)) { ?>
    <div class="format-org-compact">
    <?php 

                $org = new Organization($orgdata);                
                $displayname = $org->getName();
                
                
       //         echo "<p>Showfields:<br> <b>";
       //        echo Debug::get_html_var_dump($show_fields);
       //         echo "</b></p>";
           //     echo Debug::get_html_var_dump($org);
                         
                if (!empty($url)) {
                    $final_url = $url;
                } else {
                    $final_url = $org->getURL();
                }

                $aria_id = $org->getRandomId("section-title-");
                ?>

                <section class="format-org-compact-container" aria-labelledby="<?php echo esc_attr($aria_id);?>" itemscope itemtype="https://schema.org/Organization">
                    <header class="profile-header">
                       <?php 

                        $value_escaped = '';
                        if (!empty($final_url)) {
                            $value_escaped .= '<a itemprop="url" href="'.esc_url($final_url).'">';     
                        }
                        $value_escaped .= $displayname;
                        if (!empty($final_url)) {
                            $value_escaped .= '</a>';
                        }                        
                        echo '<h1 id="'.esc_attr($aria_id).'">'.$value_escaped.'</h1>';
                        ?>
                     </header>
                     <div class="profile-details">   
                        <?php
                        
                        if (in_array('alternateName', $show_fields) ) {                               
                            $alternateName = $org->getalternateName();
                            if (!empty($alternateName)) {
                                echo '<p itemprop="alternateName">'.$alternateName.'</p>';
                            }    
                                                  
                        }
                        
                        
                        $address_escaped = '';
                        if (in_array('address', $show_fields) ) {                               
                                $address_escaped = $org->getAddressOutput(false, $lang, false);                                                  
                        }
                        if (!empty($address_escaped)) {
                            echo '<div class="profile-address">';
                            echo '<h2 class="address-title screen-reader-text">'.__('Address', 'rrze-faudir').'</h2>';
                            echo $address_escaped;
                            echo '</div>';
                        }
                        
                        $postal_escaped = '';
                        if (in_array('postalAddress', $show_fields) ) {                               
                                $postal_escaped = $org->getPostalAddressOutput(false, $lang);
                                                  
                        }
                        if (!empty($postal_escaped)) {
                            echo '<div class="profile-address">';
                            echo '<h2 class="address-title screen-reader-text">'.__('Postal Address', 'rrze-faudir').'</h2>';
                            echo $postal_escaped;
                            echo '</div>';
                        }
                        
                        
                        
                        $contactlist_escaped = '';
                        if (in_array('email', $show_fields)) {
                            $mail = $org->getEMail();
                            if (!empty($mail)) {
                                $wval = '';

                                if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                                    $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a>';
                                    $wval .= '<span class="value"><span class="screen-reader-text">'.__('Email','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                }                 

                                if (!empty($wval)) {
                                    $contactlist_escaped .=  '<li class="email">'.$wval.'</li>';
                                }
                            }
                        }
                        
                        if (in_array('phone', $show_fields) ) {
                            $phone = $org->getPhone();              
                            $wval = '';                                    
                            if (!empty($phone)) {
                                $formattedPhone = FaudirUtils::format_phone_number($phone);
                                $cleanTel = preg_replace('/[^\+\d]/', '', $phone);
                                $formattedValue = '<a itemprop="telephone" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                                $wval .= '<span class="value"><span class="screen-reader-text">'.__('Phone','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                $contactlist_escaped .= '<li class="phone">'.$wval.'</li>';
                               
                            }
                        }
                        if (in_array('fax', $show_fields) ) {
                            $phone = $org->getFax();              
                            $wval = '';                                    
                            if (!empty($phone)) {
                                $formattedPhone = FaudirUtils::format_phone_number($phone);
                                $cleanTel = preg_replace('/[^\+\d]/', '', $phone);
                                $formattedValue = '<a itemprop="fax" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                                $wval .= '<span class="value"><span class="screen-reader-text">'.__('Phone','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                $contactlist_escaped .= '<li class="fax">'.$wval.'</li>';
                               
                            }
                        }
                        if (in_array('url', $show_fields) ) {
                            $url = $org->getURL();
                            $wval = '';         
                       
                            if (!empty($url)) {
                                $displayValue = preg_replace('/^https?:\/\//i', '', $url);     
                                $formattedValue = '<a href="' . esc_url($url) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                                $wval .= '<span class="value"><span class="screen-reader-text">'.__('URL','rrze-faudir').': </span>'.$formattedValue.'</span>';
                           
                                if (!empty($wval)) {
                                    $contactlist_escaped .= '<li class="url">'.$wval.'</li>';
                                }
                            }   
                        }
                        if (in_array('faumap', $show_fields) ){          
                             $map = $org->getFAUMap();
                             if (!empty($map)) {
                                if (preg_match('/^https?:\/\/karte\.fau\.de/i', $map)) {
                                    $formattedValue = '<a class="texticon faumap" href="' . esc_url($map) . '" itemprop="url">' . __('FAU Map', 'rrze-faudir') . '</a>';
                                    $faumap = '<span class="value icon"><span class="screen-reader-text">'.__('Map','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                    
                                    
                                     if (!empty($faumap)) {
                                        $contactlist_escaped .= '<li class="faumap url">'.$faumap.'</li>';
                                    }
                                    
                                }
                             }
                            
                            
                        }
                        
                        
                        
                        
                        if (!empty($contactlist_escaped)) {                       
                            echo '<div class="profile-contact">';
                            echo '<h2 class="contact-title screen-reader-text">'.__('Contact', 'rrze-faudir').'</h2>';
                            echo '<ul class="icon">';
                            echo $contactlist_escaped;
                            echo '</ul>';
                            echo '</div>';
                        }
                        
                        if (in_array('socialmedia', $show_fields) ) {
                            $some_escaped = $org->getSocialMedia('span');
                            if (!empty($some_escaped)) {
                                echo '<div class="profile-socialmedia">';
                                echo '<h2 class="screen-reader-text">'.__('Social Media and Websites', 'rrze-faudir').'</h2>';
                                echo $some_escaped;
                                echo '</div>';
                            }
                        }
                         if (in_array('officehours', $show_fields) || (in_array('consultationhours', $show_fields) )) {
                                $hours = $cons_escaped =  '';
                                if (in_array('consultationhours', $show_fields)) {
                                   
                                    $hours .= $org->getConsultationsHours( 'consultationHours', false, $lang, true );                                   
                                    if (!empty($hours)) {
                                            $cons_escaped .=  '<h2 class="consultation-title">'.__('Consultation Hours', 'rrze-faudir').'</h2>';
                                            $cons_escaped .= $hours;
                                    }
                                }
                                $hours = '';
                                if (in_array('officehours', $show_fields)) {
                                    $label = __('Opening hours', 'rrze-faudir');     
                                    $hours .= $org->getConsultationsHours('officeHours', false, $lang, true, $label);
               
                                    if (!empty($hours)) {
                                            $cons_escaped .=  '<h2 class="consultation-title">'. $label.'</h2>';
                                            $cons_escaped .= $hours;
                                    }
                                }
                               if (!empty($cons_escaped)) {   
                                   echo '<div class="profile-consultation">';
                                   echo $cons_escaped;
                                   echo '</div>';
                               }
                        }
                        
                       $profilcontent_escaped = '';
                       if (in_array('text', $show_fields)) {                          
                            $wval = $org->getContentText($lang);
                            if (!empty($wval)) {
                                $profilcontent_escaped .= '<div class="text">';
                                $profilcontent_escaped .= $wval;
                                $profilcontent_escaped .= '</div>';
                            }
                        }

                        if (!empty($profilcontent_escaped)) {
                            echo '<div class="profile-content">';             
                            echo $profilcontent_escaped;
                            echo '</div>';
                        }
                  
                        ?>
                    
                   
                 </div>
                </section>    
    </div>
<?php } else { ?>
    <div class="faudir-error"><?php echo esc_html__('No org entry could be found.', 'rrze-faudir'); ?></div>
<?php } ?>

</div>