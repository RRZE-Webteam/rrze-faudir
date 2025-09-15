<?php

/*
 * Organization
 * Handles data for Organizations
 */

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

class Organization {
    public array $context = [];
    public string $id = '';
    public string $type = '';
    public string $identifier = '';
    public string $disambiguatingDescription = '';
    public array $longDescription = []; // ['de' => '', 'en' => '']
    public string $name = '';
    public string $alternateName = '';
    public string $additionalType = '';
    public array $address = [];
    public array $postalAddress = [];
    public array $internalAddress = [];
    public array $parentOrganization = []; // parent org
    public array $subOrganization = [];    // list of subOrgs
    public ?array $socials = [];
    public array $content = [];

    /** Öffnungszeiten-/Sprechstunden-Objekt (gekapselt) */
    public ?OpeningHours $openingHours = null;

    protected ?Config $config = null;

    public function __construct(array $data = []) {
        $this->resetFields();
        $this->fromArray($data, false);
    }

    public function toArray(): array {
        $base = [
            '@context'                  => $this->context,
            '@id'                       => $this->id,
            '@type'                     => $this->type,
            'identifier'                => $this->identifier,
            'disambiguatingDescription' => $this->disambiguatingDescription,
            'longDescription'           => $this->longDescription,
            'name'                      => $this->name,
            'alternateName'             => $this->alternateName,
            'additionalType'            => $this->additionalType,
            'address'                   => $this->address,
            'postalAddress'             => $this->postalAddress,
            'internalAddress'           => $this->internalAddress,
            'parentOrganization'        => $this->parentOrganization,
            'subOrganization'           => $this->subOrganization,
            'content'                   => $this->content,
            'socials'                   => $this->socials,
        ];

        // Öffnungszeiten-Felder mit ausgeben (flach auf Top-Level, wie bisherige API-Struktur)
        $oh = $this->openingHours ? $this->openingHours->toArray() : [];
        return array_merge($base, $oh);
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->toArray()[$key] ?? $default;
    }

    /*
     * Config setzen
     */
    public function setConfig(?Config $config = null): void {
        $this->config = $config ?? new Config();
    }

    public function fromArray(array $data, bool $clear = true): void {
        if ($clear) {
            $this->resetFields();
        }

        if (isset($data['@context'])) $this->context = $data['@context'];
        if (isset($data['@id'])) $this->id = $data['@id'];
        if (isset($data['@type'])) $this->type = $data['@type'];
        if (isset($data['identifier'])) $this->identifier = $data['identifier'];
        if (isset($data['disambiguatingDescription'])) $this->disambiguatingDescription = $data['disambiguatingDescription'];
        if (isset($data['longDescription'])) $this->longDescription = $data['longDescription'];
        if (isset($data['name'])) $this->name = $data['name'];
        if (isset($data['alternateName'])) $this->alternateName = $data['alternateName'];
        if (isset($data['additionalType'])) $this->additionalType = $data['additionalType'];
        if (isset($data['address'])) $this->address = $data['address'];
        if (isset($data['postalAddress'])) $this->postalAddress = $data['postalAddress'];
        if (isset($data['internalAddress'])) $this->internalAddress = $data['internalAddress'];
        if (isset($data['parentOrganization'])) $this->parentOrganization = $data['parentOrganization'];
        if (isset($data['subOrganization'])) $this->subOrganization = $data['subOrganization'];
        if (isset($data['content'])) $this->content = $data['content'];
        if (isset($data['socials'])) $this->socials = $data['socials'];

        // Öffnungszeiten aus gleichen Daten mit befüllen
        if (!$this->openingHours) {
            $this->openingHours = new OpeningHours();
        }
        // OpeningHours::fromArray ignoriert unbekannte Keys – d.h. safe
        $this->openingHours->fromArray($data, true);
    }

    private function resetFields(): void {
        $this->context = [];
        $this->id = '';
        $this->type = '';
        $this->identifier = '';
        $this->disambiguatingDescription = '';
        $this->longDescription = [];
        $this->name = '';
        $this->alternateName = '';
        $this->additionalType = '';
        $this->address = [];
        $this->postalAddress = [];
        $this->internalAddress = [];
        $this->parentOrganization = [];
        $this->subOrganization = [];
        $this->content = [];
        $this->socials = [];
        $this->openingHours = new OpeningHours(); // leer initialisieren
    }

