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
    $available_fields = $config->getFieldsByFormat('org-compact');
    $opt = $config->getOptions();        
    $lang = FAUdirUtils::getLang();

    
    $dbopt = get_option('rrze_faudir_options', []);
     
     
    //echo "DB OPTIONS:<br>";
    //echo Debug::get_html_var_dump($dbopt);
    //echo "<hr>";
    
    if (!empty($orgdata)) { ?>
    <div class="format-org-compact">
    <?php 

                $org = new Organization($orgdata);                
                $displayname = $org->getName();
                
                         
                if (!empty($url)) {
                    $final_url = $url;
                } else {
                    $final_url = $org->getURL();
                }

                $aria_id = $org->getRandomId("section-title-");
                ?>

                <section class="format-org-compact-container" aria-labelledby="<?php echo $aria_id;?>" itemscope itemtype="https://schema.org/Organization">
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
                        ?>
                     </header>
                     <div class="profile-details">   
                        <?php
                        $address = '';

                        if (in_array('address', $show_fields) ) {                               
                                $showmap = false;
                                if (in_array('faumap', $show_fields) ) {
                                    $showmap = true;
                                }

                                $address .= $org->getAddressOutput(false, $lang, $showmap);
                                                  
                        }
                        if (!empty($address)) {
                            echo '<div class="profile-address">';
                            echo '<h2 class="address-title screen-reader-text">'.__('Address', 'rrze-faudir').'</h2>';
                            echo $address;
                            echo '</div>';
                        }
                        

            
                        
                        $contactlist = '';
                        if (in_array('email', $show_fields)) {
                            $mail = $org->getEMail();
                            if (!empty($mail)) {
                                $wval = '';

                                if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                                    $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a>';
                                    $wval .= '<span class="value"><span class="screen-reader-text">'.__('Email','rrze-faudir').': </span>'.$formattedValue.'</span>';
                                }                 

                                if (!empty($wval)) {
                                    $contactlist .=  '<li class="email">'.$wval.'</li>';
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
                                $contactlist .= '<li class="phone">'.$wval.'</li>';
                               
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
                                $contactlist .= '<li class="fax">'.$wval.'</li>';
                               
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
                                    $contactlist .= '<li class="url">'.$wval.'</li>';
                                }
                            }   
                        }
                        
                        
                        
                        if (!empty($contactlist)) {                       
                            echo '<div class="profile-contact">';
                            echo '<h2 class="contact-title screen-reader-text">'.__('Contact', 'rrze-faudir').'</h2>';
                            echo '<ul class="icon">';
                            echo $contactlist;
                            echo '</ul>';
                            echo '</div>';
                        }
                        
                       $profilcontent = '';
                       if (in_array('content', $show_fields)) {                          
                            $wval = $org->getContentText($lang);
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
    </div>
<?php } else { ?>
    <div class="faudir-error"><?php echo esc_html__('No org entry could be found.', 'rrze-faudir'); ?></div>
<?php } ?>

</div>