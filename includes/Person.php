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
    
    public function __construct(array $data) {
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
            'contacts'
        ];
        $remaining = array_diff_key($data, array_flip($usedKeys));
        $this->rawdata = $remaining;
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
    
    
    public function getTargetURL(): string {
        $contact_posts = get_posts([
            'post_type' => 'custom_person',
            'meta_key' => 'person_id',
            'meta_value' => $this->identifier,
            'posts_per_page' => 1, // Only fetch one post matching the person ID
        ]);
        $cpt_url = !empty($contact_posts) ? get_permalink($contact_posts[0]->ID) : '';
                       
        return $cpt_url;
                        
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
