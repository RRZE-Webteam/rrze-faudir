<?php

/**
 * Contact Class
 *
 * @author unrz59
 */

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Contact {
    public string $identifier = '';
    public array $person = [];
    public array $organization = [];
    public string $givenName = '';
    public string $familyName = '';
    public ?string $titleOfNobility = '';
    public ?string $function = '';
    public ?string $jobTitle = '';
    public ?array $functionLabel = [];
    public ?array $workplaces = [];
    public ?array $socials = [];
    public ?array $org = [];
    private array $rawdata;
    /**
     * Contact constructor
     */
    public function __construct(array $data = []) {

        if (isset($data['identifier']) && is_string($data['identifier'])) {
            $this->identifier = $data['identifier'];
        }
        if (isset($data['person']) && is_array($data['person'])) {
            $this->person = $data['person'];
        }
        if (isset($data['organization']) && is_array($data['organization'])) {
            $this->organization = $data['organization'];
        }
        if (isset($data['givenName']) && is_string($data['givenName'])) {
            $this->givenName = $data['givenName'];
        }
        if (isset($data['familyName']) && is_string($data['familyName'])) {
            $this->familyName = $data['familyName'];
        }
        if (isset($data['titleOfNobility']) && is_string($data['titleOfNobility'])) {
            $this->titleOfNobility = $data['titleOfNobility'];
        }
        if (isset($data['jobTitle']) && is_string($data['jobTitle'])) {
            $this->jobTitle = $data['jobTitle'];
        }
        if (isset($data['function']) && is_string($data['function'])) {
            $this->function = $data['function'];
        }
        if (isset($data['functionLabel']) && is_array($data['functionLabel'])) {
            $this->functionLabel = $data['functionLabel'];
        }
        if (isset($data['workplaces']) && is_array($data['workplaces'])) {
            $this->workplaces = $data['workplaces'];
        }
        if (isset($data['org']) && is_array($data['org'])) {
            $this->org = $data['org'];
        }
        if (isset($data['socials']) && is_array($data['socials'])) {
            $this->socials = $data['socials'];
        }
        
           // Aktualisiere rawdata: Füge alle übrigen Schlüssel hinzu, die nicht zu den Standardfeldern gehören.
        $usedKeys = [     
            'identifier',
            'person',
            'organization',
            'givenName',
            'familyName',
            'titleOfNobility',
            'jobTitle',
            'function',
            'functionLabel',
            'workplaces',
            'org',
            'socials'
        ];
        $remaining = array_diff_key($data, array_flip($usedKeys));
        $this->rawdata = $remaining;
    }
     /**
     * Aktualisiert die Eigenschaften der Person anhand der übergebenen Daten.
     * @param array $data Das Array mit den neuen Personendaten.
     */
    public function populateFromData(array $data, bool $clear = true): void {
        
        if ($clear) {
            // Setze alle bekannten Felder auf ihre Standardwerte zurück.
            $this->identifier       = '';
            $this->person           = [];
            $this->organization     = [];
            $this->givenName        = '';
            $this->familyName       = '';
            $this->titleOfNobility  = '';
            $this->jobTitle         = '';
            $this->function         = '';
            $this->functionLabel    = [];
            $this->workplaces       = [];
            $this->org              = [];
            $this->socials          = [];

            // Leere rawdata zurücksetzen
            $this->rawdata = [];
        }
        
        // Aktualisiere die einzelnen Eigenschaften, falls Werte vorhanden sind
         if (isset($data['identifier']) && is_string($data['identifier'])) {
            $this->identifier = $data['identifier'];
        }
        if (isset($data['person']) && is_array($data['person'])) {
            $this->person = $data['person'];
        }
        if (isset($data['organization']) && is_array($data['organization'])) {
            $this->organization = $data['organization'];
        }
        if (isset($data['givenName']) && is_string($data['givenName'])) {
            $this->givenName = $data['givenName'];
        }
        if (isset($data['familyName']) && is_string($data['familyName'])) {
            $this->familyName = $data['familyName'];
        }
        if (isset($data['function']) && is_string($data['function'])) {
            $this->function = $data['function'];
        }
        if (isset($data['titleOfNobility']) && is_string($data['titleOfNobility'])) {
            $this->titleOfNobility = $data['titleOfNobility'];
        }
        if (isset($data['jobTitle']) && is_string($data['jobTitle'])) {
            $this->jobTitle = $data['jobTitle'];
        }
        if (isset($data['functionLabel']) && is_array($data['functionLabel'])) {
            $this->functionLabel = $data['functionLabel'];
        }
        if (isset($data['workplaces']) && is_array($data['workplaces'])) {
            $this->workplaces = $data['workplaces'];
        }
        if (isset($data['org']) && is_array($data['org'])) {
            $this->organization_address = $data['org'];
        }
        if (isset($data['socials']) && is_array($data['socials'])) {
            $this->socials = $data['socials'];
        }

        // Aktualisiere rawdata: Füge alle übrigen Schlüssel hinzu, die nicht zu den Standardfeldern gehören.
        $usedKeys = [
           'identifier',
            'person',
            'organization',
            'givenName',
            'familyName',
            'titleOfNobility',
            'jobTitle',
            'function',
            'functionLabel',
            'workplaces',
            'org',
            'socials'
        ];
        $remaining = array_diff_key($data, array_flip($usedKeys));
        $this->rawdata = array_merge($this->rawdata, $remaining);
    }
    
      /*
     * Contactdaten als Array zurückliefern
     */
    public function toArray(): array {
        $data = [
            'identifier'        => $this->identifier,
            'person'            => $this->person,
            'organization'      => $this->organization,
            'givenName'         => $this->givenName,
            'familyName'        => $this->familyName,
            'titleOfNobility'   => $this->titleOfNobility,
            'function'          => $this->function,
            'jobTitle'          => $this->jobTitle,
            'functionLabel'     => $this->functionLabel,
            'workplaces'        => $this->workplaces,
            'org'               => $this->org,
            'socials'           => $this->socials,
        ];

        // Füge alle restlichen Schlüssel und Werte (rawdata) hinzu,
        // so dass das ursprüngliche Array wiederhergestellt wird.
        return array_merge($data, $this->rawdata);
    }
    
    /*
     * Get workplaces and return as Array if exists
     */  
    public function getWorkplaces(): ?array {
        if (empty($this->workplaces)) {
            return null;
        }
        return $this->workplaces;   
    }
    
    /*
     * Get Orgname
     */
    public function getOrganizationName(string $lang = "de"): ?string {
        $res = '';
        if  (!empty($this->organization)) {
            if ((isset($this->organization['longDescription'])) && (isset($this->organization['longDescription'][$lang]))) {
                $res = '<span class="organization" itemprop="name">'.esc_html($this->organization['longDescription'][$lang]).'</span>';    
            } elseif (isset($this->organization['longDescription'])) {
                // Org in searched lang nocht avaible, using the other one
                if ($lang=== 'de') {
                    $res = '<span class="organization" itemprop="name">'.esc_html($this->organization['longDescription']['en']).'</span>';    
                } else {
                    $res = '<span class="organization" itemprop="name">'.esc_html($this->organization['longDescription']['de']).'</span>';    
                }
            }
        }
        return $res;
    }
    
    /*
     * Get ConsultationHours by Workplace and return in semantic HTML
     * with key = 'consultationHours' as parameter
     *   key = 'officeHours' will use this array instead
     */    
    public function getConsultationsHours(array $workplace, string $key = 'consultationHours', bool $withaddress = true, string $lang = 'de', bool $roomfloor = false, bool $showmap = false): string {
        if ((empty($workplace)) || (empty($workplace[$key]))) {
            return '';
        } 
        
        $output = '';
        $output .= '<div class="workplace-hours" itemprop="contactPoint" itemscope itemtype="https://schema.org/ContactPoint">';
        $output .= '<strong itemprop="contactType">';
        if ($key === 'officeHours') {
            $output .= esc_html__('Office Hours', 'rrze-faudir');
        } else {
            $output .= esc_html__('Consultation Hours', 'rrze-faudir');
        }
        $output .= ':</strong>';
        
        
        $num = count($workplace[$key]);
        if ($num > 1) {
            $output .= '<ul class="ContactPointList">';
        }
        foreach ($workplace[$key] as $consultationHours) {
            if ($num > 1) {
                 $output .= '<li>';
            }
            
            $output .= '<div class="hoursAvailable" itemprop="hoursAvailable" itemscope itemtype="https://schema.org/OpeningHoursSpecification">';
            $output .= '<span class="weekday" itemprop="dayOfWeek" content="https://schema.org/';
            $output .= esc_attr(self::getWeekdaySpec($consultationHours['weekday']));
            $output .= '">';
            $output .= esc_html(self::getWeekday($consultationHours['weekday'])).': ';
            $output .= '<span itemprop="opens">'.esc_html($consultationHours['from']).'</span> - ';
            $output .= '<span itemprop="close">'.esc_html($consultationHours['to']).'</span>';
            $output .= '</span>';             
            if (!empty($consultationHours['comment'])) {
                $output .= '<p class="comment" itemprop="description">'.esc_html($consultationHours['comment']).'</p>';
            }
            if (!empty($consultationHours['url'])) {
                $output .= '<p class="url" itemprop="url"><a href="'.esc_url($consultationHours['url']).'">'.esc_html($consultationHours['url']).'</a></p>';
            }
            $output .= '</div>';
            if ($num > 1) {
                $output .= '</li>';
            }
        }
        
        if ($num > 1) {
            $output .= '</ul>';
        }
        
        if ($withaddress) {
            
            if (!empty($workplace['room'])) {
                $output .= '';
            } 
            $addressdata = $this->getAddressByWorkplace($workplace, false, $lang, $roomfloor, $showmap);
            $output .= $addressdata;
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /*
    * Get Weekday
    */
    private static function getWeekday($weekday): string {
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
    private static function getWeekdaySpec($weekday): string {
        $weekdayMap = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        return $weekdayMap[$weekday] ?? 'Unknown';
    }
    
    /*
     * Generate Address Output for a Workplace
     */
    public function getAddressByWorkplace(array $workplace, bool $orgname = true, string $lang = "de", bool $roomfloor = false, bool $showmap = false): ?string {
        $address = $result = '';

        if ($orgname) {
            $address .= $this->getOrganizationName($lang);  
        }
        if (($roomfloor) || ($showmap)) {
            
            if ((!empty($workplace['room'])) ||  (!empty($workplace['floor'])) || (!empty($workplace['faumap']))) {
                $room = $floor = $map = '';        
                if (($roomfloor) && (!empty($workplace['room']))) {
                     $room = '<span class="room">'.__('Room', 'rrze-faudir').': <span class="room" itemprop="roomNumber">'.$workplace['room'].'</span></span>';           
                }
                if (($roomfloor) && (!empty($workplace['floor']))) {
                    $floor = '<span class="floor">'.__('Floor', 'rrze-faudir').': <span class="floor" itemprop="floorLevel">'.$workplace['floor'].'</span></span>';
                }
                if (($showmap) && (!empty($workplace['faumap']))) {
                    if (preg_match('/^https?:\/\/karte\.fau\.de/i', $workplace['faumap'])) {  
                        $formattedValue = '<a href="' . esc_url($workplace['faumap']) . '" itemprop="hasMap" content="' . esc_url($workplace['faumap']) . '">' .__('FAU Map','rrze-faudir'). '</a>';
                        $map = '<span class="faumap">'.__('Map','rrze-faudir').': '.$formattedValue.'</span>';
                    }
                }
                
                $address .= '<span class="roomfloor" itemprop="containedInPlace" itemscope itemtype="https://schema.org/Room">';

                if (!empty($room)) {
                    $address .= $room;
                    if ((!empty($floor)) || (!empty($map))) {
                        $address .= ', ';
                    }
                }
                if (!empty($floor)) {
                    $address .= $floor;
                    if (!empty($map)) {
                        $address .= ', ';
                    }
                }
                if (!empty($map)) {
                    $address .= $map;
                }

                $address .= '</span>'; 
            }

           
        }
        
        if ($workplace['street']) {
            $address .= '<span class="street" itemprop="streetAdress">'.esc_html($workplace['street']).'</span>';    
        }
        if ($workplace['postOfficeBoxNumber']) {
            $address .= '<span class="postbox"><span class="screen-reader-text">'.__('Box Number', 'rrze-faudir').': </span><span itemprop="postOfficeBoxNumber">'.esc_html($workplace['postOfficeBoxNumber']).'</span></span>';    
        }
        
        if (($workplace['postalCode']) && ($workplace['addressLocality'] || $workplace['city'])) {
            $address .= '<span class="zipcity">';
        }
            if ($workplace['postalCode']) {
                $address .= '<span class="postalCode" itemprop="postalCode">'.esc_html($workplace['postalCode']).'</span> ';    
            } elseif ($workplace['zip']) {
                $address .= '<span class="postalCode" itemprop="postalCode">'.esc_html($workplace['zip']).'</span> ';    
            }
            if ($workplace['addressLocality']) {
                $address .= '<span class="addressLocality" itemprop="addressLocality">'.esc_html($workplace['addressLocality']).'</span>';    
            } elseif ($workplace['city']) {
                $address .= '<span class="addressLocality" itemprop="addressLocality">'.esc_html($workplace['city']).'</span>';    
            }
        if (($workplace['postalCode']) && ($workplace['addressLocality'] || $workplace['city'])) {    
            $address .= '</span>';
        }
        if ($workplace['addressCountry']) {
            $address .= '<span class="addressCountry" itemprop="addressCountry">'.esc_html($workplace['addressCountry']).'</span>';    
        }
        
        
        if (!empty($address)) {
            $address = '<span class="texticon" itemprop="address" itemscope="" itemtype="https://schema.org/PostalAddress">'.$address.'</span>';                  
            $result = '<div class="workplace-address"><span class="screen-reader-text">'.__('Address', 'rrze-faudir').': </span>' . $address . '</div>';
        }
        return $result;
    }
    
    
    /*
     * Get Social Website Liste as semantic HTML
     */
    public function getSocialMedia(string $htmlsurround = 'div', string $class = 'icon-list icon', string $arialabel = ''): string {
        $data = $this->getSocialArray();
        if (empty($data)) {
            return '';
        }
        if (empty($arialabel)) {
            $arialabel = __('Social Media and Websites', 'rrze-faudir');
        }
        
        
        $htmlsurround = self::sanitize_htmlsurround($htmlsurround);
        
        
        $output .= '<'.$htmlsurround;
        if (!empty($arialabel)) {
             $output .= ' aria-label="'.trim(esc_attr($arialabel)).'"';
        }
        if (!empty($class)) {
             $output .= ' class="'.trim(esc_attr($class)).'"';
        }
        $output .= '>';
        $output .= '<ul>';
        foreach ($data as $name => $value) {
            if (preg_match('/^https?:\/\//i', $value)) {
                $displayValue = preg_replace('/^https?:\/\//i', '', $value);
                $formattedValue = '<a href="' . esc_url($value) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                $output .= '<li><span class="website title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</li>';
            } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
                $output .= '<li><span class="email title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</li>';
            } else {
                $formattedValue = '<span class="value">'. esc_html($value). '</span>';
                $output .= '<li><span class="title">'.ucfirst(esc_html($name)).': </span>'.$formattedValue.'</li>';                        
            }
        }
        
        $output .= '</'.$htmlsurround.'>';
        return $output;
        
    }
    /*
     * Sanitize allowed HTML Tag for list outputs
     */
    private static function sanitize_htmlsurround(string $htmlsurround): string {
        $allowed_tags = ['div', 'span', 'nav', 'p']; // Erlaubte Tags
        $htmlsurround = strtolower(trim($htmlsurround)); // Kleinschreibung und Leerzeichen entfernen

        return in_array($htmlsurround, $allowed_tags, true) ? $htmlsurround : 'div';
    }
    /*
     * Get Social/Website from Contact and transform them into a assoc. array
     */
    public function getSocialArray(): ?array {
        if (empty($this->socials)) {
            return [];
        }

        $reslist = [];
        foreach ($this->socials as $item) {
            if (isset($item['platform']) && isset($item['url'])) {
                $reslist[$item['platform']] = $item['url'];
            }
        }
        return $reslist;
    }
    
    
    /*
     * Get the FunctionLabel
     */
    public function getFunctionLabel(string $lang = "de"): ?string {
        if (empty($this->functionLabel)) {
            return '';
        }
        if ((!empty($lang)) && (isset($this->functionLabel[$lang]))) {
            return $this->functionLabel[$lang];
        }
        
        if (!isset($this->functionLabel[$lang])) {
            if (($lang === "de") && ($this->functionLabel["en"])) {
                return $this->functionLabel["en"];
            } elseif (($lang === "en") && ($this->functionLabel["de"])) {
                return $this->functionLabel["en"];
            }
            return '';
        }
    }
    
    
    /*
     * Build JobTitle by Functionlabel and Orgname
     */
    public function getJobTitle(string $lang = "de"): ?string {
        $label = $this->getFunctionLabel($lang);
        
        if (empty($label)) {
            return '';
        }
        

        if (empty($this->organization) || !isset($this->organization['longDescription'])) {
            return $label;
        }
    
        $jobtitle = $label;
        $orgname = '';
        if ((!empty($lang)) && (isset($this->organization['longDescription'][$lang]))) {
            $orgname = $this->organization['longDescription'][$lang];
        }
        
         if (!empty($lang) && !empty($this->organization['longDescription'][$lang])) {
            $orgname = $this->organization['longDescription'][$lang];
        } elseif ($lang === "de" && !empty($this->organization['longDescription']['en'])) {
            $orgname = $this->organization['longDescription']['en'];
        } elseif ($lang === "en" && !empty($this->organization['longDescription']['de'])) {
            $orgname = $this->organization['longDescription']['de'];
        }
        if (!empty($orgname)) {
            $jobtitle .= ' '.$orgname;
        }
        $this->jobTitle = $jobtitle;
        return $jobtitle;      
    }
    
    
    
}