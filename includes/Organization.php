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
}
