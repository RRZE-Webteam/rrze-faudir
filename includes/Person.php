<?php

/*
 * Person Class
 * Handles data for a single person
 */

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

use RRZE\FAUdir\FaudirUtils;


class Person {
    public string $identifier;
    public string $givenName;
    public string $familyName;
    public ?string $honorificPrefix;
    public ?string $honorificSuffix;
    public ?string $titleOfNobility;   
    public ?string $pronoun;
    public string|array|null $email;
    public string|array|null $telephone;
    public string|array|null $fax;
    public ?array $contacts;
    private array $rawdata;
    protected ?Config $config = null;
    private ?int $postid;
    private ?Contact $primary_contact;
    
    
    public function __construct(array $data = []) {
        $this->identifier       = (string) ($data['identifier'] ?? '');
        $this->givenName        = (string) ($data['givenName'] ?? '');
        $this->familyName       = (string) ($data['familyName'] ?? '');
        $this->honorificPrefix  = isset($data['honorificPrefix']) ? (string) $data['honorificPrefix'] : '';
        $this->honorificSuffix  = isset($data['honorificSuffix']) ? (string) $data['honorificSuffix'] : '';
        $this->titleOfNobility  = isset($data['titleOfNobility']) ? (string) $data['titleOfNobility'] : '';
        $this->pronoun          = isset($data['pronoun']) ? (string) $data['pronoun'] : '';

        $this->email            = $data['email'] ?? null;
        $this->telephone        = $data['telephone'] ?? null;
        $this->fax              = $data['fax'] ?? null;

        $this->contacts         = isset($data['contacts']) && is_array($data['contacts']) ? $data['contacts'] : null;
        $this->postid           = isset($data['postid']) ? (int) $data['postid'] : 0;
        $this->primary_contact  = null;

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
            $this->identifier          = '';
            $this->givenName           = '';
            $this->familyName          = '';
            $this->honorificPrefix     = '';
            $this->honorificSuffix     = '';
            $this->titleOfNobility     = '';
            $this->pronoun             = '';
            $this->email               = null;
            $this->telephone           = null;
            $this->fax                 = null;
            $this->postid              = 0;
            $this->contacts            = null;
            $this->primary_contact     = null;
            $this->rawdata             = [];
        }

        if (isset($data['identifier'])) {
            $this->identifier = (string) $data['identifier'];
        }
        if (isset($data['givenName'])) {
            $this->givenName = (string) $data['givenName'];
        }
        if (isset($data['familyName'])) {
            $this->familyName = (string) $data['familyName'];
        }

        // Titel: bevorzugt "personalTitle", fallback "honorificPrefix"
        if (isset($data['personalTitle'])) {
            $this->honorificPrefix = (string) $data['personalTitle'];
        } elseif (isset($data['honorificPrefix'])) {
            $this->honorificPrefix = (string) $data['honorificPrefix'];
        }

        // Suffix: bevorzugt "personalTitleSuffix", fallback "honorificSuffix"
        if (isset($data['personalTitleSuffix'])) {
            $this->honorificSuffix = (string) $data['personalTitleSuffix'];
        } elseif (isset($data['honorificSuffix'])) {
            $this->honorificSuffix = (string) $data['honorificSuffix'];
        }

        if (isset($data['titleOfNobility'])) {
            $this->titleOfNobility = (string) $data['titleOfNobility'];
        }
        if (isset($data['pronoun'])) {
            $this->pronoun = (string) $data['pronoun'];
        }
        if (array_key_exists('email', $data)) {
            $this->email = $this->normalizeScalarOrStringArray($data['email']);
        }
        if (array_key_exists('telephone', $data)) {
            $this->telephone = $this->normalizeScalarOrStringArray($data['telephone']);
        }
        if (array_key_exists('fax', $data)) {
            $this->fax = $this->normalizeScalarOrStringArray($data['fax']);
        }
        if (isset($data['contacts'])) {
            $this->contacts = is_array($data['contacts']) ? $data['contacts'] : null;
        }
        if (isset($data['postid'])) {
            $this->postid = (int) $data['postid'];
        }