    /*
     * Hole eine ORG via Identifier von der API
     */
    public function getOrgbyAPI(string $identifier): bool {
        if (!self::isOrgIdentifier($identifier)) {
            return false;
        }
        if (empty($this->config)) {
            $this->setConfig();
        }
        $api = new API($this->config);
        $data = $api->getOrgById($identifier);

        if (empty($data) || !is_array($data)) {
            do_action('rrze.log.error', "FAUdir\Organization (getOrgbyAPI): No Orgdata with identifier {$identifier}");
            return false;
        }
       // do_action('rrze.log.info', "FAUdir\Organization (getOrgbyAPI): Get Orgdata with identifier {$identifier}", $data);
        $this->fromArray($data);
        return true;
    }

    
    /*
     * Hole die Identifier einer Orgnr
     */
    public function getIdentifierbyOrgnr(string $orgnr): string {
        if (!self::isOrgnr($orgnr)) {
            return false;
        }
        if (empty($this->config)) {
            $this->setConfig();
        }
        $api = new API($this->config);
        $data = $api->getOrgList(1, 0, ['lq' => 'disambiguatingDescription[eq]=' . $orgnr, 'attrs' => 'identifier']);
        $identifier = '';
         
        if (empty($data) || !is_array($data)) {
            do_action('rrze.log.error', "FAUdir\Organization (getOrgbyOrgnr): No Org Identifier found with number {$orgnr}");
            return $identifier;
        }
       
       
        if (isset($data['data']['identifier'])) {
             $identifier = $data['data']['identifier'];
        } elseif (isset($data['data'][0]['identifier'])) {
            $identifier = $data['data'][0]['identifier'];     
        }
   //     do_action('rrze.log.info', "FAUdir\Organization (getOrgbyOrgnr): Get org Identifier with number {$orgnr}: $identifier");
        
        return $identifier;
    }

    
    /*
     * Hole eine ORG via Orgnr von der API
     */
    public function getOrgbyOrgnr(string $orgnr): bool {
        if (!self::isOrgnr($orgnr)) {
            return false;
        }
        if (empty($this->config)) {
            $this->setConfig();
        }
        $api = new API($this->config);
        $data = $api->getOrgList(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . $orgnr]);

