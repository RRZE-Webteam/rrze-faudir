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
    protected ?Config $config = null;
    
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
     * Config setzen
     */
    public function setConfig(?Config $config = null): void {
        $this->config = $config ?? new Config();
    }
    
    /*
     * Hole eine Contact via Identifier von der API
     */
    public function getContactbyAPI(string $identifier): bool {
        if (empty($this->config)) {
            $this->setConfig();
        }
        $api = new API($this->config);
        // Hole die Personendaten als Array über die API-Methode.
        $data = $api->getContact($identifier);

        if (empty($data) || !is_array($data)) {
            return false;
        }
        
        $this->populateFromData($data);
        return true;
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
     * Get workplaces and return as Array if exists
     */  
    public function getWorkplacesString(): string {
        if (empty($this->workplaces)) {
            return __('No workplaces available', 'rrze-faudir');
        }
        
        // Format workplaces into a string
        $formattedWorkplaces = [];
        foreach ($this->workplaces as $workplace) {
            $workplaceDetails = [];

            if (!empty($workplace['room'])) {
                $workplaceDetails[] = __('Room', 'rrze-faudir').': ' . $workplace['room'];
            }
            if (!empty($workplace['floor'])) {
                $workplaceDetails[] = __('Floor', 'rrze-faudir').': ' . $workplace['floor'];
            }
            if (!empty($workplace['street'])) {
                $workplaceDetails[] = __('Street', 'rrze-faudir').': ' . $workplace['street'];
            }
            if (!empty($workplace['zip'])) {
                $workplaceDetails[] = __('ZIP Code', 'rrze-faudir').': ' . $workplace['zip'];
            }
            if (!empty($workplace['city'])) {
                $workplaceDetails[] = __('City', 'rrze-faudir').': ' . $workplace['city'];
            }
            if (!empty($workplace['faumap'])) {
                $workplaceDetails[] = __('FAU Map', 'rrze-faudir').': ' . $workplace['faumap'];
            }
            if (!empty($workplace['phones'])) {
                $workplaceDetails[] = __('Phones', 'rrze-faudir').': ' . implode(', ', $workplace['phones']);
            }
            if (!empty($workplace['fax'])) {
                $workplaceDetails[] = __('Fax', 'rrze-faudir').': ' . $workplace['fax'];
            }
            if (!empty($workplace['url'])) {
                $workplaceDetails[] = __('URL', 'rrze-faudir').': ' . $workplace['url'];
            }
            if (!empty($workplace['mails'])) {
                $workplaceDetails[] = __('Emails', 'rrze-faudir').': ' . implode(', ', $workplace['mails']);
            }
            if (!empty($workplace['officeHours'])) {
                $officeHours = array_map(function ($hours) {
                    return __('Weekday ', 'rrze-faudir') . $hours['weekday'] . ': ' . $hours['from'] . ' - ' . $hours['to'];
                }, $workplace['officeHours']);
                $workplaceDetails[] = __('Office Hours', 'rrze-faudir') . implode('; ', $officeHours);
            }
            if (!empty($workplace['consultationHours'])) {
                $consultationHours = array_map(function ($hours) {
                    return __('Weekday ', 'rrze-faudir') . $hours['weekday'] . ': ' . $hours['from'] . ' - ' . $hours['to'] . ' (' . $hours['comment'] . ') ' . $hours['url'];
                }, $workplace['consultationHours']);
                $workplaceDetails[] = __('Consultation Hours', 'rrze-faudir') . implode('; ', $consultationHours);
            }

            $formattedWorkplaces[] = implode("\n", $workplaceDetails);
        }

        return implode("\n\n", $formattedWorkplaces);
        
        
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
     * Get Consultation Infos by Aggreement
     */
    public function getConsultationbyAggreement(array $workplace): ?string {
        if (empty($workplace)) {
            return '';
        }
        // Öffnungszeiten aus Workplace extrahieren & rendern
        $oh = new OpeningHours($workplace);
        return $oh->getConsultationbyAggreement();  
    }
    
    
    
    /**
     * Rendert die Öffnungs-/Sprechzeiten eines Workplace als semantisches HTML.
     * Eingaben:
     *   - $workplace: Workplace-Teilstruktur aus der API (array)
     *   - $key: 'consultationHours' (Standard) oder 'officeHours'
     *   - $withaddress: Wenn true, wird die Workplace-Adresse angehängt
     *   - $lang: Sprachcode ('de' Standard)
     *   - $roomfloor: Adressausgabe: Raum/Etage ergänzen
     *   - $showmap: Adressausgabe: FAU-Map-Link ergänzen
     * Rückgabe:
     *   - HTML-Fragment als string (leer, wenn keine Zeiten vorhanden sind)
     */
    public function getConsultationsHours(array $workplace, string $key = 'consultationHours', ?bool $withaddress = true,?string $lang = 'de', ?bool $roomfloor = false, ?bool $showmap = false): string {
        if (empty($workplace)) {
            return '';
        }

        // Adresse (optional) vorbereiten – wie zuvor in Contact erzeugt
        $addressHtml = '';
        if ($withaddress) {
            $addressHtml = $this->getAddressByWorkplace($workplace, false, $lang, $roomfloor, $showmap) ?? '';
        }

        // Öffnungszeiten rendern
        $oh = new OpeningHours($workplace);
        return $oh->getConsultationsHours($key, $addressHtml, $lang);
    }

    
  
    
    /*
     * Generate Address Output for a Workplace
     */
    public function getAddressByWorkplace(array $workplace, bool $orgname = true, string $lang = "de", ?bool $roomfloor = false, ?bool $showmap = false): ?string {
        $address = $result = $roomfloorpart = '';

        if ($orgname) {
            $address .= $this->getOrganizationName($lang);  
        }
        if (($roomfloor) || ($showmap)) {
            
                $room = $floor = $map = '';        
                if (($roomfloor) && (!empty($workplace['room']))) {
                     $room = '<span class="texticon room"><span class="screen-reader-text">'.__('Room','rrze-faudir').': </span><span itemprop="floorLevel">'.esc_html($workplace['room']).'</span></span>';
                }
                if (($roomfloor) && (!empty($workplace['floor']))) {
                    $floor = '<span class="texticon floor"><span class="screen-reader-text">'.__('Floor','rrze-faudir').': </span><span itemprop="floorLevel">'.esc_html($workplace['floor']).'</span></span>';
                }
                if (($showmap) && (!empty($workplace['faumap']))) {
                    if (preg_match('/^https?:\/\/karte\.fau\.de/i', $workplace['faumap'])) {  
                        $formattedValue = '<a href="' . esc_url($workplace['faumap']) . '" itemprop="hasMap" content="' . esc_url($workplace['faumap']) . '">' .__('FAU Map','rrze-faudir'). '</a>';
                        $map = '<span class="texticon faumap"><span class="screen-reader-text">'.__('Map','rrze-faudir').': </span>'.$formattedValue.'</span>';
                    }
                }
                

                $adressparts = '';
                
                if (!empty($room)) {
                    $adressparts .= $room;
                    if ((!empty($floor)) || (!empty($map))) {
                        $adressparts .= ', ';
                    }
                }
                if (!empty($floor)) {
                    $adressparts .= $floor;
                    if (!empty($map)) {
                        $adressparts .= ', ';
                    }
                }
                if (!empty($map)) {
                    $adressparts .= $map;
                }

                if (!empty($adressparts)) {
                    $roomfloorpart .= '<span class="inaddress icon roomfloor" itemprop="containedInPlace" itemscope itemtype="https://schema.org/Room">';
                    $roomfloorpart .= $adressparts;
                    $roomfloorpart .= '</span>'; 
                }
              
         

           
        }
        
        if ($workplace['street']) {
            $address .= '<span class="street" itemprop="streetAdress">'.esc_html($workplace['street']).'</span>';    
        }
        if (!empty($workplace['postOfficeBoxNumber'])) {
            $address .= '<span class="postbox"><span class="screen-reader-text">'.__('Box Number', 'rrze-faudir').': </span><span itemprop="postOfficeBoxNumber">'.esc_html($workplace['postOfficeBoxNumber']).'</span></span>';    
        }
        
        if ((!empty($workplace['postalCode'])) && (!empty($workplace['addressLocality']) || (!empty($workplace['city'])))) {
            $address .= '<span class="zipcity">';
        }
        if (!empty(!empty($workplace['postalCode']))) {
                $address .= '<span class="postalCode" itemprop="postalCode">'.esc_html($workplace['postalCode']).'</span> ';    
        } elseif (!empty($workplace['zip'])) {
                $address .= '<span class="postalCode" itemprop="postalCode">'.esc_html($workplace['zip']).'</span> ';    
        }
        if (!empty($workplace['addressLocality'])) {
            $address .= '<span class="addressLocality" itemprop="addressLocality">'.esc_html($workplace['addressLocality']).'</span>';    
        } elseif (!empty($workplace['city'])) {
            $address .= '<span class="addressLocality" itemprop="addressLocality">'.esc_html($workplace['city']).'</span>';    
        }
        if ((!empty($workplace['postalCode'])) && (!empty($workplace['addressLocality']) || !empty($workplace['city']))) {    
            $address .= '</span>';
        }
        if (!empty($workplace['addressCountry'])) {
            $address .= '<span class="addressCountry" itemprop="addressCountry">'.esc_html($workplace['addressCountry']).'</span>';    
        }
        
        
        if (!empty($address)) {
            $address = '<address class="texticon" itemprop="address" itemscope="" itemtype="https://schema.org/PostalAddress">'.$address;             
            $address .= '</address>';   
            if (!empty($roomfloorpart)) {
                $address .= $roomfloorpart;
            }
           
            $result = '<div class="workplace-address">' . $address . '</div>';
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

        
        $htmlsurround = self::sanitize_htmlsurround($htmlsurround);
        
        $output = '';
        $output .= '<'.$htmlsurround;
        if (!empty($arialabel)) {
             $output .= ' aria-label="'.trim(esc_attr($arialabel)).'"';
        }
        if (!empty($class)) {
             $output .= ' class="'.trim(esc_attr($class)).'"';
        }
        $output .= '>';
        $output .= '<ul class="list-icons">';
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
        $output .= '</ul>';
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
     * Get Socials as String
     */
     public function getSocialString(): string {
        if (empty($this->socials)) {
             return __('No social media available', 'rrze-faudir');
        }
            
        // Format social media into a string
        $formattedSocials = [];
        foreach ($this->socials as $social) {
            if (!empty($social['platform']) && !empty($social['url'])) {
                $formattedSocials[] = ucfirst($social['platform']) . ': ' . $social['url'];
            }
        }

        return implode("\n", $formattedSocials);
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
     * Get all FunctionLabels
     */
    public function getAllFunctionLabels(): array {
        $out = [];

        if (!empty($this->function) && is_string($this->function)) {
            $out[] = $this->function;
        }
        if (!empty($this->functionLabel['de'])) {
            $out[] = (string) $this->functionLabel['de'];
        }
        if (!empty($this->functionLabel['en'])) {
            $out[] = (string) $this->functionLabel['en'];
        }

        // trimmen, Leere raus, Duplikate entfernen
        $out = array_map(static fn($s) => trim((string) $s), $out);
        $out = array_values(array_unique(array_filter($out, static fn($s) => $s !== '')));

        return $out;
    }

    
    
    
    /*
     * Build JobTitle by Functionlabel and Orgname
     */
    public function getJobTitle(string $lang = "de", ?string $template = ''): ?string {   
        $label = $this->getFunctionLabel($lang);   
        if (empty($label)) {
            return '';
        }

 
        if (empty($this->organization) || !isset($this->organization['longDescription'])) {
            return $label;
        }
    
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
        
        
        
        if (empty($template)) {
            $template = "#functionlabel# #orgname#".PHP_EOL;
        }
        // Platzhalter mit den entsprechenden Werten ersetzen
        $replacements = [
            '#orgname#' => $orgname ?? '',
            '#functionlabel#' => $label ?? '',
            '#alternatename#' =>  $this->organization['alternateName'] ?? ''
        ]; 
        $jobtitle = str_replace(array_keys($replacements), array_values($replacements), $template);     
        $this->jobTitle = $jobtitle;
        
        return $jobtitle;      
    }


    /*
    * Prüft, ob der Kontakt eine der übergebenen Rollen besitzt.
    * Optional: Wenn $organizationIdentifier übergeben ist, muss die Org-ID
    *           dieses Kontakts exakt übereinstimmen.
    *
    * @param string      $role                   Kommaseparierte Rollen (z. B. "Professor, Postdoc")
    * @param string|null $organizationIdentifier Erwartete Org-ID dieses Kontakts (optional)
    * @return bool       true, wenn (optional Org passt und) eine Rollen-Übereinstimmung besteht
    */
   public function isRole(string $role, ?string $organizationIdentifier = null): bool {
       // Rollen normalisieren
       $roles = array_values(array_filter(array_map('trim', explode(',', $role)), 'strlen'));
       if (empty($roles)) {
           return false;
       }

       // Optional: Organisation abgleichen
       if ($organizationIdentifier !== null) {
           $contactOrgId = $this->organization['identifier'] ?? null;
           if (!$contactOrgId || (string) $contactOrgId !== (string) $organizationIdentifier) {
               return false;
           }
       }

       // Rollenfelder prüfen (exakte Matches)
       $fn   = $this->function ?? null;
       $fnDe = $this->functionLabel['de'] ?? null;
       $fnEn = $this->functionLabel['en'] ?? null;

       $matches =
           ($fn   !== null && in_array($fn, $roles, true)) ||
           ($fnDe !== null && in_array($fnDe, $roles, true)) ||
           ($fnEn !== null && in_array($fnEn, $roles, true));

       return $matches;
   }

}

