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
    public array $content = [];          
    protected ?Config $config = null;
    
    
    public function __construct(array $data = []) {
        $this->resetFields();
        $this->fromArray($data, false);
    }

    public function toArray(): array {
        return [
            '@context' => $this->context,
            '@id' => $this->id,
            '@type' => $this->type,
            'identifier' => $this->identifier,
            'disambiguatingDescription' => $this->disambiguatingDescription,
            'longDescription' => $this->longDescription,
            'name' => $this->name,
            'alternateName' => $this->alternateName,
            'additionalType' => $this->additionalType,
            'address' => $this->address,
            'postalAddress' => $this->postalAddress,
            'internalAddress' => $this->internalAddress,
            'parentOrganization' => $this->parentOrganization,
            'subOrganization' => $this->subOrganization,
            'content' => $this->content,
        ];
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
        // Hole die ORG-Daten als Array über die API-Methode.
        $data = $api->getOrgById($identifier);

        if (empty($data) || !is_array($data)) {
            return false;
        }
        
        $this->fromArray($data);
      //  error_log("FAUdir\Organization (getOrgbyAPI): Got org data by {$identifier}.");
        return true;
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
        
        // Hole die ORG-Daten als Array über die API-Methode.
        $data = $api->getOrgList(0, 0, ['lq' => 'disambiguatingDescription[eq]=' . $orgnr]);

        if (empty($data) || !is_array($data)) {
            return false;
        }
        if (isset($data['data'][0])) {
            $this->fromArray($data['data'][0]);
        } elseif (!isset($data['data']) && !empty($data)) {
           $this->fromArray($data);
        }
        
      //  error_log("FAUdir\Organization (getOrgbyOrgnr): Got org data by {$orgnr}.");
        return true;
    }    
    /*
     * Create Address as String
     */
    
    function getAdressString(): string {
        if (empty($this->address)) {
            return __('No address available', 'rrze-faudir');
        }

        $address = $this->address;

        // Format address into a string
        $addressDetails = [];

        if (!empty($address['phone'])) {
            $addressDetails[] = __('Phone', 'rrze-faudir').': ' . $address['phone'];
        }
        if (!empty($address['mail'])) {
            $addressDetails[] = __('Email', 'rrze-faudir').': ' . $address['mail'];
        }
        if (!empty($address['url'])) {
            $addressDetails[] = __('URL', 'rrze-faudir').': ' . $address['url'];
        }
        if (!empty($address['street'])) {
            $addressDetails[] = __('Street', 'rrze-faudir').': ' . $address['street'];
        }
        if (!empty($address['zip'])) {
            $addressDetails[] = __('ZIP Code', 'rrze-faudir').': ' . $address['zip'];
        }
        if (!empty($address['city'])) {
            $addressDetails[] = __('City', 'rrze-faudir').': ' . $address['city'];
        }
        if (!empty($address['faumap'])) {
            $addressDetails[] = __('FAU Map', 'rrze-faudir').': ' . $address['faumap'];
        }

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
    
    // Sanitize Org Identifier, also in case of using url to faudir instead
    public static function sanitizeOrgIdentifier(string $input): ?string {
        if (preg_match('/^[a-z0-9]+$/', $input)) {
            return $input;
        }
        
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            $parts = explode('/', rtrim($input, '/'));
            $lastSegment = end($parts);

            // Validieren, ob letzter Teil ein gültiger orgid ist
            if (preg_match('/^[a-z0-9]+$/', $lastSegment)) {
                return $lastSegment;
            }
            // ggf bereinigen, wenn da noch anderes drin ist
            return preg_replace('/[^a-z0-9]/', '', strtolower($lastSegment));
        }
        // Anderer String: Bereinigen
        return preg_replace('/[^a-z0-9]/', '', strtolower($input));
    }
    

     /*
     * Generate Postal Address Output
     */       

    public function getAddressOutput(bool $orgname = false, string $lang = "de", bool $showmap = false): ?string {
        $address = $result = '';

        if ($orgname) {
            $address .= $this->getName(true, $lang);  
        }
        $workplace = $this->address;
        
        if ($showmap) {
            
            if (!empty($workplace['faumap'])) {
                $map = '';        
                if (($showmap) && (!empty($workplace['faumap']))) {
                    if (preg_match('/^https?:\/\/karte\.fau\.de/i', $workplace['faumap'])) {  
                        $formattedValue = '<a href="' . esc_url($workplace['faumap']) . '" itemprop="hasMap" content="' . esc_url($workplace['faumap']) . '">' .__('FAU Map','rrze-faudir'). '</a>';
                        $map = '<span class="faumap">'.__('Map','rrze-faudir').': '.$formattedValue.'</span>';
                    }
                }

                if (!empty($map)) {
                    $address .= '<span class="roomfloor" itemprop="containedInPlace" itemscope itemtype="https://schema.org/Room">';
                    $address .= $map;
                    $address .= '</span>'; 
                }
            }

           
        }
        
        if (!empty($workplace['street'])) {
            $address .= '<span class="street" itemprop="streetAdress">'.esc_html($workplace['street']).'</span>';    
        }
        if (!empty($workplace['postOfficeBoxNumber'])) {
            $address .= '<span class="postbox"><span class="screen-reader-text">'.__('Box Number', 'rrze-faudir').': </span><span itemprop="postOfficeBoxNumber">'.esc_html($workplace['postOfficeBoxNumber']).'</span></span>';    
        }
        
        if ((!empty($workplace['zip'])) &&(!empty($workplace['postalCode'])) && (!empty($workplace['addressLocality']) || (!empty($workplace['city'])))) {
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
        if ((!empty($workplace['zip'])) && (!empty($workplace['postalCode'])) && (!empty($workplace['addressLocality']) || !empty($workplace['city']))) {    
            $address .= '</span>';
        }
        if (!empty($workplace['addressCountry'])) {
            $address .= '<span class="addressCountry" itemprop="addressCountry">'.esc_html($workplace['addressCountry']).'</span>';    
        }
        
        
        if (!empty($address)) {
            $address = '<span class="texticon" itemprop="address" itemscope="" itemtype="https://schema.org/PostalAddress">'.$address.'</span>';                  
            $result = '<div class="workplace-address">' . $address . '</div>';
        }
        return $result;
    }
    
    
     /*
     * Get Phone Number
     */       
    public function getPhone(): ?string {       
        $phone = '';
        if (!empty($this->address)) {
            if (!empty($this->address['phone'])) {
                $phone = $this->address['phone'];
            }
        }
        
        return $phone;
    }
     /*
     * Get Fax Number
     */       
    public function getFax(): ?string {       
        $phone = '';
        if (!empty($this->address)) {
            if (!empty($this->address['fax'])) {
                $phone = $this->address['fax'];
            }
        }
        
        return $phone;
    }
     
     /*
     * Get E-Mail 
     */       
    public function getEMail(): ?string {       
        $mail = '';
        if (!empty($this->address)) {
            if (!empty($this->address['mail'])) {
                $mail = $this->address['mail'];
            }
        }
        
        return $mail;
    }
    
    
     /*
     * Get Name 
     */       
    public function getName(bool $uselongdesc = false, string $lang = 'de'): string {       
        $name = '';
        if ($uselongdesc) {
            if (isset($this->longDescription[$lang])) {
                $name = $this->longDescription[$lang];
            }
        }
        if (empty($name)) {
            $name = $this->name;
        }
        
        return $name;
    }
                
    
    /*
     * get text entry from content
     */
    public function getContentText(string $lang = 'de'): string {       
        if (empty($this->content)) {
            return '';
        }
        $content = $this->content;
        $res = '';
        foreach ($this->content as $content) {
            if (isset($content['type']) && ($content['type']== 'text')) {
                if (isset($content['text'][$lang])) {
                    $res = $content['text'][$lang];
                }
            }
        }
        return $res;

    }
    
    
     /*
     * Get URL for Org
     */       
    public function getURL(bool $fallbackfaudir = true): string {       
        $cpt_url = '';
        if (!empty($this->address)) {
            if (!empty($this->address['url'])) {
                $cpt_url = $this->address['url'];
            }
        }
        
        
        if ((empty($cpt_url)) && ($fallbackfaudir)) {
            if (empty($this->config)) {
                $this->setConfig();
            }
            $cpt_url = $this->config->get('faudir-url').'public/org/'.$this->identifier;
        }                
        return $cpt_url;                       
    }
    
     /*
     * Get a random identifier; Used for aria-labelledby if more entries 
     * of the same person is displayed on the same page
     */
    public function getRandomId(string $prefix = ''): string {
        $res = '';        
        if (!empty($prefix)) {
            $res = esc_attr($prefix);
        }
        $only_numbers = filter_var($this->identifier, FILTER_SANITIZE_NUMBER_INT);
        $res .= $only_numbers.'-'.wp_rand(1000,5000);
        
        return $res;
    }
    
}
