<?php

/**
 * Contact Class
 *
 * @author unrz59
 */

namespace RRZE\FAUdir;

class Contact {
    public array $context = [];
    public string $type = '';
    public string $id = '';
    public string $identifier = '';
    public array $person = [];
    public array $organization = [];
    public string $givenName = '';
    public string $familyName = '';
    public ?string $titleOfNobility = '';
    public ?string  $function = '';
    public array $functionLabel = [];
    public array $workplaces = [];
    public array $organization_address = [];

    /**
     * Contact constructor
     */
    public function __construct(array $data = []) {
        if (isset($data['@context']) && is_array($data['@context'])) {
            $this->context = $data['@context'];
        }
        if (isset($data['@type']) && is_string($data['@type'])) {
            $this->type = $data['@type'];
        }
        if (isset($data['@id']) && is_string($data['@id'])) {
            $this->id = $data['@id'];
        }
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
        if (isset($data['function']) && is_string($data['function'])) {
            $this->function = $data['function'];
        }
        if (isset($data['functionLabel']) && is_array($data['functionLabel'])) {
            $this->functionLabel = $data['functionLabel'];
        }
        if (isset($data['workplaces']) && is_array($data['workplaces'])) {
            $this->workplaces = $data['workplaces'];
        }
        if (isset($data['organization_address']) && is_array($data['organization_address'])) {
            $this->organization_address = $data['organization_address'];
        }
    }
}