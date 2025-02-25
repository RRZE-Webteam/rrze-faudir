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
    <div class="format-compact-wrapper">
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
                    <div class="image">
                        <?php echo $person->getImage(); ?>
                    </div>
                    <div class="compact-body">
                        <?php
                        $value = '';
                        if (!empty($final_url)) {
                            $value .= '<a itemprop="url" href="'.esc_url($final_url).'">';     
                        }
                        $value .= $displayname;
                        if (!empty($final_url)) {
                            $value .= '</a>';
                        }
                        echo '<header>'.$value.'</header>';    

                        echo $contact->getJobTitle($lang);
                        
                        $address = '';
                        if (!empty($workplaces)) {
                                $wval = '';
                                foreach ($workplaces as $w => $wdata) {
                                    $wval .= $contact->getAddressByWorkplace($wdata, false);
                                }
                                $address = $wval;      
                        }
                        echo $address;
                        
                        ?>
                    </div>
                    <div class="compact-link">
                        
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