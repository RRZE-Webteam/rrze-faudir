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
    public ?string $titleOfNobility = null;
    public ?string $function = null;
    public ?string $jobTitle = null;
    public array $functionLabel = [];
    public array $workplaces = [];
    public array $socials = [];
    public array $org = [];

    private array $rawdata = [];
    protected ?Config $config = null;
    
    /**
     * Contact constructor
     */
    public function __construct(array $data = []) {
        $this->populateFromData($data, true);
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
            $this->titleOfNobility  = null;
            $this->function         = null;
            $this->jobTitle         = null;
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
            $this->org = $data['org']; // BUGFIX: war organization_address
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
    public function getWorkplaces(): array {
        return $this->workplaces;   
    }
    
   /*
    * Get workplaces as plain text for <textarea>.
    * $lineSeparator: separator between fields inside one workplace (default "\n")
    * $blockSeparator: separator between workplaces (default "\n\n")
    */
   public function getWorkplacesString(string $lineSeparator = "\n", string $blockSeparator = "\n\n"): string {
       if (empty($this->workplaces) || !is_array($this->workplaces)) {
           return __('No workplaces available', 'rrze-faudir');
       }

       // normalize separators for textarea output
       $lineSeparator = FaudirUtils::normalizeTextareaSeparator($lineSeparator, "\n");
       $blockSeparator = FaudirUtils::normalizeTextareaSeparator($blockSeparator, "\n\n");

       $workplaceBlocks = [];

       foreach ($this->workplaces as $workplace) {
           if (empty($workplace) || !is_array($workplace)) {
               continue;
           }

           $parts = [];

           if (!empty($workplace['room'])) {
               $parts[] = __('Room', 'rrze-faudir') . ': ' . (string) $workplace['room'];
           }
           if (!empty($workplace['floor'])) {
               $parts[] = __('Floor', 'rrze-faudir') . ': ' . (string) $workplace['floor'];
           }
           if (!empty($workplace['street'])) {
               $parts[] = __('Street', 'rrze-faudir') . ': ' . (string) $workplace['street'];
           }

           $zip = '';
           if (!empty($workplace['zip'])) {
               $zip = (string) $workplace['zip'];
           } elseif (!empty($workplace['postalCode'])) {
               $zip = (string) $workplace['postalCode'];
           }
           if ($zip !== '') {
               $parts[] = __('ZIP Code', 'rrze-faudir') . ': ' . $zip;
           }

           $city = '';
           if (!empty($workplace['city'])) {
               $city = (string) $workplace['city'];
           } elseif (!empty($workplace['addressLocality'])) {
               $city = (string) $workplace['addressLocality'];
           }
           if ($city !== '') {
               $parts[] = __('City', 'rrze-faudir') . ': ' . $city;
           }

           if (!empty($workplace['faumap'])) {
               $parts[] = __('FAU Map', 'rrze-faudir') . ': ' . (string) $workplace['faumap'];
           }

           if (!empty($workplace['phones']) && is_array($workplace['phones'])) {
               $phones = FaudirUtils::normalizeStringArray($workplace['phones']);
               if (!empty($phones)) {
                   $parts[] = __('Phones', 'rrze-faudir') . ': ' . implode(', ', $phones);
               }
           }

           if (!empty($workplace['fax'])) {
               if (is_array($workplace['fax'])) {
                   $fax = FaudirUtils::normalizeStringArray($workplace['fax']);
                   if (!empty($fax)) {
                       $parts[] = __('Fax', 'rrze-faudir') . ': ' . implode(', ', $fax);
                   }
               } else {
                   $parts[] = __('Fax', 'rrze-faudir') . ': ' . (string) $workplace['fax'];
               }
           }

           if (!empty($workplace['url'])) {
               $parts[] = __('URL', 'rrze-faudir') . ': ' . (string) $workplace['url'];
           }

           if (!empty($workplace['mails']) && is_array($workplace['mails'])) {
               $mails = FaudirUtils::normalizeStringArray($workplace['mails']);
               if (!empty($mails)) {
                   $parts[] = __('Emails', 'rrze-faudir') . ': ' . implode(', ', $mails);
               }
           }

           if (!empty($workplace['officeHours']) && is_array($workplace['officeHours'])) {
               $oh = [];
               foreach ($workplace['officeHours'] as $hours) {
                   if (empty($hours) || !is_array($hours)) {
                       continue;
                   }
                   $wd = isset($hours['weekday']) ? (string) $hours['weekday'] : '';
                   $from = isset($hours['from']) ? (string) $hours['from'] : '';
                   $to = isset($hours['to']) ? (string) $hours['to'] : '';
                   if ($wd !== '' && $from !== '' && $to !== '') {
                       $oh[] = __('Weekday', 'rrze-faudir') . ' ' . $wd . ': ' . $from . ' - ' . $to;
                   }
               }
               $oh = FaudirUtils::normalizeStringArray($oh);
               if (!empty($oh)) {
                   $parts[] = __('Office Hours', 'rrze-faudir') . ': ' . implode('; ', $oh);
               }
           }

           if (!empty($workplace['consultationHours']) && is_array($workplace['consultationHours'])) {
               $ch = [];
               foreach ($workplace['consultationHours'] as $hours) {
                   if (empty($hours) || !is_array($hours)) {
                       continue;
                   }
                   $wd = isset($hours['weekday']) ? (string) $hours['weekday'] : '';
                   $from = isset($hours['from']) ? (string) $hours['from'] : '';
                   $to = isset($hours['to']) ? (string) $hours['to'] : '';
                   $comment = isset($hours['comment']) ? trim((string) $hours['comment']) : '';
                   $url = isset($hours['url']) ? trim((string) $hours['url']) : '';

                   if ($wd === '' || $from === '' || $to === '') {
                       continue;
                   }

                   $line = __('Weekday', 'rrze-faudir') . ' ' . $wd . ': ' . $from . ' - ' . $to;
                   if ($comment !== '') {
                       $line .= ' (' . $comment . ')';
                   }
                   if ($url !== '') {
                       $line .= ' ' . $url;
                   }
                   $ch[] = $line;
               }
               $ch = FaudirUtils::normalizeStringArray($ch);
               if (!empty($ch)) {
                   $parts[] = __('Consultation Hours', 'rrze-faudir') . ': ' . implode('; ', $ch);
               }
           }

           $parts = FaudirUtils::normalizeStringArray($parts);
           if (empty($parts)) {
               continue;
           }

           $workplaceBlocks[] = implode($lineSeparator, $parts);
       }

       if (empty($workplaceBlocks)) {
           return __('No workplaces available', 'rrze-faudir');
       }

       return implode($blockSeparator, $workplaceBlocks);
   }

   
    /*
     * Get Orgname
     */
    public function getOrganizationName(?string $lang = "de"): ?string {
        if ($lang === null || $lang === '') {
            $lang = 'de';
        }
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
    public function getConsultationsHours(array $workplace, string $key = 'consultationHours', ?bool $withaddress = true,?string $lang = 'de', ?bool $room = false, ?bool $floor = false, ?bool $showmap = false): string {
        if (empty($workplace)) {
            return '';
        }

        // Adresse (optional) vorbereiten – wie zuvor in Contact erzeugt
        $addressHtml = '';
        if ($withaddress) {
            $addressHtml = $this->getAddressByWorkplace($workplace, false, $lang, $room, $floor, $showmap) ?? '';
        }

        // Öffnungszeiten rendern
        $oh = new OpeningHours($workplace);
        return $oh->getConsultationsHours($key, $addressHtml, $lang);
    }

   
    
    /*
     * Generate Address Output for a Workplace
     */
    public function getAddressByWorkplace( array $workplace,bool $orgname = true,string $lang = 'de', ?bool $room = false, ?bool $floor = false, ?bool $showmap = false, string $sep = ' '): ?string {
        if (empty($workplace)) {
            return '';
        }

        $sep = trim($sep);
        if ($sep !== '<br>' && $sep !== "\n" && $sep !== ' ') {
            $sep = ' ';
        }

        $parts = [];

        if ($orgname) {
            $org = (string) $this->getOrganizationName($lang);
            if ($org !== '') {
                $parts[] = $org;
            }
        }

        if (!empty($workplace['street']) && is_string($workplace['street'])) {
            $parts[] = '<span class="street" itemprop="streetAddress">' . esc_html($workplace['street']) . '</span>';
        }

        if (!empty($workplace['postOfficeBoxNumber']) && is_string($workplace['postOfficeBoxNumber'])) {
            $parts[] = '<span class="postbox"><span class="screen-reader-text">' . __('Box Number', 'rrze-faudir') . ': </span><span itemprop="postOfficeBoxNumber">' . esc_html($workplace['postOfficeBoxNumber']) . '</span></span>';
        }

        $postalCode = '';
        if (!empty($workplace['postalCode']) && is_string($workplace['postalCode'])) {
            $postalCode = $workplace['postalCode'];
        } elseif (!empty($workplace['zip']) && is_string($workplace['zip'])) {
            $postalCode = $workplace['zip'];
        }

        $locality = '';
        if (!empty($workplace['addressLocality']) && is_string($workplace['addressLocality'])) {
            $locality = $workplace['addressLocality'];
        } elseif (!empty($workplace['city']) && is_string($workplace['city'])) {
            $locality = $workplace['city'];
        }

        if ($postalCode !== '' || $locality !== '') {
            $zipCity = '';
            if ($postalCode !== '') {
                $zipCity .= '<span class="postalCode" itemprop="postalCode">' . esc_html($postalCode) . '</span>';
            }
            if ($locality !== '') {
                if ($zipCity !== '') {
                    $zipCity .= ' ';
                }
                $zipCity .= '<span class="addressLocality" itemprop="addressLocality">' . esc_html($locality) . '</span>';
            }
            $parts[] = '<span class="zipcity">' . $zipCity . '</span>';
        }

        if (!empty($workplace['addressCountry']) && is_string($workplace['addressCountry'])) {
            $parts[] = '<span class="addressCountry" itemprop="addressCountry">' . esc_html($workplace['addressCountry']) . '</span>';
        }

        // containedInPlace -> Room (optional)
        $roomFloorPart = '';
        if ($room || $floor || $showmap) {
            $chips = [];

            if ($room && !empty($workplace['room']) && is_string($workplace['room'])) {
                // Schema: Room.name (Raumnummer ist idR der Name)
                $chips[] = '<span class="texticon room"><span class="screen-reader-text">' . __('Room', 'rrze-faudir') . ': </span><span itemprop="name">' . esc_html($workplace['room']) . '</span></span>';
            }

            if ($floor && !empty($workplace['floor']) && is_string($workplace['floor'])) {
                // Schema: Room.floorLevel
                $chips[] = '<span class="texticon floor"><span class="screen-reader-text">' . __('Floor', 'rrze-faudir') . ': </span><span itemprop="floorLevel">' . esc_html($workplace['floor']) . '</span></span>';
            }

            if ($showmap && !empty($workplace['faumap']) && is_string($workplace['faumap'])) {
                $faumap = trim($workplace['faumap']);
                if ($faumap !== '' && preg_match('/^https?:\/\/karte\.fau\.de/i', $faumap)) {
                    $chips[] = '<span class="texticon faumap"><span class="screen-reader-text">' . __('Map', 'rrze-faudir') . ': </span><a href="' . esc_url($faumap) . '" itemprop="hasMap">' . __('FAU Map', 'rrze-faudir') . '</a></span>';
                }
            }

            if (!empty($chips)) {
                $chipsJoiner = ($sep === '<br>') ? ', ' : ', ';
                // containedInPlace hängt semantisch am Place/Workplace-Kontext, nicht am PostalAddress
                $roomFloorPart  = '<span class="inaddress icon roomfloor" itemprop="containedInPlace" itemscope itemtype="https://schema.org/Room">';
                $roomFloorPart .= implode($chipsJoiner, $chips);
                $roomFloorPart .= '</span>';
            }
        }

        if (empty($parts) && $roomFloorPart === '') {
            return '';
        }

        $joiner = ($sep === '<br>') ? '<br>' : $sep;
        $addressInner = implode($joiner, $parts);

        $html  = '<div class="workplace-address" data-wpautop="off">';
        $html .= '<address class="texticon" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
        $html .= $addressInner;
        $html .= '</address>';
        if ($roomFloorPart !== '') {
            // roomfloor is outside PostalAddress, but still associated via itemprop on the surrounding entity in your templates
            $html .= $roomFloorPart;
        }
        $html .= '</div>';

        return $html;
    }
    
    
    /*
     * Get Social Website Liste as semantic HTML
     */
    public function getSocialMedia(string $htmlsurround = 'div', string $class = 'icon-list icon', string $arialabel = '', string $context = ''): string {
        $items = FaudirUtils::normalizeSocialItems($this->socials);
        return FaudirUtils::renderSocialMediaList($items, $htmlsurround, $class, $arialabel, $context);
    }
    
    
    /*
     * Get Social/Website from Contact and transform them into a assoc. array
     */
    public function getSocialArray(): array {
        return FaudirUtils::normalizeSocialItems($this->socials);
    }
    
    
    /*
     * Get Socials as String
     */
     public function getSocialString(): string {
        $items = FaudirUtils::normalizeSocialItems($this->socials);
        $text = FaudirUtils::renderSocialMediaText($items, "\n");

        if ($text === '') {
            return __('No social media available', 'rrze-faudir');
        }

        return $text; 
     }

    
    
    /*
     * Get the FunctionLabel
     */
    public function getFunctionLabel(string $lang = 'de'): ?string {
        $lang = sanitize_key((string) $lang);
        if ($lang === '') {
            $lang = 'de';
        }

        if (empty($this->functionLabel) || !is_array($this->functionLabel)) {
            return '';
        }

        $candidates = [];

        // Primärsprache
        if (!empty($this->functionLabel[$lang])) {
            $candidates[] = $this->functionLabel[$lang];
        }

        // Fallback
        if ($lang === 'de' && !empty($this->functionLabel['en'])) {
            $candidates[] = $this->functionLabel['en'];
        } elseif ($lang !== 'de' && !empty($this->functionLabel['de'])) {
            $candidates[] = $this->functionLabel['de'];
        }

        $normalized = FaudirUtils::normalizeStringArray($candidates);

        return $normalized[0] ?? '';
    }

     /*
     * Get all FunctionLabels
     */
    public function getAllFunctionLabels(): array {
        $out = [];

        if (!empty($this->function)) {
            $out[] = (string) $this->function;
        }
        if (!empty($this->functionLabel['de'])) {
            $out[] = (string) $this->functionLabel['de'];
        }
        if (!empty($this->functionLabel['en'])) {
            $out[] = (string) $this->functionLabel['en'];
        }


        return FaudirUtils::normalizeStringArray($out);
    }

    
    
    
    /*
     * Build JobTitle by Functionlabel and Orgname
     */
    public function getJobTitle(string $lang = 'de', ?string $template = ''): ?string {
        $lang = sanitize_key((string) $lang);
        if ($lang === '') {
            $lang = 'de';
        }

        $label = $this->getFunctionLabel($lang);
        if ($label === '') {
            return '';
        }

        $orgCandidates = [];

        if (!empty($this->organization['longDescription']) && is_array($this->organization['longDescription'])) {
            $ld = $this->organization['longDescription'];

            if (!empty($ld[$lang])) {
                $orgCandidates[] = $ld[$lang];
            }

            if ($lang === 'de' && !empty($ld['en'])) {
                $orgCandidates[] = $ld['en'];
            } elseif ($lang !== 'de' && !empty($ld['de'])) {
                $orgCandidates[] = $ld['de'];
            }
        }

        $orgNormalized = FaudirUtils::normalizeStringArray($orgCandidates);
        $orgname = $orgNormalized[0] ?? '';

        if ($orgname === '') {
            $this->jobTitle = $label;
            return $this->jobTitle;
        }

        if ($template === null || trim((string) $template) === '') {
            $template = "#functionlabel# #orgname#";
        }

        $replacements = [
            '#orgname#'       => $orgname,
            '#functionlabel#' => $label,
            '#alternatename#' => $this->organization['alternateName'] ?? '',
        ];

        $jobtitle = str_replace(array_keys($replacements), array_values($replacements), (string) $template);
        $jobtitle = trim(preg_replace("/[ \t]+/", " ", $jobtitle) ?? $jobtitle);

        $this->jobTitle = $jobtitle;

        return $this->jobTitle;
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
        $roles = FaudirUtils::csvToArray($role);
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

        // Rollen normalisieren (trim + lowercase)
        $needles = [];
        foreach ($roles as $r) {
            $r = trim((string) $r);
            if ($r === '') {
                continue;
            }
            $needles[] = function_exists('mb_strtolower') ? mb_strtolower($r, 'UTF-8') : strtolower($r);
        }
        $needles = FaudirUtils::normalizeStringArray($needles);
        if (empty($needles)) {
            return false;
        }

        // Labels des Kontakts normalisieren und vergleichen
        $labels = $this->getAllFunctionLabels();
        foreach ($labels as $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $hay = function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);

            if (in_array($hay, $needles, true)) {
                return true;
            }
        }

        return false;
    }

}

