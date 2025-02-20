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