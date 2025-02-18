<?php

/*
 * Person Class
 * Handles data for a single person
 */

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;


class Person {
    public string $identifier;
    public string $givenName;
    public string $familyName;
    public ?string $personalTitle;
    public ?string $personalTitleSuffix;
    public ?string $titleOfNobility;   
    public ?string $pronoun;
    public ?string $email;
    public ?string $telephone;
    public ?array $contacts;
    private array $rawdata;
    protected ?Config $config = null;
    private ?int $postid;
    
    
    public function __construct(array $data = []) {
        $this->identifier = $data['identifier'] ?? '';
        $this->givenName = $data['givenName'] ?? '';
        $this->familyName = $data['familyName'] ?? '';    
        $this->personalTitle = $data['personalTitle'] ?? '';
        $this->personalTitleSuffix = $data['personalTitleSuffix'] ?? '';
        $this->titleOfNobility = $data['titleOfNobility'] ?? '';       
        $this->pronoun = $data['pronoun'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->telephone = $data['telephone'] ?? '';         
        $this->contacts = $data['contacts'] ?? null;
        $this->postid = $data['postid'] ?? 0;
        

        // Everything else that comes over data move in rawdata       
        $usedKeys = [
            'identifier',
            'givenName',
            'familyName',
            'personalTitle',
            'personalTitleSuffix',
            'titleOfNobility',
            'pronoun',
            'email',
            'telephone',
            'contacts',
            'postid'
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
            $this->identifier          = '';
            $this->givenName           = '';
            $this->familyName          = '';
            $this->personalTitle       = '';
            $this->personalTitleSuffix = '';
            $this->titleOfNobility     = '';
            $this->pronoun             = '';
            $this->email               = '';
            $this->telephone           = '';
            $this->postid              = 0;
            $this->contacts            = null;

            // Leere rawdata zurücksetzen
            $this->rawdata = [];
        }
        
        // Aktualisiere die einzelnen Eigenschaften, falls Werte vorhanden sind
        if (isset($data['identifier'])) {
            $this->identifier = $data['identifier'];
        }
        if (isset($data['givenName'])) {
            $this->givenName = $data['givenName'];
        }
        if (isset($data['familyName'])) {
            $this->familyName = $data['familyName'];
        }
        if (isset($data['personalTitle'])) {
            $this->personalTitle = $data['personalTitle'];
        }
        if (isset($data['personalTitleSuffix'])) {
            $this->personalTitleSuffix = $data['personalTitleSuffix'];
        }
        if (isset($data['titleOfNobility'])) {
            $this->titleOfNobility = $data['titleOfNobility'];
        }
        if (isset($data['pronoun'])) {
            $this->pronoun = $data['pronoun'];
        }
        if (isset($data['email'])) {
            $this->email = $data['email'];
        }
        if (isset($data['telephone'])) {
            $this->telephone = $data['telephone'];
        }
        if (isset($data['contacts'])) {
            $this->contacts = $data['contacts'];
        }
        if (isset($data['postid'])) {
            $this->postid = $data['postid'];
        }

        // Aktualisiere rawdata: Füge alle übrigen Schlüssel hinzu, die nicht zu den Standardfeldern gehören.
        $usedKeys = [
            'identifier',
            'givenName',
            'familyName',
            'personalTitle',
            'personalTitleSuffix',
            'titleOfNobility',
            'pronoun',
            'email',
            'telephone',
            'contacts',
            'postid'
        ];
        $remaining = array_diff_key($data, array_flip($usedKeys));
        $this->rawdata = array_merge($this->rawdata, $remaining);
    }
    
    
    /*
     * Personendaten als Array zurückliefern
     */
    public function toArray(): array {
        $data = [
            'identifier'           => $this->identifier,
            'givenName'            => $this->givenName,
            'familyName'           => $this->familyName,
            'personalTitle'        => $this->personalTitle,
            'personalTitleSuffix'  => $this->personalTitleSuffix,
            'titleOfNobility'      => $this->titleOfNobility,
            'pronoun'              => $this->pronoun,
            'email'                => $this->email,
            'telephone'            => $this->telephone,
            'contacts'             => $this->contacts,
            'postid'                => $this->postid,
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
     * Hole eine Person via Identifier von der API
     */
    public function getPersonbyAPI(string $identifier): bool {
        if (empty($this->config)) {
            $this->setConfig();
        }
        $api = new API($this->config);
        // Hole die Personendaten als Array über die API-Methode.
        $personData = $api->getPerson($identifier);

        if (empty($personData) || !is_array($personData)) {
            return false;
        }
        
        $this->populateFromData($personData);
        error_log("FAUdir\Person (getPersonbyAPI): Got person data by {$identifier}.");
        return true;
    }
    
    
    /*
     * Contact-Daten befüllen
     */
    public function reloadContacts(bool $loadorg = false): bool {
        $personContacts = [];
        
        // Falls keine Kontakte gesetzt sind, nichts tun
        if (empty($this->contacts) || !is_array($this->contacts)) {
            error_log("FAUdir\Person (reloadContacts): No Contact data for Person.");
            return false;
        }
        if (empty($this->config)) {
            $this->setConfig();
        }
        
        // Erstelle eine API-Instanz unter Verwendung der Konfiguration der Instanz
        $api = new API($this->config);
        
        // Iteriere über die Kontakte
        foreach ($this->contacts as $contact) {
            $contactIdentifier = $contact['identifier'] ?? null;
            if ($contactIdentifier) {                  
                $contactData = $api->getContacts(0, 0, ['identifier' => $contactIdentifier]);
                
                if (!empty($contactData['data'])) {
                    $contact = $contactData['data'][0];
                    $organizationId = $contact['organization']['identifier'] ?? null;

                    if (($organizationId) && ($loadorg)) {
                        // Wozu brauchen wir die Orgdaten eigentlich?
                        // Addresse kommt doch aus dem Workplaces...
                        
                        $organizationData = $api->getOrgById($organizationId);
                        
                        
                        if (!empty($organizationData['address'])) {
                             $contact['org']['address'] = $organizationData['address'];                 
                        }
                        if (!empty($organizationData['identifier'])) {
                             $contact['org']['identifier'] = $organizationData['identifier'];                 
                        }
                        if (!empty($organizationData['longDescription'])) {
                             $contact['org']['longDescription'] = $organizationData['longDescription'];                 
                        }
                        if (!empty($organizationData['name'])) {
                             $contact['org']['name'] = $organizationData['name'];                 
                        }
                        if (!empty($organizationData['disambiguatingDescription'])) {
                             $contact['org']['disambiguatingDescription'] = $organizationData['disambiguatingDescription'];                 
                        }
                        if (!empty($organizationData['parentOrganization'])) {
                             $contact['org']['parentOrganization'] = $organizationData['parentOrganization'];                 
                        }
                        if (!empty($organizationData['subOrganization'])) {
                             $contact['org']['subOrganization'] = $organizationData['subOrganization'];                 
                        }
                    
                        
                    }

                    $personContacts[] = $contact;
                }
            }
        }
        error_log("FAUdir\Person (reloadContacts): Populated Person with all avaible contactdata.");
        $this->contacts = $personContacts;
        return true;
    }
    
    /*
     * Get Workplaces of Person as Array
     */
    public function getWorkplaces(): ?array {
         // zuerst hole Primary Contact, falls vorhanden
        $contact = $this->getPrimaryContact();
        if ($contact) {
            return $contact->getWorkplaces();
                       
        }
        return null;          
    }
    
    
    public function getDisplayName(bool $html = true, bool $hard_sanitize = false, array $show = [], array $hide = []): string {
        if (empty($this->givenName) && empty($this->titleOfNobility) && empty($this->familyName) && empty($this->personalTitle)) {
            return '';
        }
        $nameText = '';
        $nameHtml  = '';

        // Wrapper für HTML-Ausgabe
        $nameHtml .= '<span class="displayname" itemprop="name">';

        // Hilfsfunktion: Überprüft, ob ein Feld ausgegeben werden soll.
        // Falls $hide nicht leer ist und der Schlüssel darin enthalten ist, oder
        // falls $show nicht leer ist und der Schlüssel nicht enthalten ist, wird FALSE zurückgegeben.
        $shouldOutput = function(string $fieldKey) use ($show, $hide): bool {
            if (!empty($hide) && in_array($fieldKey, $hide, true)) {
                return false;
            }
            if (!empty($show) && !in_array($fieldKey, $show, true)) {
                return false;
            }
            return true;
        };

        // "personalTitle"
        if (!empty($this->personalTitle) && $shouldOutput('personalTitle')) {
            if ($hard_sanitize) {
                $long_version = self::getAcademicTitleLongVersion($this->personalTitle);
                if (!empty($long_version)) {
                    $nameHtml .= '<abbr title="' . esc_attr($long_version) . '" itemprop="honorificPrefix">' 
                                . esc_html($this->personalTitle) . '</abbr> ';
                } else {
                    $nameHtml .= '<span itemprop="honorificPrefix">' . esc_html($this->personalTitle) . '</span> ';
                }
            } else {
                $nameHtml .= '<span itemprop="honorificPrefix">' . esc_html($this->personalTitle) . '</span> ';
            }
            $nameText .= esc_html($this->personalTitle) . ' ';
        }

        // "givenName"
        if (!empty($this->givenName) && $shouldOutput('givenName')) {
            $nameHtml .= '<span itemprop="givenName">' . esc_html($this->givenName) . '</span> ';
            $nameText .= esc_html($this->givenName) . ' ';
        }

        

        // "familyName"
        if (!empty($this->familyName) && $shouldOutput('familyName')) {
            
            $nameHtml .= '<span itemprop="familyName">';
            
            // "titleOfNobility" is part of the familyName
            if (!empty($this->titleOfNobility) && $shouldOutput('titleOfNobility')) {
                $nameHtml .= esc_html($this->titleOfNobility) . ' ';
                $nameText .= esc_html($this->titleOfNobility) . ' ';
            }
             
            $nameHtml .= esc_html($this->familyName) . '</span> ';
            $nameText .= esc_html($this->familyName) . ' ';
        }

        // "personalTitleSuffix"
        if (!empty($this->personalTitleSuffix) && $shouldOutput('personalTitleSuffix')) {
            $nameHtml .= '(<span itemprop="honorificSuffix">' . esc_html($this->personalTitleSuffix) . '</span>)';
            $nameText .= '(' . esc_html($this->personalTitleSuffix) . ')';
        }

        $nameHtml .= '</span>';

        return $html ? $nameHtml : $nameText;
    }  
    
    public function getRawData(): array {
        return $this->rawdata;
    }
    
    
     public function getPostId(): int {
        $contact_posts = get_posts([
            'post_type' => 'custom_person',
            'meta_key' => 'person_id',
            'meta_value' => $this->identifier,
            'posts_per_page' => 1, // Only fetch one post matching the person ID
        ]);
        if (!empty($contact_posts)) {
            $postid = $contact_posts[0]->ID;
            $this->postid = $postid;
            return $postid;
        }
        return 0;               
    }
    
    public function getTargetURL(): string {       
        if (empty($this->postid)) {
            $postid = $this->getPostId();
        } else {
            $postid = $this->postid;
        }
        $cpt_url = '';
        if ($this->postid !== 0) {
            $cpt_url  =  get_permalink($postid); 
        }
                       
        return $cpt_url;                       
    }
    
    public function getPrimaryContact(): ?Contact {       
        // Wenn es keinen Contact-Array gibt, dann gibt es kein Contact
        if (empty($this->contacts)) {
            return false;
        }
        
        // Wenn es nur einen einzigen Contacteintrag gibt, dann returne diesen        
        if (count($this->contacts)==1) {
            return new Contact($this->contacts[0]);
        }
        
        // get primary contact
        if (empty($this->postid)) {
            $postid = $this->getPostId();
        } else {
            $postid = $this->postid;
        }
        
        if ($postid === 0) {
            // No custum post entry, therfor i take the first entry
            return new Contact($this->contacts[0]);            
        }        
        
        $displayed_contacts = get_post_meta($postid, 'displayed_contacts', true);
        
        // downwardcompatbility
        if (is_array($displayed_contacts)) {
            $displayed_contacts = 0;
        }
    
        if (isset($this->contacts[$displayed_contacts])) {
             return new Contact($this->contacts[$displayed_contacts]);
        } else {
            return new Contact($this->contacts[0]);            
        }
                            
    }
    
    
    public function getView(?string $template = null): string {
        if (empty($template)) {
            $template = "Name : #displayname#".PHP_EOL;
        }
        

        // Platzhalter mit den entsprechenden Werten ersetzen
        $replacements = [
            '#identifier#' => $this->identifier ?? '',
            '#givenName#' => $this->givenName ?? '',
            '#displayname#' => $this->getDisplayName() ?? '',
            '#familyName#' => $this->familyName ?? '',
            '#personalTitle#' => $this->personalTitle ?? '',
            '#personalTitleSuffix#' => $this->personalTitleSuffix ?? '',
            '#titleOfNobility#' => $this->titleOfNobility ?? ''
        ];

                
        return str_replace(array_keys($replacements), array_values($replacements), $template);

    }
    private static function getAcademicTitleLongVersion(string $prefix): string  {
        $prefixes = array(
            'Dr.' => __('Doctor', 'rrze-faudir'),
            'Prof.' => __('Professor', 'rrze-faudir'),
            'Prof. Dr.' => __('Professor Doctor', 'rrze-faudir'),
            'Prof. em.' => __('Professor (Emeritus)', 'rrze-faudir'),
            'Prof. Dr. em.' => __('Professor Doctor (Emeritus)', 'rrze-faudir'),
            'PD' => __('Private lecturer', 'rrze-faudir'),
            'PD Dr.' => __('Private lecturer doctor', 'rrze-faudir')
        );

        return isset($prefixes[$prefix]) ? $prefixes[$prefix] : '';
    }
  
}