        // rawdata: alles, was nicht in die bekannten Felder gehört
        $usedKeys = [
            'identifier',
            'givenName',
            'familyName',
            'honorificPrefix',
            'honorificSuffix',
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
    
    private function normalizeScalarOrStringArray(mixed $value): string|array|null {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $v = trim($value);
            return ($v === '') ? null : $v;
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $v) {
                $v = trim((string) $v);
                if ($v !== '') {
                    $out[] = $v;
                }
            }

            if (empty($out)) {
                return null;
            }

            $out = array_values(array_unique($out));
            return $out;
        }

        // alles andere: als String versuchen
        $v = trim((string) $value);
        return ($v === '') ? null : $v;
    }
    /*
     * Personendaten als Array zurückliefern
     */
    public function toArray(): array {
        $data = [
            'identifier'       => $this->identifier,
            'givenName'        => $this->givenName,
            'familyName'       => $this->familyName,
            'honorificPrefix'  => $this->honorificPrefix,
            'honorificSuffix'  => $this->honorificSuffix,
            'titleOfNobility'  => $this->titleOfNobility,
            'pronoun'          => $this->pronoun,
            'email'            => $this->email,
            'telephone'        => $this->telephone,
            'fax'              => $this->fax,
            'contacts'         => $this->contacts,
            'postid'           => $this->postid,
        ];

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
        return true;
    }
    
    
    /*
     * Contact-Daten befüllen
     */
    public function reloadContacts(bool $loadorg = false): bool {
        if (empty($this->contacts) || !is_array($this->contacts)) {
            return false;
        }
        if (empty($this->config)) {
            $this->setConfig();
        }

        $api = new API($this->config);

        $personContacts = [];
        foreach ($this->contacts as $contact) {
            if (empty($contact) || !is_array($contact)) {
                continue;
            }

            /*
             * Wenn Contact schon "voll" ist (z.B. workplaces vorhanden),
             * dann nicht erneut per API laden.
             */
            if (!empty($contact['workplaces']) && is_array($contact['workplaces'])) {
                if ($loadorg) {
                    $contact = $this->enrichContactOrganization($api, $contact);
                }
                $personContacts[] = $contact;
                continue;
            }

            $contactIdentifier = $contact['identifier'] ?? '';                     
            if (!FaudirUtils::isValidContactId($contactIdentifier)) {
                /*
                 * Kein Identifier → wir können nicht nachladen.
                 * Contact so übernehmen, wie er ist.
                 */
                if ($loadorg) {
                    $contact = $this->enrichContactOrganization($api, $contact);
                }
                $personContacts[] = $contact;
                continue;
            }

            $contactData = $api->getContact($contactIdentifier);
            
            do_action('rrze.log.info', "FAUdir\API (getContacts): Getting contact data for {$contactIdentifier}: ", $contactData);
            if (empty($contactData) || !is_array($contactData)) {
                continue;
            }
            $full = $contactData;
            if ($loadorg) {
                $full = $this->enrichContactOrganization($api, $full);
            }

            $personContacts[] = $full;
        }

        $this->contacts = $personContacts;

        return !empty($this->contacts);
    }
    
    /*
     * Helper-Funktion für die Org-Daten 
     */
    private function enrichContactOrganization(API $api, array $contact): array {
        $orgId = $contact['organization']['identifier'] ?? '';
        $orgId = is_string($orgId) ? trim($orgId) : '';
        if ($orgId === '') {
            return $contact;
        }

        $organizationData = $api->getOrgById($orgId);
        if (empty($organizationData) || !is_array($organizationData)) {
            return $contact;
        }

        if (!isset($contact['organization']) || !is_array($contact['organization'])) {
            $contact['organization'] = [];
        }

        $keys = [
            'address',
            'identifier',
            'longDescription',
            'name',
            'disambiguatingDescription',
            'parentOrganization',
            'subOrganization',
        ];

        foreach ($keys as $k) {
            if (isset($organizationData[$k])) {
                $contact['organization'][$k] = $organizationData[$k];
            }
        }

        return $contact;
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
    public function getPhone(): array {
        $vals = FaudirUtils::normalizeScalarOrArrayToList($this->telephone);

        if (!empty($vals)) {
            $out = [];
            foreach ($vals as $val) {
                if (FaudirUtils::isValidPhoneNumber($val)) {
                    $out[] = $val;
                }
            }
            return array_values(array_unique($out));
        }

        $workplaces = $this->getWorkplaces();
        if (empty($workplaces) || !is_array($workplaces)) {
            return [];
        }

        $out = [];
        foreach ($workplaces as $wdata) {
            if (!is_array($wdata) || empty($wdata['phones']) || !is_array($wdata['phones'])) {
                continue;
            }

            foreach ($wdata['phones'] as $val) {
                if (!is_string($val)) {
                    continue;
                }
                $val = trim($val);
                if ($val === '') {
                    continue;
                }
                if (FaudirUtils::isValidPhoneNumber($val)) {
                    $out[] = $val;
                }
            }
        }

        return array_values(array_unique($out));
    }
    
    /*
     * Get Fax Numbers for person
     * - if $person->fax is set, use this.
     * - if its empty use the phones address from active primary contact
     * Cause a person can use more as one phone number, we result with an array
     */ 
    public function getFax(): array {
        $vals = FaudirUtils::normalizeScalarOrArrayToList($this->fax);

        if (!empty($vals)) {
            $out = [];
            foreach ($vals as $val) {
                if (FaudirUtils::isValidPhoneNumber($val)) {
                    $out[] = $val;
                }
            }
            return array_values(array_unique($out));
        }

        $workplaces = $this->getWorkplaces();
        if (empty($workplaces) || !is_array($workplaces)) {
            return [];
        }

        $out = [];
        foreach ($workplaces as $wdata) {
            if (!is_array($wdata)) {
                continue;
            }

            // dein bisheriges Modell: 'fax' ist ein Array
            if (empty($wdata['fax']) || !is_array($wdata['fax'])) {
                continue;
            }

            foreach ($wdata['fax'] as $val) {
                if (!is_string($val)) {
                    continue;
                }
                $val = trim($val);
                if ($val === '') {
                    continue;
                }
                if (FaudirUtils::isValidPhoneNumber($val)) {
                    $out[] = $val;
                }
            }
        }

        return array_values(array_unique($out));
    }

    /*
     * Get Email address for person
     * - if $person->email is set, use this.
     * - if its empty use the email address from active primary contact
     * Cause a person can use more as one mail adress, we result with an array
     */ 
    public function getEMail(): array {
        $vals = FaudirUtils::normalizeScalarOrArrayToList($this->email);

        if (!empty($vals)) {
            $out = [];
            foreach ($vals as $val) {
                if (FaudirUtils::isValidEmailAddress($val)) {
                    $out[] = $val;
                }
            }
            return array_values(array_unique($out));
        }

        $workplaces = $this->getWorkplaces();
        if (empty($workplaces) || !is_array($workplaces)) {
            return [];
        }

        $out = [];
        foreach ($workplaces as $wdata) {
            if (!is_array($wdata) || empty($wdata['mails']) || !is_array($wdata['mails'])) {
                continue;
            }

            foreach ($wdata['mails'] as $val) {
                if (!is_string($val)) {
                    continue;
                }
                $val = trim($val);
                if ($val === '') {
                    continue;
                }
                if (FaudirUtils::isValidEmailAddress($val)) {
                    $out[] = $val;
                }
            }
        }

        return array_values(array_unique($out));
    }
    
    
    
    /**
    * Erzeugt den Anzeigenamen (optional semantisches HTML).
    * Optionale Eingaben:
    *  - $html (bool): HTML mit Microdata ausgeben (Default: true).
    *  - $normalize (bool): Akademischen Titel normalisieren (Default: false).
    *  - $format (string): Optionales Format mit Platzhaltern
    *    (#givenName#, #familyName#, #honorificPrefix#, #honorificSuffix#, #titleOfNobility#, #displayname#).
    * Rückgabe: string – formatierter Name (HTML oder Plaintext).
    */
   public function getDisplayName(bool $html = true, bool $normalize = false, string $format = ''): string {
       if (empty($this->givenName) && empty($this->familyName)) {
           return '';
       }
       $nameText = '';
       $nameHtml = '';

       if (empty($format)) {
           // Wrapper für HTML-Ausgabe
           $nameHtml .= '<span class="displayname" itemprop="name">';

           // "honorificPrefix" (akademischer Titel)
           if (!empty($this->honorificPrefix)) {
               $displayPrefix = $this->honorificPrefix;
               $abbrTitleLong = '';

               if ($normalize) {
                   // Normalisieren → sichtbarer Titel + Langlabel direkt aus normalizeAcademicTitle()
                   $norm          = \RRZE\FAUdir\FaudirUtils::normalizeAcademicTitle($this->honorificPrefix);
                   $displayPrefix = $norm['visible_title_no_discipline'] ?? $this->honorificPrefix;
                   $abbrTitleLong = $norm['label'] ?? ''; // <-- hier statt getAcademicTitleLongVersion(...)
               } else {
                   // Falls bekannt: Langlabel (für title-Attribut) klassisch aus Config-Mapping
                   $abbrTitleLong = self::getAcademicTitleLongVersion($this->honorificPrefix);
               }

               if (!empty($abbrTitleLong)) {
                   $nameHtml .= '<abbr title="' . esc_attr($abbrTitleLong) . '" itemprop="honorificPrefix">' . esc_html($displayPrefix) . '</abbr> ';
               } else {
                   $nameHtml .= '<span itemprop="honorificPrefix">' . esc_html($displayPrefix) . '</span> ';
               }
               $nameText .= esc_html($displayPrefix) . ' ';
           }

           // "givenName" + "familyName"
           if (!empty($this->givenName) && !empty($this->familyName)) {
               $nameHtml .= '<span class="namepart">';
           }

           if (!empty($this->givenName)) {
               $nameHtml .= '<span itemprop="givenName">' . esc_html($this->givenName) . '</span> ';
               $nameText .= esc_html($this->givenName) . ' ';
           }

           if (!empty($this->familyName)) {
               $nameHtml .= '<span itemprop="familyName">';
               // "titleOfNobility" als Teil des familyName
               if (!empty($this->titleOfNobility)) {
                   $nameHtml .= esc_html($this->titleOfNobility) . ' ';
                   $nameText .= esc_html($this->titleOfNobility) . ' ';
               }
               $nameHtml .= esc_html($this->familyName) . '</span>';
               $nameText .= esc_html($this->familyName);
           }

           if (!empty($this->givenName) && !empty($this->familyName)) {
               $nameHtml .= '</span>';
           }

           // "honorificSuffix"
           if (!empty($this->honorificSuffix)) {
               $nameHtml .= ' (<span itemprop="honorificSuffix">' . esc_html($this->honorificSuffix) . '</span>)';
               $nameText .= ' (' . esc_html($this->honorificSuffix) . ')';
           }

           $nameHtml .= '</span>';
           return $html ? $nameHtml : $nameText;
       }

       // Format-String ist übergeben → nur Platzhalter ersetzen (ohne zusätzliche Semantik)
       $nameHtml = '';
       if ($html) {
           $nameHtml = '<span class="displayname" itemprop="name">';
       }

       // Optional auch im Formatfall den normalisierten sichtbaren Titel verwenden
       $formattedPrefix = $this->honorificPrefix;
       if ($normalize && !empty($this->honorificPrefix)) {
           $norm            = \RRZE\FAUdir\FaudirUtils::normalizeAcademicTitle($this->honorificPrefix);
           $formattedPrefix = $norm['visible_title'] ?? $this->honorificPrefix;
       }

       $replacements = [
           '#givenName#'       => $this->givenName ?? '',
           '#displayname#'     => $this->getDisplayName(false, false) ?? '',
           '#familyName#'      => $this->familyName ?? '',
           '#honorificPrefix#' => $formattedPrefix ?? '',
           '#honorificSuffix#' => $this->honorificSuffix ?? '',
           '#titleOfNobility#' => $this->titleOfNobility ?? '',
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
        // Sperrliste (immer kleingeschrieben vergleichen)
        $restricted_abbr = ['hj', 'kz', 'ns', 'sa', 'ss', 'sex'];
            // gemäss: https://www.bundesverkehrsamt.online/verbotene-kennzeichen/
        
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

        // Prüfen gegen Sperrliste (case-insensitive)
        $resLower = mb_strtolower($res, 'UTF-8');
        if (in_array($resLower, $restricted_abbr, true)) {
             if ((!empty($this->familyName)) &&  (mb_strlen((string) $this->givenName, 'UTF-8') >=2)) {
                $addletter = mb_strtolower(mb_substr($this->familyName, 1, 1, 'UTF-8'),'UTF-8'); 
             } elseif ((!empty($this->givenName)) &&  (mb_strlen((string) $this->givenName, 'UTF-8') >=2)) {
                 $addletter = mb_strtolower(mb_substr($this->givenName, 1, 1, 'UTF-8'),'UTF-8'); 
             } else {
                 $addletter = ".";
             }
             $res .= $addletter;
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
        if (empty($this->config)) {
            $this->setConfig();
        }

        $post_type = (string) $this->config->get('person_post_type');

        $contact_posts = get_posts([
            'post_type'      => $post_type,
            'meta_key'       => 'person_id',
            'meta_value'     => $this->identifier,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);

        if (!empty($contact_posts)) {
            $postid = (int) $contact_posts[0];

            if (class_exists('\RRZE\Multilang\Helper')) {
                $lookforid = \RRZE\Multilang\Helper::getPostIdTranslation($postid);
                if (!empty($lookforid)) {
                    $postid = (int) $lookforid;
                }
            }

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
             
             
             // if there is a canonical and the setting wants to use it,
             // then we set the target to it.
            $canonical = '';
            $custom = get_post_meta($postid, 'canonical_url', true);
            if ($custom && filter_var($custom, FILTER_VALIDATE_URL)) {
                $canonical = esc_url_raw($custom);
            }
            
            if (empty($this->config)) {
                $this->setConfig();
            }
            $opt = $this->config->getOptions();   
            $usecanonical = 0;
            if (isset($opt['redirect_to_canonicals'])) {
                $usecanonical = $opt['redirect_to_canonicals'];
            }      
            if ($usecanonical && (!empty($canonical))) {
                $cpt_url = $canonical;
            }
           
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
    public function getContent(): string {
         if (empty($this->postid)) {
            $postid = $this->getPostId();
        } else {
            $postid = $this->postid;
        }
        if ($postid !== 0) {
            $content = get_post_field('post_content', $postid);

            return $content;
        }             
        return '';  
  
    }
    
    
    /*
     * Get teasertext
     */       
    public function getTeasertext(): string {       
        if (empty($this->postid)) {
            $postid = $this->getPostId();
        } else {
            $postid = $this->postid;
        }
        if ($postid !== 0) {
            
             $current_post  = get_post($postid);
             $excerpt       = isset($current_post->post_excerpt) ? $current_post->post_excerpt : '';

            return $excerpt;
        }
                       
        return '';                       
    }
    
   /*
    * Get Image as HTML or Replacement
    */
   public function getImage(string $css_classes = '', ?bool $signature = null, ?bool $figcaption = null, ?bool $displaycopyright = null, ?string $link_url = null): string {
        $postid = !empty($this->postid) ? $this->postid : $this->getPostId();

        // Link-URL validieren
        $valid_link_url = null;
        if (is_string($link_url)) {
            $link_url = trim($link_url);
            if ($link_url !== '' && filter_var($link_url, FILTER_VALIDATE_URL)) {
                $valid_link_url = $link_url;
            }
        }
    
        if (empty($this->config)) {
                $this->setConfig();
        }
        $opt = $this->config->getOptions();        
        $visible_copyrightmeta      = filter_var($opt['default_visible_copyrightmeta']    ?? true, FILTER_VALIDATE_BOOLEAN);
        $visible_bildunterschrift   = filter_var($opt['default_visible_bildunterschrift'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $placeholder_with_sign      = filter_var($opt['default_placeholder_image_with_signature'] ?? true, FILTER_VALIDATE_BOOLEAN);
        
        // Wenn Argumente nicht gesetzt wurden, auf Optionen zurückfallen
        if ($figcaption === null) {
            $figcaption = $visible_bildunterschrift;
        }
        if ($displaycopyright === null) {
            $displaycopyright = $visible_copyrightmeta;
        }
        if ($signature === null) {
            $signature = $placeholder_with_sign;
        }
        
        if ($postid !== 0) {
           $thumb_id = get_post_thumbnail_id($postid);
           $img_src  = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'full') : false;

           if ($img_src) {
               $src    = $img_src[0];
               $width  = $img_src[1];
               $height = $img_src[2];

               // srcset / sizes
               $srcset = wp_get_attachment_image_srcset($thumb_id, 'full');
               $sizes  = wp_get_attachment_image_sizes($thumb_id, 'full');

               // ALT-Text: zuerst echtes Anhangs-ALT, sonst Signatur
               $alt = (string) get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
               if ($alt === '') {
                   $alt = (string) $this->getSignature();
               }
   

               // Caption-Quelle: 1) Meta 'caption' (falls es bei dir existiert), 2) Attachment-Caption
               $captionText = '';
               if ($figcaption) {
                   $captionMeta = get_post_meta($thumb_id, 'caption', true);
                   if (is_string($captionMeta) && $captionMeta !== '') {
                       $captionText = $captionMeta;
                   } else {
                       $captionText = (string) wp_get_attachment_caption($thumb_id);
                   }
               }
 
 
               // EXIF/Meta → Copyright oder Credit
               $meta = wp_get_attachment_metadata($thumb_id);
               $copyrightText = '';
               $schemaMeta = [];
               $required_imageprop_given = false;
               
               if (is_array($meta) && !empty($meta['image_meta'])) {
                    $imeta = (array) $meta['image_meta'];
                    if (!empty($imeta['copyright'])) {
                        $copyrightText = trim((string) $imeta['copyright']);
                        $schemaMeta['copyrightNotice'] = $copyrightText;
                        $required_imageprop_given = true;
                    } elseif (!empty($imeta['credit'])) {
                        $copyrightText = trim((string) $imeta['credit']);
                    }
                    
                    // name (IPTC/EXIF title)
                    if (!empty($imeta['title'])) {
                        $schemaMeta['name'] = (string) $imeta['title'];
                    }

                    // caption (IPTC caption/abstract) – unabhängig davon, ob du eine sichtbare figcaption ausgibst
                    if (!empty($captionText)) {
                        $schemaMeta['caption'] = (string) $captionText;
                    }

                    // creditText
                    if (!empty($imeta['credit'])) {
                        $schemaMeta['creditText'] = (string) $imeta['credit'];
                        $required_imageprop_given = true;
                    }  

                    // dateCreated (UNIX → ISO 8601)
                    if (!empty($imeta['created_timestamp']) && ctype_digit((string) $imeta['created_timestamp'])) {
                        $schemaMeta['dateCreated'] = gmdate('c', (int) $imeta['created_timestamp']);
                    }

                    // keywords (Array → kommagetrennt)
                    if (!empty($imeta['keywords']) && is_array($imeta['keywords'])) {
                        // Du kannst auch mehrere <meta itemprop="keywords"> schreiben; hier kommagetrennt:
                        $schemaMeta['keywords'] = implode(', ', array_filter(array_map('trim', $imeta['keywords'])));
                    }
                   
               }
               if (($required_imageprop_given === false) && (empty($schemaMeta['license']))) {
                   // wir haben nur die url. Das reicht nicht, wir brauchen mindestens einen der folgenden 
                   // itemprops: creator, creditText, copyrightNotice, licence
                   // befülle daher wenigstens licence mit der Impressums-URL
                    $locale = function_exists('get_locale') ? (string) get_locale() : (string) get_bloginfo('language');
                    // DE → /impressum, sonst → /imprint
                    $slug   = (stripos($locale, 'de') === 0) ? 'impressum' : 'imprint';

                    // Domain + URI
                    $url = home_url('/' . $slug);

                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        // schema.org korrekt:
                        $schemaMeta['license'] = $url;
                    }
               }
                   
               
               
               if ($copyrightText !== '') {
                    Filters::pushCopyright($copyrightText, $thumb_id);
               }
                
               // HTML zusammenbauen
               $html  = '<figure itemprop="image" itemscope itemtype="http://schema.org/ImageObject"';
               if (!empty($css_classes)) {
                   $html .= ' class="' . esc_attr($css_classes) . '"';
               }
                $html .= '>';

                $html .= '<img src="' . esc_url($src) . '" ';
                if ($alt !== '') {
                    $html .= 'alt="' . esc_attr($alt) . '" ';
                }
                $html .= 'width="' . esc_attr($width) . '" ';
                $html .= 'height="' . esc_attr($height) . '" ';
                if ($srcset) {
                    $html .= 'srcset="' . esc_attr($srcset) . '" ';
                }
                if ($sizes) {
                    $html .= 'sizes="' . esc_attr($sizes) . '" ';
                }
                $html .= 'itemprop="contentUrl">';

                $html .= '<meta itemprop="width" content="' . esc_attr($width) . '">';
                $html .= '<meta itemprop="height" content="' . esc_attr($height) . '">';
                foreach ($schemaMeta as $prop => $val) {
                    if ($val === '' || $val === null) { continue; }
                    $html .= '<meta itemprop="' . esc_attr($prop) . '" content="' . esc_attr((string) $val) . '">';
                }
               
              

               // Figcaption nur wenn gewünscht + Inhalt vorhanden
                if ($figcaption && ($captionText !== '' || ($displaycopyright && $copyrightText !== ''))) {
                    $html .= '<figcaption>';
                    if ($captionText !== '') {
                        $html .= '<p itemprop="caption">' . esc_html($captionText) . '</p>';
                       
                    }
                    // Sichtbare Copyright-Info nur wenn gewünscht
                    if ($displaycopyright && $copyrightText !== '') {
                        $html .= '<p class="image-copyright">' . esc_html($copyrightText) . '</p>';
                    }
                    $html .= '</figcaption>';
                }
                

               $html .= '</figure>';
                if ($valid_link_url !== null) {
                    $html = '<a href="' . esc_url($valid_link_url) . '">' . $html . '</a>';
                }
               return $html;
           }
       }

       // Fallback: Text-Signatur (ohne Bild)
       if ($signature) {
           $alt  = $this->getSignature();
           $html = '<figure';
           $css_classes = trim($css_classes . ' signature');
           $html .= ' class="' . esc_attr($css_classes) . '">';
           $html .= '<span class="text">' . esc_html($alt) . '</span>';
           $html .= '</figure>';
           
            if ($valid_link_url !== null) {
                $html = '<a href="' . esc_url($valid_link_url) . '">' . $html . '</a>';
            }

           return $html;
       }

       return '';
   }

    
   /*
    * Liefert den Contacteintrag zurück, der auf einen Role-FUnktionsstring matcht
    */
    public function getContactByRole(string $role = '', bool $fallback = true, bool $partial = true): ?Contact {
        if (empty($this->contacts) || !is_array($this->contacts)) {
            return null;
        }

        $role = $this->normalizeRoleString($role);
        if ($role === '') {
            if ($fallback) {
                return $this->getFirstContactOrNull();
            }
            return null;
        }

        // Wenn nur ein Contact vorhanden ist → optional Fallback
        if (count($this->contacts) === 1) {
            if ($fallback) {
                return $this->getFirstContactOrNull();
            }
            return null;
        }

        // Komma-separierte Rollen in Eingabe-Reihenfolge auswerten
        $needles = $this->splitAndNormalizeRoles($role);
        if (empty($needles)) {
            if ($fallback) {
                return $this->getFirstContactOrNull();
            }
            return null;
        }

        foreach ($needles as $needle) {
            foreach ($this->contacts as $contactData) {
                if (empty($contactData) || !is_array($contactData)) {
                    continue;
                }

                $contact = new Contact($contactData);

                $labels = $contact->getAllFunctionLabels();
                if (empty($labels) || !is_array($labels)) {
                    continue;
                }

                foreach ($labels as $labelString) {
                    $hay = $this->normalizeRoleString((string) $labelString);
                    if ($hay === '') {
                        continue;
                    }

                    if ($partial) {
                        if (strpos($hay, $needle) !== false) {
                            return $contact;
                        }
                    } else {
                        if ($hay === $needle) {
                            return $contact;
                        }
                    }
                }
            }
        }

        if ($fallback) {
            return $this->getFirstContactOrNull();
        }

        return null;
    }

    /**
     * Hilfsfunktion: trim + (mb_)strtolower + Tags entfernen
     */
   private function normalizeRoleString(string $s): string {
        $s = trim(wp_strip_all_tags($s));
        if ($s === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($s, 'UTF-8');
        }

        return strtolower($s);
    }

    private function splitAndNormalizeRoles(string $csv): array {
        $csv = trim($csv);
        if ($csv === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/u', $csv, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts) || !is_array($parts)) {
            return [];
        }

        $out = [];
        foreach ($parts as $p) {
            $p = $this->normalizeRoleString((string) $p);
            if ($p !== '') {
                $out[] = $p;
            }
        }

        // Duplikate entfernen, Reihenfolge behalten
        $uniq = [];
        $seen = [];
        foreach ($out as $p) {
            if (!isset($seen[$p])) {
                $seen[$p] = true;
                $uniq[] = $p;
            }
        }

        return $uniq;
    }
    
    private function getFirstContactOrNull(): ?Contact {
        $first = $this->contacts[0] ?? null;
        if (empty($first) || !is_array($first)) {
            return null;
        }
        return new Contact($first);
    }
    
    
    
    /*
     * Liefert den Contact-Eintrag zurück, der entweder im Backend definiert
     *  wurde als Primärer, den spezifischnen zu eine Role oder als Fallback
     * den erst möglichen  
     */
    public function getPrimaryContact(string $role = ''): ?Contact {
        if (empty($this->contacts) || !is_array($this->contacts)) {
            return null;
        }

        // Wenn Role übergeben wurde: NICHT cachen, sondern gezielt suchen
        $role = $this->normalizeRoleString($role);
        if ($role !== '') {
            $roleContact = $this->getContactByRole($role, false, true);
            if ($roleContact instanceof Contact) {
                return $roleContact;
            }
            // wenn Role gesucht aber nicht gefunden → Fallback auf "normalen" Primary
            // (optional: hier auch null zurückgeben; ich bleibe bei fallback)
        }

        // Cache nur für "ohne role"
        if ($this->primary_contact instanceof Contact) {
            return $this->primary_contact;
        }

        // Nur ein Contact → fertig
        if (count($this->contacts) === 1) {
            $this->primary_contact = $this->getFirstContactOrNull();
            return $this->primary_contact;
        }

        // CPT: displayed_contacts Index holen
        $postId = !empty($this->postid) ? (int) $this->postid : (int) $this->getPostId();
        if ($postId > 0) {
            $idx = get_post_meta($postId, 'displayed_contacts', true);

            // Downward compatibility: alte Werte/Arrays → 0
            if (is_array($idx) || $idx === '' || $idx === null) {
                $idx = 0;
            }

            $idx = (int) $idx;
            if (isset($this->contacts[$idx]) && is_array($this->contacts[$idx])) {
                $this->primary_contact = new Contact($this->contacts[$idx]);
                return $this->primary_contact;
            }
        }

        // Default-Fallback: erster Contact
        $this->primary_contact = $this->getFirstContactOrNull();
        return $this->primary_contact;
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
    
    /**
    * Liefert die Langform (lokalisierte Bezeichnung) eines akademischen Titels
    * anhand der in der Config gepflegten Präfix-Tabelle.
    * Optionale Eingaben: keine.
    * Rückgabe: string (übersetzte Langbezeichnung) oder '' wenn unbekannt.
    */
   private static function getAcademicTitleLongVersion(string $prefix): string {
       // Normalisieren/zuordnen (nutzt intern die Config-Mapping-Tabelle + Aliase)
       $norm = \RRZE\FAUdir\FaudirUtils::normalizeAcademicTitle($prefix);

       // Wenn in der Config gefunden, die dort gepflegte Langbezeichnung zurückgeben
       if (!empty($norm['label'])) {
           return (string) $norm['label'];
       }

       return '';
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
