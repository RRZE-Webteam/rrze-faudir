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
    public ?string $fax;
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
            $this->identifier           = '';
            $this->givenName            = '';
            $this->familyName           = '';
            $this->honorificPrefix      = '';
            $this->honorificSuffix      = '';
            $this->titleOfNobility      = '';
            $this->pronoun              = '';
            $this->email                = '';
            $this->telephone            = '';
            $this->postid               = 0;
            $this->contacts             = null;
            $this->primary_contact      = null;
            // Leere rawdata zurücksetzen
            $this->rawdata              = [];
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
        return true;
    }
    
    
    /*
     * Contact-Daten befüllen
     */
    public function reloadContacts(bool $loadorg = false): bool {
        $personContacts = [];
        
        // Falls keine Kontakte gesetzt sind, nichts tun
        if (empty($this->contacts) || !is_array($this->contacts)) {
            return false;
        }
        if (empty($this->config)) {
            $this->setConfig();
        }
        
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
                        // Adresse kommt doch aus dem Workplaces...
                        
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
     * Get Fax Numbers for person
     * - if $person->fax is set, use this.
     * - if its empty use the phones address from active primary contact
     * Cause a person can use more as one phone number, we result with an array
     */ 
    public function getFax(): ?array {
        $resphone = [];
       
        $workplaces = $this->getWorkplaces();
        if (empty($workplaces)) {
            return [];
        }
        
        
        $gatherphones = [];     
        foreach ($workplaces as $num => $wdata) {
            if (isset($wdata['fax'])) {
                foreach ($wdata['fax'] as $i => $val) {
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
        $config = new Config();
        $post_type = $config->get('person_post_type'); 
         
        
                
        $contact_posts = get_posts([
            'post_type' => $post_type,
            'meta_key' => 'person_id',
            'meta_value' => $this->identifier,
            'posts_per_page' => 1, // Only fetch one post matching the person ID
        ]);
         
        if (!empty($contact_posts)) {        
            $postid = $contact_posts[0]->ID;      
         
            if (class_exists('\RRZE\Multilang\Helper')) {
                // check for multilang plugin
                $lookforid = \RRZE\Multilang\Helper::getPostIdTranslation($contact_posts[0]->ID);
             //    do_action( 'rrze.log.info',"FAUdir\Person (getPostId): Looking for post id $postid with Multilang Helper, target: $lookforid");
                 if ($lookforid) {
                     $postid = $lookforid;
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
   public function getImage(string $css_classes = '', ?bool $signature = null, ?bool $figcaption = null, ?bool $displaycopyright = null): string {
        $postid = !empty($this->postid) ? $this->postid : $this->getPostId();

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
           return $html;
       }

       return '';
   }

    
   /*
    * Liefert den Contacteintrag zurück, der auf einen Role-FUnktionsstring matcht
    */
    public function getContactByRole(string $role = '', bool $fallback = true, bool $partial = true): ?Contact {
        if (empty($this->contacts) || $role === '') {
            return null;
        }

        // Wenn es nur einen einzigen Contacteintrag gibt → ggf. Fallback auf diesen
        if (count($this->contacts) === 1 && !empty($this->contacts[0]) && $fallback) {
            return new Contact($this->contacts[0]);
        }

       // Komma-separierte Rollen in Reihenfolge der Eingabe auswerten
        $needles = preg_split('/\s*,\s*/u', (string) $role, -1, PREG_SPLIT_NO_EMPTY);
        $needles = array_values(array_filter(array_map(fn($r) => $this->normalizeRoleString($r), (array) $needles)));

        if (empty($needles)) {
            return null;
        }

        // In der Reihenfolge der Needles suchen (erste Übereinstimmung gewinnt)
        foreach ($needles as $needle) {
            foreach ((array) $this->contacts as $contactData) {
                if (empty($contactData) || !is_array($contactData)) {
                    continue;
                }
                $contact = new Contact($contactData);
                foreach ($contact->getAllFunctionLabels() as $labelString) {
                    $hay   = $this->normalizeRoleString($labelString);
                    $match = $partial ? (strpos($hay, $needle) !== false) : ($hay === $needle);
                    if ($match) {
                        return $contact;
                    }
                }
            }
        }

        // kein Treffer
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
        return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    }

    
    /*
     * Lieder den Contact-Eintrag zurück, der entweder im Backend definiert
     *  wurde als Primärer, den spezifischnen zu eine Role oder als Fallback
     * den erst möglichen  
     */
    public function getPrimaryContact(string $role = ''): ?Contact {       
        // Wenn es keinen Contact-Array gibt, dann gibt es kein Contact
        if (empty($this->contacts)) {
            return false;
        }

        // Wenn es nur einen einzigen Contacteintrag gibt, dann returne diesen        
        if ((count($this->contacts)==1) && (!empty($this->contacts[0]))) {         
            $this->primary_contact = new Contact($this->contacts[0]);
            return $this->primary_contact;
        }
        
        if (!empty($role)) {
            // wenn wir eine spezifische Role/Funktionslabel übergeben bekommen haben, dann
            // schauen wir zunächst ob diese matcht nehmen daher nicht den
            // vordefinierten Contact.
            $rolecontact = $this->getContactByRole($role); 
            if ($rolecontact) {
                $this->primary_contact = $rolecontact;
                return $this->primary_contact;
            }
           
        }
        if (!empty($this->primary_contact)) {
            // we already calculated this, therfor we return this            
            return $this->primary_contact;
        }
         
        
        // look for existing local cpt
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
        if ((is_array($displayed_contacts)) || (empty($displayed_contacts))) {
            $displayed_contacts = 0;
        }
        
    
        if (isset($this->contacts[$displayed_contacts])) {
            $this->primary_contact = new Contact($this->contacts[$displayed_contacts]);
            return $this->primary_contact;
        } else {              
                          
            $this->primary_contact = new Contact($this->contacts);
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
