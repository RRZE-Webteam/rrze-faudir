<?php
use RRZE\FAUdir\FaudirUtils;
use RRZE\FAUdir\Person;

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="faudir">
<?php
$lang = FaudirUtils::getLang();
$normalize_titles = $this->config->get('default_normalize_honorificPrefix');

if (!empty($persons)) {
    ?>
    <div class="format-card">
        <?php
        foreach ($persons as $persondata) {
            if (isset($persondata['error'])) {
                if ($this->config->get('show_error_message')) {
                    ?>
                    <div class="faudir-error">
                        <?php echo esc_html($persondata['message']); ?>
                    </div>
                    <?php
                }
                continue;
            }

            if (empty($persondata)) {
                continue;
            }

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
                $workplaces = $contact->getWorkplaces();
            }

            $aria_id = $person->getRandomId("section-title-");
            $showname = '';

            if (in_array('displayname', $show_fields, true)) {
                $showname = $displayname;
            } elseif (in_array('familyName', $show_fields, true)) {
                if (!empty($person->titleOfNobility)) {
                    $showname = $person->titleOfNobility . ' ';
                }
                $showname .= $person->familyName;
            } elseif (in_array('givenName', $show_fields, true)) {
                $showname = $person->givenName;
            }
            ?>
            <section class="format-card-container" aria-labelledby="<?php echo esc_attr($aria_id); ?>" itemscope itemtype="https://schema.org/Person">
                <?php if (in_array('image', $show_fields, true)) { ?>
                    <div class="profile-image-section">
                        <?php
                        if ((empty($showname)) && (!empty($final_url))) {
                            echo $person->getImage(link_url: $final_url);
                        } else {
                            echo $person->getImage();
                        }
                        ?>
                    </div>
                <?php } ?>

                <header class="profile-header">
                    <?php
                    $value = '';
                    if (!empty($showname)) {
                        if ((!empty($final_url)) && in_array('link', $show_fields, true)) {
                            $value .= '<a itemprop="url" href="' . esc_url($final_url) . '">';
                        }

                        $value .= esc_html($showname);

                        if ((!empty($final_url)) && in_array('link', $show_fields, true)) {
                            $value .= '</a>';
                        }

                        echo '<h1 id="' . esc_attr($aria_id) . '">' . $value . '</h1>';
                    }

                    if (!empty($contact) && in_array('organization', $show_fields, true)) {
                        echo '<p class="organisation_name">' . esc_html($contact->getOrganizationName($lang)) . '</p>';
                    }

                    if (!empty($contact) && in_array('jobTitle', $show_fields, true)) {
                        $jobtitleformat = '#functionlabel#';
                        if (!empty($this->config->get('jobtitle_format'))) {
                            $jobtitleformat = $this->config->get('jobtitle_format');
                        }
                        echo '<p class="jobtitle">' . esc_html($contact->getJobTitle($lang, $jobtitleformat)) . '</p>';
                    }
                    ?>
                </header>

                <div class="profile-details">
                    <?php
                    $contactlist = '';

                    if (in_array('email', $show_fields, true)) {
                        $mailadresses = $person->getEMail();
                        $items = [];
                        foreach ($mailadresses as $mail) {
                            if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                                $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a>';
                                $items[] = '<span class="value"><span class="screen-reader-text">' . esc_html__('Email', 'rrze-faudir') . ': </span>' . $formattedValue . '</span>';
                            }
                        }
                        if (!empty($items)) {
                            $contactlist .= '<li class="email listcontent">' . implode('<br>', $items) . '</li>';
                        }
                    }

                    if (in_array('phone', $show_fields, true)) {
                        $items = [];
                        $phonenumbers = $person->getPhone();
                        foreach ($phonenumbers as $phone) {
                            $formattedPhone = FaudirUtils::format_phone_number($phone);
                            $cleanTel = preg_replace('/[^\+\d]/', '', $phone);
                            $formattedValue = '<a itemprop="telephone" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                            $items[] = '<span class="value"><span class="screen-reader-text">' . esc_html__('Phone', 'rrze-faudir') . ': </span>' . $formattedValue . '</span>';
                        }
                        if (!empty($items)) {
                            $contactlist .= '<li class="phone listcontent">' . implode('<br>', $items) . '</li>';
                        }
                    }

                    if (in_array('fax', $show_fields, true) && !empty($workplaces)) {
                        $items = [];
                        foreach ($workplaces as $wdata) {
                            if (!empty($wdata['fax'])) {
                                $formattedPhone = FaudirUtils::format_phone_number($wdata['fax']);
                                $cleanTel = preg_replace('/[^\+\d]/', '', $wdata['fax']);
                                $formattedValue = '<a itemprop="faxNumber" href="tel:' . esc_attr($cleanTel) . '">' . esc_html($formattedPhone) . '</a>';
                                $items[] = '<span class="value"><span class="screen-reader-text">' . esc_html__('Fax', 'rrze-faudir') . ': </span>' . $formattedValue . '</span>';
                            }
                        }
                        if (!empty($items)) {
                            $contactlist .= '<li class="fax listcontent">' . implode('<br>', $items) . '</li>';
                        }
                    }

                    if (in_array('url', $show_fields, true) && !empty($workplaces)) {
                        $items = [];
                        foreach ($workplaces as $wdata) {
                            if (!empty($wdata['url'])) {
                                $displayValue = preg_replace('/^https?:\/\//i', '', $wdata['url']);
                                $formattedValue = '<a href="' . esc_url($wdata['url']) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                                $items[] = '<span class="value"><span class="screen-reader-text">' . esc_html__('URL', 'rrze-faudir') . ': </span>' . $formattedValue . '</span>';
                            }
                        }
                        if (!empty($items)) {
                            $contactlist .= '<li class="url listcontent">' . implode('<br>', $items) . '</li>';
                        }
                    }

                    if (!empty($contactlist)) {
                        echo '<div class="profile-contact icon icon-list">';
                        echo '<h2 class="screen-reader-text">' . esc_html__('Contact', 'rrze-faudir') . '</h2>';
                        echo '<ul class="list-icons">';
                        echo $contactlist;
                        echo '</ul>';
                        echo '</div>';
                    }

                    if (!empty($contact) && in_array('socialmedia', $show_fields, true)) {
                        $some = $contact->getSocialMedia('span');
                        if (!empty($some)) {
                            echo '<div class="profile-socialmedia">';
                            echo '<h2 class="screen-reader-text">' . esc_html__('Social Media and Websites', 'rrze-faudir') . '</h2>';
                            echo $some;
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </section>
            <?php
        }
        ?>
    </div>
    <?php
} else {
    ?>
    <div class="faudir-error"><?php echo esc_html__('No contact entry could be found.', 'rrze-faudir'); ?></div>
    <?php
}
?>
</div>