        if (empty($data) || !is_array($data)) {
            do_action('rrze.log.error', "FAUdir\Organization (getOrgbyOrgnr): No Orgdata with number {$orgnr}");
            return false;
        }
  //      do_action('rrze.log.info', "FAUdir\Organization (getOrgbyOrgnr): Get Orgdata with number {$orgnr}", $data);
        if (isset($data['data'][0])) {
            $this->fromArray($data['data'][0]);
        } elseif (!isset($data['data']) && !empty($data)) {
            $this->fromArray($data);
        }
        return true;
    }

    /*
     * Create Address as String
     */
    public function getAdressString(): string {
        if (empty($this->address)) {
            return __('No address available', 'rrze-faudir');
        }

        $address = $this->address;
        $addressDetails = [];

        if (!empty($address['phone'])) $addressDetails[] = __('Phone', 'rrze-faudir') . ': ' . $address['phone'];
        if (!empty($address['mail']))  $addressDetails[] = __('Email', 'rrze-faudir') . ': ' . $address['mail'];
        if (!empty($address['url']))   $addressDetails[] = __('URL', 'rrze-faudir') . ': ' . $address['url'];
        if (!empty($address['street'])) $addressDetails[] = __('Street', 'rrze-faudir') . ': ' . $address['street'];
        if (!empty($address['zip']))    $addressDetails[] = __('ZIP Code', 'rrze-faudir') . ': ' . $address['zip'];
        if (!empty($address['city']))   $addressDetails[] = __('City', 'rrze-faudir') . ': ' . $address['city'];
        if (!empty($address['faumap'])) $addressDetails[] = __('FAU Map', 'rrze-faudir') . ': ' . $address['faumap'];

        return implode("\n", $addressDetails);
    }

    // Prüfen ob wir eine syntaktisch valide Orgnr haben
    public static function isOrgnr(string $input): bool {
        return (bool) preg_match('/^\d{10}$/', $input);
    }

    // Prüfen ob wir eine syntaktisch valide Org-Identifier haben
    public static function isOrgIdentifier(string $input): bool {
        return (bool) preg_match('/^[a-z0-9]+$/', $input);
    }

    // Sanitize Org Identifier, auch wenn eine URL übergeben wurde
    public static function sanitizeOrgIdentifier(string $input): ?string {
        if (preg_match('/^[a-z0-9]+$/', $input)) {
            return $input;
        }

        if (filter_var($input, FILTER_VALIDATE_URL)) {
            $parts = explode('/', rtrim($input, '/'));
            $lastSegment = end($parts);
            if (preg_match('/^[a-z0-9]+$/', $lastSegment)) {
                return $lastSegment;
            }
            return preg_replace('/[^a-z0-9]/', '', strtolower($lastSegment));
        }
        return preg_replace('/[^a-z0-9]/', '', strtolower($input));
    }

    /*
     * Generate Address Output (HTML)
     */
    public function getAddressOutput(bool $orgname = false, string $lang = "de", bool $showmap = false): ?string {
        $address = $result = '';

        if ($orgname) {
            $address .= $this->getName(true, $lang);
        }
        $workplace = $this->address;

        if ($showmap && !empty($workplace['faumap'])) {
            $map = '';
            if (preg_match('/^https?:\/\/karte\.fau\.de/i', $workplace['faumap'])) {
                $formattedValue = '<a href="' . esc_url($workplace['faumap']) . '" itemprop="hasMap" content="' . esc_url($workplace['faumap']) . '">' . __('FAU Map', 'rrze-faudir') . '</a>';
                $map = '<span class="faumap">' . __('Map', 'rrze-faudir') . ': ' . $formattedValue . '</span>';
            }
            if (!empty($map)) {
                $address .= '<span class="roomfloor" itemprop="containedInPlace" itemscope itemtype="https://schema.org/Room">';
                $address .= $map;
                $address .= '</span>';
            }
        }

        if (!empty($workplace['street'])) {
            $address .= '<span class="street" itemprop="streetAdress">' . esc_html($workplace['street']) . '</span>';
        }
        if (!empty($workplace['postOfficeBoxNumber'])) {
            $address .= '<span class="postbox"><span class="screen-reader-text">' . __('Box Number', 'rrze-faudir') . ': </span><span itemprop="postOfficeBoxNumber">' . esc_html($workplace['postOfficeBoxNumber']) . '</span></span>';
        }

        if ((!empty($workplace['zip'])) && (!empty($workplace['postalCode'])) && (!empty($workplace['addressLocality']) || (!empty($workplace['city'])))) {
            $address .= '<span class="zipcity">';
        }
        if (!empty(!empty($workplace['postalCode']))) {
            $address .= '<span class="postalCode" itemprop="postalCode">' . esc_html($workplace['postalCode']) . '</span> ';
        } elseif (!empty($workplace['zip'])) {
            $address .= '<span class="postalCode" itemprop="postalCode">' . esc_html($workplace['zip']) . '</span> ';
        }
        if (!empty($workplace['addressLocality'])) {
            $address .= '<span class="addressLocality" itemprop="addressLocality">' . esc_html($workplace['addressLocality']) . '</span>';
        } elseif (!empty($workplace['city'])) {
            $address .= '<span class="addressLocality" itemprop="addressLocality">' . esc_html($workplace['city']) . '</span>';
        }
        if ((!empty($workplace['zip'])) && (!empty($workplace['postalCode'])) && (!empty($workplace['addressLocality']) || !empty($workplace['city']))) {
            $address .= '</span>';
        }
        if (!empty($workplace['addressCountry'])) {
            $address .= '<span class="addressCountry" itemprop="addressCountry">' . esc_html($workplace['addressCountry']) . '</span>';
        }

        if (!empty($address)) {
            $address = '<span class="texticon" itemprop="address" itemscope="" itemtype="https://schema.org/PostalAddress">' . $address . '</span>';
            $result = '<div class="workplace-address">' . $address . '</div>';
        }
        return $result;
    }

     /*
     * Generate Postal Address Output (HTML)
     */
    public function getPostalAddressOutput(bool $orgname = false, string $lang = "de"): ?string {
        $address = $result = '';

        $workplace = $this->postalAddress;
        if (empty($workplace)) {
            return '';
        }  
        
        if ($orgname) {
            if (!empty($workplace['shortName'])) {
                $address .= $workplace['shortName'];
            } else {
                $address .= $this->getName(true, $lang);
            }
        }
      
        if (!empty($workplace['extension'])) {
            $address .= '<span class="extension" itemprop="extendedAddress">' . esc_html($workplace['extension']) . '</span>';
        }
      

        if (!empty($workplace['street'])) {
            $address .= '<span class="street" itemprop="streetAdress">' . esc_html($workplace['street']) . '</span>';
        }
        
        if (!empty($workplace['zip']) || (!empty($workplace['city']))) {
            $address .= '<span class="zipcity">';
       
            if (!empty($workplace['zip'])) {
                $address .= '<span class="postalCode" itemprop="postalCode">' . esc_html($workplace['zip']) . '</span> ';
            }
            if (!empty($workplace['city'])) {
                $address .= '<span class="addressLocality" itemprop="addressLocality">' . esc_html($workplace['city']) . '</span>';
            }

            $address .= '</span>';
        }
        

        if (!empty($address)) {
            $address = '<span class="texticon" itemprop="address" itemscope="" itemtype="https://schema.org/PostalAddress">' . $address . '</span>';
            $result = '<div class="workplace-address">' . $address . '</div>';
        }
        return $result;
    }

    
    
    /*
     * Get Phone Number
     */
    public function getPhone(): ?string {
        $phone = '';
        if (!empty($this->address) && !empty($this->address['phone'])) {
            $phone = $this->address['phone'];
        }
        return $phone;
    }

    /*
     * Get Fax Number
     */
    public function getFax(): ?string {
        $fax = '';
        if (!empty($this->address) && !empty($this->address['fax'])) {
            $fax = $this->address['fax'];
        }
        return $fax;
    }

    /*
     * Get E-Mail
     */
    public function getEMail(): ?string {
        $mail = '';
        if (!empty($this->address) && !empty($this->address['mail'])) {
            $mail = $this->address['mail'];
        }
        return $mail;
    }

    
        /*
     * Get getFAUMap
     */
    public function getFAUMap(): ?string {
        $map = '';
        if (!empty($this->address) && !empty($this->address['faumap'])) {
            $map = $this->address['faumap'];
        }
        return $map;
    }
    
    /*
     * Get Name
     */
    public function getName(bool $uselongdesc = false, string $lang = 'de'): string {
        $name = '';
        if ($uselongdesc && isset($this->longDescription[$lang])) {
            $name = $this->longDescription[$lang];
        }
        if (empty($name)) {
            $name = $this->name;
        }
        return $name;
    }
    
    
     /*
     * Get Name
     */
    public function getalternateName(): string {
        if (!empty($this->alternateName)) {
            return $this->alternateName;
        }
       
        return '';
    }
    
    

   /*
    * Liefert alle Text-Einträge vom Typ 'text' aus $this->content in der gewünschten Sprache.
    * Jeder Eintrag wird mit esc_html() sicher ausgegeben (kein HTML aus der API möglich).
    * Optional kann jeder Eintrag mit einem HTML-Tag (Default <p>) umschlossen werden.
    *
    * @param string      $lang      Sprachschlüssel, z.B. 'de' (Default).
    * @param string|null $wrapTag   HTML-Tag für den Wrapper (z.B. 'p', 'div', 'span').
    *                               null oder '' = kein Wrapper. Default 'p'.
    * @return string                 Zusammengefügter HTML-/Text-String.
    */
   public function getContentText(string $lang = 'de', ?string $wrapTag = 'p'): string {
       if (empty($this->content) || !is_array($this->content)) {
           return '';
       }

       $parts = [];

       foreach ($this->content as $entry) {
           if (!is_array($entry) || (($entry['type'] ?? '') !== 'text')) {
               continue;
           }

           $raw = $entry['text'][$lang] ?? '';
           if (!is_string($raw) || $raw === '') {
               continue;
           }

           // Text-Inhalt sicher ausgeben (keine HTML-Interpretation möglich)
           $safeText = esc_html($raw);

           if (!empty($wrapTag)) {
               $tag = self::sanitize_htmlsurround($wrapTag);
               $parts[] = '<' . $tag . '>' . $safeText . '</' . $tag . '>';
           } else {
               $parts[] = $safeText;
           }
       }

       return implode("\n", $parts);
   }

    /*
     * Get URL for Org
     */
    public function getURL(bool $fallbackfaudir = true): string {
        $cpt_url = '';
        if (!empty($this->address) && !empty($this->address['url'])) {
            $cpt_url = $this->address['url'];
        }

        if ((empty($cpt_url)) && ($fallbackfaudir)) {
            if (empty($this->config)) {
                $this->setConfig();
            }
            $cpt_url = $this->config->get('faudir-url') . 'public/org/' . $this->identifier;
        }
        return $cpt_url;
    }

    /*
     * Get a random identifier (for aria-labelledby, etc.)
     */
    public function getRandomId(string $prefix = ''): string {
        $res = '';
        if (!empty($prefix)) {
            $res = esc_attr($prefix);
        }
        $only_numbers = filter_var($this->identifier, FILTER_SANITIZE_NUMBER_INT);
        $res .= $only_numbers . '-' . wp_rand(1000, 5000);
        return $res;
    }

    /*
     * Socials as semantic HTML list
     */
    public function getSocialMedia(string $htmlsurround = 'div', string $class = 'icon-list icon', string $arialabel = ''): string {
        $data = $this->getSocialArray();
        if (empty($data)) {
            return '';
        }

        $htmlsurround = self::sanitize_htmlsurround($htmlsurround);

        $output = '';
        $output .= '<' . $htmlsurround;
        if (!empty($arialabel)) {
            $output .= ' aria-label="' . trim(esc_attr($arialabel)) . '"';
        }
        if (!empty($class)) {
            $output .= ' class="' . trim(esc_attr($class)) . '"';
        }
        $output .= '>';
        $output .= '<ul>';
        foreach ($data as $name => $value) {
            if (preg_match('/^https?:\/\//i', $value)) {
                $displayValue = preg_replace('/^https?:\/\//i', '', $value);
                $formattedValue = '<a href="' . esc_url($value) . '" itemprop="url">' . esc_html($displayValue) . '</a>';
                $output .= '<li><span class="website title">' . ucfirst(esc_html($name)) . ': </span>' . $formattedValue . '</li>';
            } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $formattedValue = '<a itemprop="email" href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
                $output .= '<li><span class="email title">' . ucfirst(esc_html($name)) . ': </span>' . $formattedValue . '</li>';
            } else {
                $formattedValue = '<span class="value">' . esc_html($value) . '</span>';
                $output .= '<li><span class="title">' . ucfirst(esc_html($name)) . ': </span>' . $formattedValue . '</li>';
            }
        }
        $output .= '</' . $htmlsurround . '>';
        return $output;
    }

    private static function sanitize_htmlsurround(string $htmlsurround): string {
        $allowed_tags = ['div', 'span', 'nav', 'p'];
        $htmlsurround = strtolower(trim($htmlsurround));
        return in_array($htmlsurround, $allowed_tags, true) ? $htmlsurround : 'div';
    }

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

    public function getSocialString(): string {
        if (empty($this->socials)) {
            return __('No social media available', 'rrze-faudir');
        }
        $formattedSocials = [];
        foreach ($this->socials as $social) {
            if (!empty($social['platform']) && !empty($social['url'])) {
                $formattedSocials[] = ucfirst($social['platform']) . ': ' . $social['url'];
            }
        }
        return implode("\n", $formattedSocials);
    }


    /**
     * Kurzer Hinweis „nach Vereinbarung“ als HTML – delegiert an OpeningHours.
     * @return string|null
     */
    public function getConsultationbyAggreement(): ?string {
        return $this->openingHours ? $this->openingHours->getConsultationbyAggreement() : '';
    }

    /**
     * Rendert Öffnungs-/Sprechzeiten als semantisches HTML – delegiert an OpeningHours.
     * @param string $key 'consultationHours' (Standard) oder 'officeHours'
     * @param bool   $withaddress Adresse darunter anhängen (aus Organization)
     * @param string $lang Sprachcode ('de' Standard)
     * @param bool   $showmap FAU-Map-Link in Adressblock ergänzen
     * @return string
     */
    public function getConsultationsHours(string $key = 'consultationHours',bool $withaddress = true,string $lang = 'de', bool $showmap = false): string {
        if (!$this->openingHours) {
            return '';
        }
        $addressHtml = '';
        if ($withaddress) {
            // Org-Adressblock (ohne Langnamen-Präfix)
            $addressHtml = $this->getAddressOutput(false, $lang, $showmap) ?? '';
        }
        return $this->openingHours->getConsultationsHours($key, $addressHtml, $lang);
    }
}
