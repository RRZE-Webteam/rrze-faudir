<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class FaudirUtils {
    const API_BASE_URL = 'https://api.fau.de/pub/v1/opendir/';

    public static function isUsingNetworkKey(): bool  {
        if (is_multisite()) {
            $settingsOptions = get_site_option('rrze_settings');
            if (!empty($settingsOptions->plugins->faudir_public_apiKey)) {
                return true;
            }
        }
        return false;
    }

    public static function getKey()  {
        if (self::isUsingNetworkKey()) {
            $settingsOptions = get_site_option('rrze_settings');
            return $settingsOptions->plugins->faudir_public_apiKey;
        } else {
            $options = get_option('rrze_faudir_options');
            return isset($options['api_key']) ? $options['api_key'] : '';
        }
    }

    public static function getApiBaseUrl() {
        return self::API_BASE_URL;
    }

    

    
    
    public static function getDefaultOutputFields() {
        $options = get_option('rrze_faudir_options');
        $default_output_fields = isset($options['default_output_fields']) ? $options['default_output_fields'] : [];

        $field_mapping = [
            'display_name' => 'displayName',
            'academic_title' => 'personalTitle',
            'first_name' => 'givenName',
            'nobility_title' => 'titleOfNobility',
            'last_name' => 'familyName',
            'academic_suffix' => 'personalTitleSuffix',
            'organization' => 'organization',
            'function' => 'function',
            'url' => 'url'
        ];

        // Map fields from options to internal field names
        $default_show_fields = array_map(function ($field) use ($field_mapping) {
            return isset($field_mapping[$field]) ? $field_mapping[$field] : $field;
        }, $default_output_fields);

        return array_unique($default_show_fields);
    }

    private static function getAcademicTitleLongVersion(string $prefix): string  {
        $prefixes = array(
            '' => __('Not specified', 'rrze-faudir'),
            'Dr.' => __('Doktor', 'rrze-faudir'),
            'Prof.' => __('Professor', 'rrze-faudir'),
            'Prof. Dr.' => __('Professor Doktor', 'rrze-faudir'),
            'Prof. em.' => __('Professor (Emeritus)', 'rrze-faudir'),
            'Prof. Dr. em.' => __('Professor Doktor (Emeritus)', 'rrze-faudir'),
            'PD' => __('Privatdozent', 'rrze-faudir'),
            'PD Dr.' => __('Privatdozent Doktor', 'rrze-faudir')
        );

        return isset($prefixes[$prefix]) ? $prefixes[$prefix] : '';
    }

    public static function getPersonNameHtml($person_data)  {
        $hard_sanitize = $person_data['hard_sanitize'] ?? false;
        $personal_title = $person_data['personal_title'] ?? '';
        $first_name = $person_data['first_name'] ?? '';
        $nobility_title = $person_data['nobility_title'] ?? '';
        $last_name = $person_data['last_name'] ?? '';
        $title_suffix = $person_data['title_suffix'] ?? '';
        $identifier = $person_data['identifier'] ?? '';

        // if all name parts are empty, return an empty string
        if (empty($personal_title) && empty($first_name) && empty($nobility_title) && empty($last_name) && empty($title_suffix)) {
            return '';
        }

        $nameHtml = '';
        $nameHtml .= '<span id="name-' . esc_attr($identifier) . '" itemprop="name">';
        if (!empty($personal_title)) {
            if ($hard_sanitize) {
                $long_version = self::getAcademicTitleLongVersion($personal_title);
                if (!empty($long_version)) {
                    $nameHtml .= '<abbr title="' . esc_attr($long_version) . '" itemprop="honorificPrefix">' . esc_html($personal_title) . '</abbr> ';
                }
            } else {
                $nameHtml .= '<span itemprop="honorificPrefix">' . esc_html($personal_title) . '</span> ';
            }
        }
        if (!empty($first_name)) {
            $nameHtml .= '<span itemprop="givenName">' . esc_html($first_name) . '</span> ';
        }
        if (!empty($nobility_title)) {
            $nameHtml .= '<span>' . esc_html($nobility_title) . '</span> ';
        }
        if (!empty($last_name)) {
            $nameHtml .= '<span itemprop="familyName">' . esc_html($last_name) . '</span> ';
        }
        if (!empty($title_suffix)) {
            $nameHtml .= '(<span itemprop="honorificSuffix">' . esc_html($title_suffix) . '</span>)';
        }
        $nameHtml .= '</span>';
        return $nameHtml;
    }

    public static function getWeekday($weekday)  {
        $weekdayMap = [
            0 => __('Sunday','rrze-faudir'),
            1 => __('Monday','rrze-faudir'),
            2 => __('Tuesday','rrze-faudir'),
            3 => __('Wednesday','rrze-faudir'),
            4 => __('Thursday','rrze-faudir'),
            5 => __('Friday','rrze-faudir'),
            6 => __('Saturday','rrze-faudir'),
        ];
        return $weekdayMap[$weekday] ?? __('Unknown','rrze-faudir');
    }

    public static function filterContactsByCriteria($contacts, $includeDefaultOrg, $defaultOrgIds, $email)  {
        foreach ($contacts as $contactKey => $contact) {
            $shouldRemove = false;

            // Check organization if includeDefaultOrg is true
            if ($includeDefaultOrg && !in_array($contact['organization']['identifier'], $defaultOrgIds)) {
                $shouldRemove = true;
            }

            // Check email only if an email search parameter was provided
            if (!empty($email)) {
                $contactEmails = $contact['mails'] ?? [];
                if (empty($contactEmails) || !in_array($email, $contactEmails)) {
                    $shouldRemove = true;
                }
            }

            if ($shouldRemove) {
                unset($contacts[$contactKey]);
            }
        }

        return $contacts;
    }
    
    /* 
     * Get a data list from typ name, value which gets displayed as list.
     * Returns HTML for output
     */
    public static function getListOutput(array $data, string $htmlsurround = 'div', string $label = '', string $class = '',  array $show = [], array $hide = [], array $reihenfolge = []): string {
        $output = '';
        if ((!is_array($data)) || (empty($data))) {
            return $output;
        }
        $data = self::flatten_data_array($data);     
        $htmlsurround = self::sanitize_htmlsurround($htmlsurround);
        
        
        $output .= '<'.$htmlsurround;
        if (!empty($label)) {
             $output .= ' aria-label="'.trim(esc_attr($label)).'"';
        }
        if (!empty($class)) {
             $output .= ' class="'.trim(esc_attr($class)).'"';
        }
        $output .= '>';
        $output .= '<ul>';
        
        // Prüfen, ob Zip, City und Street im $data enthalten sind
        $address = '';
        $roompos = '';     
        if (isset($data['zip']) || isset($data['city']) || isset($data['street'])) {
            if (!empty($hide) && (in_array('zip', $hide, true) || in_array('city', $hide, true) || in_array('street', $hide, true))) {
                // Wenn eines der Felder in $hide ist, Adresse nicht anzeigen
                unset($data['zip'], $data['city'], $data['street']);
            } else {
                // Adresse zusammenfügen
            
                $address .= isset($data['street']) ? '<span itemprop="streetAdress">'.esc_html($data['street']).'</span>' : '';
                if (isset($data['street']) && (isset($data['zip']) || isset($data['city']))) {
                    $address .= ', ';
                }
                
                $address .= isset($data['zip']) ?  '<span itemprop="postalCode">'.esc_html($data['zip']).'</span> ' : '';
                $address .= isset($data['city']) ? '<span itemprop="addressLocality">'.esc_html($data['city']).'</span>' : '';
                if (!empty($address)) {
                    $address = '<span class="texticon address" itemprop="address" itemscope="" itemtype="https://schmea.org/PostalAddress">'.$address.'</span>';                  
                    $address = '<li><span class="screen-reader-text">'.__('Address', 'rrze-faudir').': </span>' . $address . '</li>';
                }

                // Zip, City und Street aus der normalen Verarbeitung entfernen
                unset($data['zip'], $data['city'], $data['street']);
                $data['address'] = $address;
            }
        }
        
        if (isset($data['room']) || isset($data['floor'])) {
            if (!empty($hide) && (in_array('room', $hide, true) || in_array('floor', $hide, true) )) {
                // Wenn eines der Felder in $hide ist, Raum und Stockwerk zusammensetzen
                unset($data['room'], $data['floor']);
            } else {
                // Raum und Stockwerk zusammenfügen
                if (!empty($show) && in_array('floor', $show, true)) {
                    $roompos .= isset($data['floor']) ? esc_html($data['floor']).'. '.__('Floor','rrze-faudir') : '';
                    if (isset($data['room']) && (isset($data['floor']))) {
                        $roompos .= ', ';
                    }
                }
                if (!empty($show) && in_array('room', $show, true)) {
                    $roompos .= isset($data['room']) ?  __('Room','rrze-faudir').' '.esc_html($data['room']) : '';
                }
                if (!empty($roompos)) {
                    $roompos = '<li><span class="screen-reader-text">'.__('Bureau', 'rrze-faudir').': </span><span class="texticon room">' . $roompos . '</span></li>';
                }

                // Zip, City und Street aus der normalen Verarbeitung entfernen
                unset($data['room'], $data['floor']);
                $data['roompos'] = $roompos;
            }
        }
        
        
        
         // Falls eine spezifische Reihenfolge gegeben ist, sortiere die vorhandenen Felder zuerst
        if (!empty($reihenfolge)) {
            $priorisierteKeys = array_intersect($reihenfolge, array_keys($data)); // Nur vorhandene Felder aus Reihenfolge
            $restlicheKeys = array_diff(array_keys($data), $priorisierteKeys); // Alle restlichen Felder
            $sortierteKeys = array_merge($priorisierteKeys, $restlicheKeys); // Kombinierte Reihenfolge

            // Sortiertes Array erzeugen
            $data = array_merge(array_flip($sortierteKeys), $data);
        }
        
    
   //     $output .= Debug::get_html_var_dump($data);
        foreach ($data as $name => $value) {
            // Falls es sich um ein Unterarray "phones" handelt, das mehrere Nummern enthält
             if ($name === 'phones' && is_array($value)) {
                if (!empty($hide) && in_array('phone', $hide, true)) {
                    continue;
                }
                if (!empty($show) && !in_array('phone', $show, true)) {
                    continue;
                }

                // Mehrere Telefonnummern -> Ausgabe als Unterliste
                if (count($value) > 1) {
                    $output .= '<li><span class="screen-reader-text">'.__('Phone Numbers','rrze-faudir').':</span><ul class="phonelist">';
                    foreach ($value as $phone) {
                        if (preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $phone)) {
                            $formattedPhone = self::format_phone_number($phone);
                            $telLink = preg_replace('/\s+/', '', $formattedPhone);
                            $output .= '<li><a itemprop="telephone" href="tel:' . esc_attr($telLink) . '">' . esc_html($formattedPhone) . '</a></li>';
                        } else {
                            $output .= '<li>' . esc_html($phone) . '</li>';
                        }
                    }
                    $output .= '</ul></li>';
                }
                // Genau eine Telefonnummer -> Direkte Ausgabe ohne Unterliste
                elseif (count($value) === 1) {
                    $phone = reset($value); // Erstes Element des Arrays holen
                    if (preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $phone)) {
                        $formattedPhone = self::format_phone_number($phone);
                        $telLink = preg_replace('/\s+/', '', $formattedPhone);
                        $output .= '<li><span class="screen-reader-text">'.__('Phone','rrze-faudir').': </span><a itemprop="telephone" href="tel:' . esc_attr($telLink) . '">' . esc_html($formattedPhone) . '</a></li>';
                    }
                }
                continue;
            } 
            // Falls es sich um ein Unterarray "phones" handelt, das mehrere Nummern enthält
            if ($name === 'mails' && is_array($value)) {
                if (!empty($hide) && in_array('email', $hide, true)) {
                    continue;
                }
                if (!empty($show) && !in_array('email', $show, true)) {
                    continue;
                }

                // Mehrere Mailadressen -> Ausgabe als Unterliste
                if (count($value) > 1) {
                    $output .= '<li><span class="screen-reader-text">'.__('Email','rrze-faudir').':</span><ul class="maillist">';
                    foreach ($value as $mail) {
                        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                            $output .= '<li><a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a></li>';
                        }
                    }
                    $output .= '</ul></li>';
                }
                // Genau eine Mailadresse -> Direkte Ausgabe ohne Unterliste
                elseif (count($value) === 1) {
                    $mail = reset($value); // Erstes Element des Arrays holen
                    if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                        $output .= '<li><span class="screen-reader-text">'.__('Email','rrze-faudir').':</span><a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a></li>';
                    }
                }
                continue;
            } 
            if ($name === 'address') {
                $output .= $value;
                continue;
            }
            if ($name === 'roompos') {
                $output .= $value;
                continue;
            }
            
             // Wenn $hide gesetzt ist und der Key existiert, wird der Eintrag übersprungen
            if (!empty($hide) && in_array($name, $hide, true)) {
                continue;
            }
            // Wenn $show gesetzt ist und der Key NICHT enthalten ist, wird der Eintrag übersprungen
            if (!empty($show) && !in_array($name, $show, true)) {
                continue;
            }
            
            
            // Falls $value eine FAUMap-Adresse ist
            if (preg_match('/^https?:\/\/karte\.fau\.de/i', $value)) {
                $displayValue = __('Map','rrze-faudir');
                $formattedValue = '<a href="' . esc_url($value) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                
                $output .= '<li><span class="website title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</li>';
                continue;
            }
            
            // Falls $value eine URL ist (http oder https)
            elseif (preg_match('/^https?:\/\//i', $value)) {
                $displayValue = preg_replace('/^https?:\/\//i', '', $value);
                $formattedValue = '<a href="' . esc_url($value) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                
                $output .= '<li><span class="website title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</li>';
                continue;
            }
            // Falls $value eine E-Mail-Adresse ist
            elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
                $output .= '<li class="link"><span class="title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</li>';
                continue;
            }
            // Falls $value eine Telefonnummer ist
            elseif (preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $value)) {
                $formattedPhone = self::format_phone_number($value); // Formatierte Nummer
                $telLink = preg_replace('/\s+/', '', $formattedPhone); // Entferne Leerzeichen für den `tel:`-Link
                $formattedValue = '<a itemprop="telephone" href="tel:' . esc_attr($telLink) . '">' . esc_html($formattedPhone) . '</a>';
                $output .= '<li><span class="title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</li>';
                continue;
                        
            
                
            } else {
                $formattedValue = '<span class="value">'. esc_html($value). '</span>';
            }

            // Ausgabe in die Liste
            $output .= '<li><span class="title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</li>';
        }
        
        $output .= '</ul>';
        $output .= '</'.$htmlsurround.'>';
        
    
        return $output;
    }


    /* 
     * Get a Cell-List for Table-Content from typ name, value 
     * Returns HTML for output
     */
    public static function getTableCellOutput(array $data, array $show = [], array $hide = [], array $reihenfolge = []): string {
        $output = '';
        if ((!is_array($data)) || (empty($data))) {
            return $output;
        }
        $data = self::flatten_data_array($data);           
       
        
        // Prüfen, ob Zip, City und Street im $data enthalten sind
        $address = '';
        $roompos = '';     
        if (isset($data['zip']) || isset($data['city']) || isset($data['street'])) {
            if (!empty($hide) && (in_array('zip', $hide, true) || in_array('city', $hide, true) || in_array('street', $hide, true))) {
                // Wenn eines der Felder in $hide ist, Adresse nicht anzeigen
                unset($data['zip'], $data['city'], $data['street']);
            } else {
                // Adresse zusammenfügen
            
                $address .= isset($data['street']) ? '<span itemprop="streetAdress">'.esc_html($data['street']).'</span>' : '';
                if (isset($data['street']) && (isset($data['zip']) || isset($data['city']))) {
                    $address .= ', ';
                }
                
                $address .= isset($data['zip']) ?  '<span itemprop="postalCode">'.esc_html($data['zip']).'</span> ' : '';
                $address .= isset($data['city']) ? '<span itemprop="addressLocality">'.esc_html($data['city']).'</span>' : '';
                if (!empty($address)) {
                    $address = '<span class="texticon address" itemprop="address" itemscope="" itemtype="https://schmea.org/PostalAddress">'.$address.'</span>';                  
                    $address = '<td><span class="screen-reader-text">'.__('Address', 'rrze-faudir').': </span>' . $address . '</td>';
                }

                // Zip, City und Street aus der normalen Verarbeitung entfernen
                unset($data['zip'], $data['city'], $data['street']);
                $data['address'] = '<td>'.$address.'</td>';
            }
        }
        
        if (isset($data['room']) || isset($data['floor'])) {
            if (!empty($hide) && (in_array('room', $hide, true) || in_array('floor', $hide, true) )) {
                // Wenn eines der Felder in $hide ist, Raum und Stockwerk zusammensetzen
                unset($data['room'], $data['floor']);
            } else {
                // Raum und Stockwerk zusammenfügen
                if (!empty($show) && in_array('floor', $show, true)) {
                    $roompos .= isset($data['floor']) ? esc_html($data['floor']).'. '.__('Floor','rrze-faudir') : '';
                    if (isset($data['room']) && (isset($data['floor']))) {
                        $roompos .= ', ';
                    }
                }
                if (!empty($show) && in_array('room', $show, true)) {
                    $roompos .= isset($data['room']) ?  __('Room','rrze-faudir').' '.esc_html($data['room']) : '';
                }
                if (!empty($roompos)) {
                    $roompos = '<span class="screen-reader-text">'.__('Bureau', 'rrze-faudir').': </span><span class="texticon room">' . $roompos . '</span>';
                }

                // Zip, City und Street aus der normalen Verarbeitung entfernen
                unset($data['room'], $data['floor']);
                $data['roompos'] = '<td>'.$roompos.'</td>';
            }
        }
        
        
        
         // Falls eine spezifische Reihenfolge gegeben ist, sortiere die vorhandenen Felder zuerst
        if (!empty($reihenfolge)) {
            $priorisierteKeys = array_intersect($reihenfolge, array_keys($data)); // Nur vorhandene Felder aus Reihenfolge
            $restlicheKeys = array_diff(array_keys($data), $priorisierteKeys); // Alle restlichen Felder
            $sortierteKeys = array_merge($priorisierteKeys, $restlicheKeys); // Kombinierte Reihenfolge

            // Sortiertes Array erzeugen
            $data = array_merge(array_flip($sortierteKeys), $data);
        }
        
    
   //     $output .= Debug::get_html_var_dump($data);
        foreach ($data as $name => $value) {
            // Falls es sich um ein Unterarray "phones" handelt, das mehrere Nummern enthält
             if ($name === 'phones' && is_array($value)) {
                if (!empty($hide) && in_array('phone', $hide, true)) {
                    continue;
                }
                if (!empty($show) && !in_array('phone', $show, true)) {
                    continue;
                }

                // Mehrere Telefonnummern -> Ausgabe als Unterliste
                if (count($value) > 1) {
                    $output .= '<td><span class="screen-reader-text">'.__('Phone Numbers','rrze-faudir').':</span><ul class="phonelist">';
                    foreach ($value as $phone) {
                        if (preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $phone)) {
                            $formattedPhone = self::format_phone_number($phone);
                            $telLink = preg_replace('/\s+/', '', $formattedPhone);
                            $output .= '<li><a itemprop="telephone" href="tel:' . esc_attr($telLink) . '">' . esc_html($formattedPhone) . '</a></li>';
                        } else {
                            $output .= '<li>' . esc_html($phone) . '</li>';
                        }
                    }
                    $output .= '</ul></td>';
                }
                // Genau eine Telefonnummer -> Direkte Ausgabe ohne Unterliste
                elseif (count($value) === 1) {
                    $phone = reset($value); // Erstes Element des Arrays holen
                    if (preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $phone)) {
                        $formattedPhone = self::format_phone_number($phone);
                        $telLink = preg_replace('/\s+/', '', $formattedPhone);
                        $output .= '<td><span class="screen-reader-text">'.__('Phone','rrze-faudir').': </span><a itemprop="telephone" href="tel:' . esc_attr($telLink) . '">' . esc_html($formattedPhone) . '</a></td>';
                    }
                } else {
                     $output .= '<td></td>';
                }
                continue;
            } 
            // Falls es sich um ein Unterarray "phones" handelt, das mehrere Nummern enthält
            if ($name === 'mails' && is_array($value)) {
                if (!empty($hide) && in_array('email', $hide, true)) {
                    continue;
                }
                if (!empty($show) && !in_array('email', $show, true)) {
                    continue;
                }

                // Mehrere Mailadressen -> Ausgabe als Unterliste
                if (count($value) > 1) {
                    $output .= '<td><span class="screen-reader-text">'.__('Email','rrze-faudir').':</span><ul class="maillist">';
                    foreach ($value as $mail) {
                        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                            $output .= '<li><a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a></li>';
                        }
                    }
                    $output .= '</ul></td>';
                }
                // Genau eine Mailadresse -> Direkte Ausgabe ohne Unterliste
                elseif (count($value) === 1) {
                    $mail = reset($value); // Erstes Element des Arrays holen
                    if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                        $output .= '<td><span class="screen-reader-text">'.__('Email','rrze-faudir').':</span><a itemprop="email" href="mailto:' . esc_attr($mail) . '">' . esc_html($mail) . '</a></td>';
                    }
                } else {
                     $output .= '<td></td>';
                }
                continue;
            } 
            if ($name === 'address') {
                $output .= $value;
                continue;
            }
            if ($name === 'roompos') {
                $output .= $value;
                continue;
            }
            
             // Wenn $hide gesetzt ist und der Key existiert, wird der Eintrag übersprungen
            if (!empty($hide) && in_array($name, $hide, true)) {
                continue;
            }
            // Wenn $show gesetzt ist und der Key NICHT enthalten ist, wird der Eintrag übersprungen
            if (!empty($show) && !in_array($name, $show, true)) {
                continue;
            }
            
            
            // Falls $value eine FAUMap-Adresse ist
            if (preg_match('/^https?:\/\/karte\.fau\.de/i', $value)) {
                $displayValue = __('Map','rrze-faudir');
                $formattedValue = '<a href="' . esc_url($value) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                
                $output .= '<td><span class="website title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</td>';
            }
            
            // Falls $value eine URL ist (http oder https)
            elseif (preg_match('/^https?:\/\//i', $value)) {
                $displayValue = preg_replace('/^https?:\/\//i', '', $value);
                $formattedValue = '<a href="' . esc_url($value) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                
                $output .= '<td><span class="website title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</td>';
            }
            // Falls $value eine E-Mail-Adresse ist
            elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
                $output .= '<td class="link"><span class="title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</td>';
            }
            // Falls $value eine Telefonnummer ist
            elseif (preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $value)) {
                $formattedPhone = self::format_phone_number($value); // Formatierte Nummer
                $telLink = preg_replace('/\s+/', '', $formattedPhone); // Entferne Leerzeichen für den `tel:`-Link
                $formattedValue = '<a itemprop="telephone" href="tel:' . esc_attr($telLink) . '">' . esc_html($formattedPhone) . '</a>';
                $output .= '<td><span class="title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</td>';                       
            
               
            } else {
                $formattedValue = '<span class="value">'. esc_html($value). '</span>';
            }

            // Ausgabe in die Liste
            $output .= '<td><span class="title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</td>';
        }
        
 
        
    
        return $output;
    }

    
    /*
     * Flatten Array in case its an Subarray
     */
    public static function flatten_data_array(array $data): array {
        if (count($data) === 1 && is_array(reset($data))) {
            return reset($data); 
        }

        return $data; 
    }

    // Sanitize and format telephone number
    public static function format_phone_number(string $phone): string {
        // Entferne alle Zeichen außer Zahlen, "+", "(", ")", "-" und Leerzeichen
        $phone = preg_replace('/[^\d\+\-\(\) ]/', '', $phone);
        $phone = preg_replace('/\s+/', ' ', trim($phone));

        // Falls die Nummer mit "+49(0)" beginnt → zu "+49" umwandeln
        $phone = preg_replace('/^\+49\s*\(0\)/', '+49', $phone);
        $phone = preg_replace('/^0049/', '+49', $phone);

        // Falls die Nummer mit "0" beginnt (deutsche Nummer ohne Ländercode)
        if (preg_match('/^0[1-9]/', $phone)) {
            $phone = preg_replace('/^0/', '+49 ', $phone);
        }

        // Standardisiere das Format mit Leerzeichen zwischen Gruppen
        $phone = preg_replace('/(\+?\d{1,3})\s*(\d{3,4})\s*(\d{3,4})\s*(\d{0,4})/', '$1 $2 $3 $4', $phone);

        return trim($phone); // Entfernt überflüssige Leerzeichen am Ende
    }
    
   

    // Sanitize allowed HTML Tag for list outputs
    public static function sanitize_htmlsurround(string $htmlsurround): string {
        $allowed_tags = ['div', 'span', 'nav', 'p']; // Erlaubte Tags
        $htmlsurround = strtolower(trim($htmlsurround)); // Kleinschreibung und Leerzeichen entfernen

        return in_array($htmlsurround, $allowed_tags, true) ? $htmlsurround : 'div';
    }

    
}
