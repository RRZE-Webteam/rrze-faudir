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
    public ?string $honorificPrefix;
    public ?string $honorificSuffix;
    public ?string $titleOfNobility;   
    public ?string $pronoun;
    public ?string $email;
    public ?string $telephone;
    public ?array $contacts;
    private array $rawdata;
    protected ?Config $config = null;
    private ?int $postid;
    private ?Contact $primary_contact;
    
    public function __construct(array $data = []) {
        $this->identifier = $data['identifier'] ?? '';
        $this->givenName = $data['givenName'] ?? '';
        $this->familyName = $data['familyName'] ?? '';    
        $this->honorificPrefix = $data['honorificPrefix'] ?? '';
        $this->honorificSuffix = $data['honorificSuffix'] ?? '';
        $this->titleOfNobility = $data['titleOfNobility'] ?? '';       
        $this->pronoun = $data['pronoun'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->telephone = $data['telephone'] ?? '';         
        $this->contacts = $data['contacts'] ?? null;
        $this->postid = $data['postid'] ?? 0;
        $this->primary_contact = null;

        // Everything else that comes over data move in rawdata       
        $usedKeys = [
            'identifier',
            'givenName',
            'familyName',
            'honorificPrefix',
            'honorificSuffix',
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
            $this->honorificPrefix       = '';
            $this->honorificSuffix      = '';
            $this->titleOfNobility     = '';
            $this->pronoun             = '';
            $this->email               = '';
            $this->telephone           = '';
            $this->postid              = 0;
            $this->contacts            = null;
            $this->primary_contact     = null;
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
            $this->honorificPrefix = $data['personalTitle'];
        }
         if (isset($data['personalTitleSuffix'])) {
            $this->honorificSuffix = $data['personalTitleSuffix'];
        }
        if (isset($data['honorificSuffix'])) {
            $this->honorificSuffix = $data['honorificSuffix'];
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
            'honorificPrefix',
            'honorificSuffix',
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
            'identifier'            => $this->identifier,
            'givenName'             => $this->givenName,
            'familyName'            => $this->familyName,
            'honorificPrefix'       => $this->honorificPrefix,
            'honorificSuffix'       => $this->honorificSuffix,
            'titleOfNobility'       => $this->titleOfNobility,
            'pronoun'               => $this->pronoun,
            'email'                 => $this->email,
            'telephone'             => $this->telephone,
            'contacts'              => $this->contacts,
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
       //  error_log("FAUdir\Person (getPersonbyAPI): Got person data by {$identifier}.");
        return true;
    }
    
    
    /*
     * Contact-Daten befüllen
     */
    public function reloadContacts(bool $loadorg = false): bool {
        $personContacts = [];
        
        // Falls keine Kontakte gesetzt sind, nichts tun
        if (empty($this->contacts) || !is_array($this->contacts)) {
      //      error_log("FAUdir\Person (reloadContacts): No Contact data for Person.");
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
        // error_log("FAUdir\Person (reloadContacts): Populated Person with all avaible contactdata.");
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
    
    /*
     * Get Phone Numbers for person
     * - if $person->telephone is set, use this.
     * - if its empty use the phones address from active primary contact
     * Cause a person can use more as one phone number, we result with an array
     */ 
    public function getPhone(): ?array {
        $resphone = [];
        if (!empty($this->telephone)) {
            if ((is_string($this->telephone)) && (preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $this->telephone)) ) {
                $resphone[] = $this->telephone;
            } elseif (is_array($this->telephone)) {           
                foreach ($this->telephone as $i => $val) {
                    if (preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $this->telephone)) {
                        $resphone[] = $val;
                    }
                }
            }
            return $resphone;
        }
        $workplaces = $this->getWorkplaces();
        if (empty($workplaces)) {
            return [];
        }
        
        
        $gatherphones = [];     
        foreach ($workplaces as $num => $wdata) {
            if (isset($wdata['phones'])) {
                foreach ($wdata['phones'] as $i => $val) {
                    if (preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $val)) {
                        $gatherphones[] = $val;
                    }
                 
                }
            }
        }
        if (!empty($gatherphones)) {
            // Entferne etwaige Dupletten und returne dann die neu indizierte Liste
            return array_values(array_unique($gatherphones));
        }
        return [];
    }
    /*
     * Get Email address for person
     * - if $person->email is set, use this.
     * - if its empty use the email address from active primary contact
     * Cause a person can use more as one mail adress, we result with an array
     */ 
    public function getEMail(): ?array {
        $resmail = [];
        if (!empty($this->email)) {
            if ((is_string($this->email)) && (filter_var($this->email, FILTER_VALIDATE_EMAIL))) {
                $resmail[] = $this->email;
            } elseif (is_array($this->email)) {           
                foreach ($this->email as $i => $val) {
                    if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        $resmail[] = $val;
                    }
                }
            }
            return $resmail;
        }
        $workplaces = $this->getWorkplaces();
        if (empty($workplaces)) {
            return [];
        }
        
        
        $gathermails = [];     
        foreach ($workplaces as $num => $wdata) {
            if (isset($wdata['mails'])) {
                foreach ($wdata['mails'] as $i => $val) {
                    if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        $gathermails[] = $val;
                    }
                 
                }
            }
        }
        if (!empty($gathermails)) {
            // Entferne etwaige Dupletten und returne dann die neu indizierte Liste
            return array_values(array_unique($gathermails));
        }
        return [];
    }
    
    
    
    /*
     * Create and get Displayname in semantic HTML
     */
    public function getDisplayName(bool $html = true, bool $hard_sanitize = false, string $format = '', array $show = [], array $hide = []): string {
        if (empty($this->givenName) && empty($this->familyName)) {
            return '';
        }
        $nameText = '';
        $nameHtml  = '';

        if (empty($format)) {
            // Wrapper für HTML-Ausgabe
            $nameHtml .= '<span class="displayname" itemprop="name">';

            // "personalTitle"
            if ((!empty($this->honorificPrefix)) && (!in_array('honorificPrefix', $hide))) {
                if ($hard_sanitize) {
                    $long_version = self::getAcademicTitleLongVersion($this->honorificPrefix);
                    if (!empty($long_version)) {
                        $nameHtml .= '<abbr title="' . esc_attr($long_version) . '" itemprop="honorificPrefix">' 
                                    . esc_html($this->honorificPrefix) . '</abbr> ';
                    } else {
                        $nameHtml .= '<span itemprop="honorificPrefix">' . esc_html($this->honorificPrefix) . '</span> ';
                    }
                } else {
                    $nameHtml .= '<span itemprop="honorificPrefix">' . esc_html($this->honorificPrefix) . '</span> ';
                }
                $nameText .= esc_html($this->honorificPrefix) . ' ';
            }

            // "givenName"
            if ((!empty($this->givenName))  && (!in_array('givenName', $hide))) { 
                $nameHtml .= '<span itemprop="givenName">' . esc_html($this->givenName) . '</span> ';
                $nameText .= esc_html($this->givenName) . ' ';
            }



            // "familyName"
            if ((!empty($this->familyName))  && (!in_array('familyName', $hide))) { 
                $nameHtml .= '<span itemprop="familyName">';        
                // "titleOfNobility" is part of the familyName
                if ((!empty($this->titleOfNobility))  && (!in_array('titleOfNobility', $hide))) { 
                    $nameHtml .= esc_html($this->titleOfNobility) . ' ';
                    $nameText .= esc_html($this->titleOfNobility) . ' ';
                }

                $nameHtml .= esc_html($this->familyName) . '</span> ';
                $nameText .= esc_html($this->familyName) . ' ';
            }

            // "personalTitleSuffix"
            if ((!empty($this->honorificSuffix))  && (!in_array('honorificSuffix', $hide))) { 
                $nameHtml .= '(<span itemprop="honorificSuffix">' . esc_html($this->honorificSuffix) . '</span>)';
                $nameText .= '(' . esc_html($this->honorificSuffix) . ')';
            }

            $nameHtml .= '</span>';

            return $html ? $nameHtml : $nameText;
        }
        
        // format string is given. In this case display the name parts in the form that is displayed in the format
        // add nor semantic nor commas or other signs

        
        
        $nameHtml = '';
        if ($html) {
            $nameHtml = '<span class="displayname" itemprop="name">';
        }
        
        // Platzhalter mit den entsprechenden Werten ersetzen
        $replacements = [
            '#givenName#' => $this->givenName ?? '',
            '#displayname#' => $this->getDisplayName(false,false) ?? '',
            '#familyName#' => $this->familyName ?? '',
            '#honorificPrefix#' => $this->honorificPrefix ?? '',
            '#honorificSuffix#' => $this->honorificSuffix ?? '',
            '#titleOfNobility#' => $this->titleOfNobility ?? ''
        ]; 
        $nameHtml .= str_replace(array_keys($replacements), array_values($replacements), $format);
        if ($html) {
            $nameHtml .= '</span>';
        }
        return $nameHtml;
    }  
    
    
    /*
     * Create signature of a person
     */
    public function getSignature(): string {
        if (empty($this->givenName) && empty($this->familyName)) {
            return '';
        }
        $firstLetter = $middleLetter = $lastLetter = $res = '';
        
        if (!empty($this->givenName)) {
            $firstLetter = mb_substr($this->givenName, 0, 1, 'UTF-8'); 
            $res =  mb_strtoupper($firstLetter, 'UTF-8');
        }
        if (!empty($this->titleOfNobility)) {
            $middleLetter = mb_substr($this->titleOfNobility, 0, 1, 'UTF-8'); 
            $res .=  mb_strtolower($middleLetter, 'UTF-8');
        }
        if (!empty($this->familyName)) {          
            $lastLetter = mb_substr($this->familyName, 0, 1, 'UTF-8'); 
            $res .= mb_strtoupper($lastLetter, 'UTF-8');
        }

        return $res;        
    }  
    
    
    
    /*
     * Get Raw Data, Data that is not avaible as direct attribut of the object
     */
    public function getRawData(): array {
        return $this->rawdata;
    }
    
    /*
     * Get Post Id for a person
     */
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
    
    
    /*
     * Get permalink for custom post type
     */       
    public function getTargetURL(bool $fallbackfaudir = true): string {       
        if (empty($this->postid)) {
            $postid = $this->getPostId();
        } else {
            $postid = $this->postid;
        }
        $cpt_url = '';
        if ($postid !== 0) {
            $cpt_url  =  get_permalink($postid); 
        }
        if ((empty($cpt_url)) && ($fallbackfaudir)) {
            if (empty($this->config)) {
                $this->setConfig();
            }
            $cpt_url = $this->config->get('faudir-url').'public/person/'.$this->identifier;
        }                
        return $cpt_url;                       
    }
    
    
    /*
     * Get Content from custom post
     */
    public function getContent(string $lang = 'de'): string {
         if (empty($this->postid)) {
            $postid = $this->getPostId();
        } else {
            $postid = $this->postid;
        }
        if ($postid !== 0) {
            if ($lang === 'de') {
                $raw_content = get_post_field('post_content', $postid);
                $content   = apply_filters('the_content', $raw_content);
            } else {
                $raw_content = get_post_meta($postid, '_content_en', true);
                $content   = apply_filters('the_content', $raw_content);
            }
            return $content;
        }             
        return '';  
  
    }
    
    
    /*
     * Get teasertext
     */       
    public function getTeasertext(string $lang = 'de'): string {       
        if (empty($this->postid)) {
            $postid = $this->getPostId();
        } else {
            $postid = $this->postid;
        }
        if ($postid !== 0) {
            if ($lang === 'de') {
                $teaser_text_key = '_teasertext_de';
            } else {
                $teaser_text_key = '_teasertext_en';
            }
            return get_post_meta($postid, $teaser_text_key, true);
        }
                       
        return '';                       
    }
    
    /*
     * Get Image as HTML or Replacement
     */
    public function getImage(string $css_classes = '', string $figcaption = '', bool $signature = true): string {
         if (empty($this->postid)) {
            $postid = $this->getPostId();
        } else {
            $postid = $this->postid;
        }
        if ($postid !== 0) {
            $thumb_id = get_post_thumbnail_id( $postid );
             // Erhalte den Bildpfad, die Breite und Höhe des Bildes in der Größe "full" (oder einer anderen gewünschten Größe)
             $img_src = wp_get_attachment_image_src( $thumb_id, 'full' );
             if ($img_src ) {
                $src    = $img_src[0];
                $width  = $img_src[1];
                $height = $img_src[2];

                // Hole srcset und sizes, sofern verfügbar
                $srcset = wp_get_attachment_image_srcset( $thumb_id, 'full' );
                $sizes  = wp_get_attachment_image_sizes( $thumb_id, 'full' );
                $alt = $this->getSignature();
                
                $html  = '<figure itemprop="image" itemscope itemtype="http://schema.org/ImageObject"';
                if (!empty($css_classes)) {
                    $html  .= ' class="' . esc_attr( $css_classes ) . '"';
                }
                $html .= '>';
                $html .= '<img src="' . esc_url( $src ) . '" ';
                if (!empty($alt)) {
                    $html .= 'alt="' . esc_attr( $alt ) . '" ';
                }
                $html .= 'width="' . esc_attr( $width ) . '" ';
                $html .= 'height="' . esc_attr( $height ) . '" ';
                if ( $srcset ) {
                    $html .= 'srcset="' . esc_attr( $srcset ) . '" ';
                }
                if ( $sizes ) {
                    $html .= 'sizes="' . esc_attr( $sizes ) . '" ';
                }
                $html .= 'itemprop="contentUrl">';
                $html .= '<meta itemprop="width" content="' . esc_attr( $width ) . '">';
                $html .= '<meta itemprop="height" content="' . esc_attr( $height ) . '">';
                if (!empty($figcaption)) {
                    $html .= '<figcaption itemprop="caption">'.esc_html($figcaption).'</figcaption>';
                }
                $html .= '</figure>';

                return $html;
            }
        }
        
        if ($signature) {
            // No image returned, therefor create the text variant and return this
            $alt = $this->getSignature();
            $html  = '<figure ';
            if (!empty($css_classes)) {
               $css_classes .= ' signature';
            } else {
               $css_classes = 'signature';
            }
            $html  .= ' class="' . esc_attr( $css_classes ) . '"';
            $html .= '>';
            $html .= '<span class="text">'.$alt.'</span>';    
            $html .= '</figure>';
            return $html;
        }
        return '';
        
    }
    
    
    public function getPrimaryContact(): ?Contact {       
        // Wenn es keinen Contact-Array gibt, dann gibt es kein Contact
        if (empty($this->contacts)) {
            return false;
        }
        
        if (!empty($this->primary_contact)) {
            // we already calculated this, therfor we return this
            return $this->primary_contact;
        }
        
        // Wenn es nur einen einzigen Contacteintrag gibt, dann returne diesen        
        if ((count($this->contacts)==1) && (!empty($this->contacts[0]))) {
            $this->primary_contact = new Contact($this->contacts[0]);
            return $this->primary_contact;
        }
        
        // get primary contact
        if (empty($this->postid)) {
            $postid = $this->getPostId();
        } else {
            $postid = $this->postid;
        }
        
        if (($postid === 0) && (!empty($this->contacts[0]))) {
            // No custum post entry, therfor i take the first entry
            $this->primary_contact = new Contact($this->contacts[0]);
            return $this->primary_contact;         
        }        
        
        $displayed_contacts = get_post_meta($postid, 'displayed_contacts', true);
        
        // downwardcompatbility
        if (is_array($displayed_contacts)) {
            $displayed_contacts = 0;
        }
    
        if (isset($this->contacts[$displayed_contacts])) {
            $this->primary_contact = new Contact($this->contacts[$displayed_contacts]);
            return $this->primary_contact;
        } else {
            $this->primary_contact = new Contact($this->contacts[0]);
            return $this->primary_contact;
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
            '#honorificPrefix#' => $this->honorificPrefix ?? '',
            '#honorificSuffix#' => $this->honorificSuffix ?? '',
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
