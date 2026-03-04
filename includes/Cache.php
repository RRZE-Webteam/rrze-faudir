<?php

declare(strict_types=1);

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

final class Cache {
    private string $baseUrl;
    private string $transient_prefix;
    private int $transient_jitter_minutes;
    private array $transient_times;

    public function __construct(string $baseUrl) {
        $this->baseUrl = $baseUrl;
        $this->transient_prefix = Constants::TRANSIENT_PREFIX_API;
        $this->transient_jitter_minutes = Constants::TRANSIENT_JITTER_MINUTES;
        $this->transient_times = Constants::TRANSIENT_TIMES;
    }

    public function normalizeUrlForCache(string $url): string {
        $parsed = wp_parse_url($url);
        if (!is_array($parsed)) {
            return $url;
        }

        $scheme = (string) ($parsed['scheme'] ?? '');
        $host = (string) ($parsed['host'] ?? '');
        $path = (string) ($parsed['path'] ?? '');
        $query = (string) ($parsed['query'] ?? '');

        if ($scheme === '' || $host === '' || $query === '') {
            return $url;
        }

        $params = [];
        parse_str($query, $params);

        if (!is_array($params) || empty($params)) {
            return $url;
        }

        ksort($params);

        $rebuilt = $scheme . '://' . $host . $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        if (!empty($parsed['fragment'])) {
            $rebuilt .= '#' . $parsed['fragment'];
        }

        return $rebuilt;
    }

    public function buildTransientKey(string $url, ?string $cache_key_basis = null): string {
        if ($cache_key_basis === null || $cache_key_basis === '') {
            $endpoint = $this->deriveEndpointFromUrl($url);
            return $this->transient_prefix . $endpoint . '_' . md5($url);
        }

        $safe = strtolower((string) $cache_key_basis);
        $safe = preg_replace('/[^a-z0-9_\-:]/', '_', $safe);
        $safe = trim((string) $safe, '_');

        $key = $this->transient_prefix . $safe;

        if (strlen($key) > 172) {
            $key = $this->transient_prefix . md5($safe);
        }

        return $key;
    }

    public function get(string $url, ?string $cache_key_basis = null): ?array {
        $url = $this->normalizeUrlForCache($url);
        $key = $this->buildTransientKey($url, $cache_key_basis);

        $cached = get_transient($key);
        if ($cached !== false && is_array($cached)) {
            return $cached;
        }

        return null;
    }

    public function set(string $url, array $data, ?string $cache_key_basis = null): void {
        $url = $this->normalizeUrlForCache($url);
        $endpoint = $this->deriveEndpointFromUrl($url);

        $base_minutes = (int) ($this->transient_times[$endpoint] ?? $this->transient_times['default']);
        $random_offset = wp_rand(0, $this->transient_jitter_minutes) * 60;
        $lifetime = ($base_minutes * 60) + $random_offset;

        $key = $this->buildTransientKey($url, $cache_key_basis);
        set_transient($key, $data, $lifetime);

       // do_action('rrze.log.info', "FAUdir\Cache (set): Set Transient key {$key}.");
    }

    public function deleteByTypeAndId(string $type, string $id): bool {
        $type = strtolower(trim($type));

        if ($type === 'person' || $type === 'persons') {
            return $this->deletePersonTransient($id);
        }
        if ($type === 'contact' || $type === 'contacts') {
            return $this->deleteContactTransient($id);
        }
        if ($type === 'org' || $type === 'organization' || $type === 'organizations') {
            return $this->deleteOrgTransient($id);
        }

        return false;
    }

    public function getTransientKeyForPerson(string $input): ?string {
        $personId = FaudirUtils::sanitizePersonId($input);
        if ($personId === null) {
            return null;
        }

        $url = trailingslashit($this->baseUrl) . 'persons/' . $personId;
        $cacheKeyBasis = Constants::TRANSIENT_KEY_PERSON_PREFIX . $personId;

        return $this->buildTransientKey($this->normalizeUrlForCache($url), $cacheKeyBasis);
    }

    public function getTransientKeyForContact(string $input): ?string {
        $contactId = FaudirUtils::sanitizePersonId($input);
        if ($contactId === null) {
            return null;
        }

        $url = trailingslashit($this->baseUrl) . 'contacts/' . $contactId;
        $cacheKeyBasis = Constants::TRANSIENT_KEY_CONTACT_PREFIX . $contactId;

        return $this->buildTransientKey($this->normalizeUrlForCache($url), $cacheKeyBasis);
    }

    public function getTransientKeyForOrg(string $input): ?string {
        $orgId = FaudirUtils::sanitizeOrganizationId($input);
        if ($orgId === null) {
            return null;
        }

        $url = trailingslashit($this->baseUrl) . 'organizations/' . $orgId;
        $cacheKeyBasis = Constants::TRANSIENT_KEY_ORG_PREFIX . $orgId;

        return $this->buildTransientKey($this->normalizeUrlForCache($url), $cacheKeyBasis);
    }

    public function deletePersonTransient(string $id): bool {
        $key = $this->getTransientKeyForPerson($id);
        if ($key === null) {
            return false;
        }

        return delete_transient($key);
    }

    public function deleteContactTransient(string $id): bool {
        $key = $this->getTransientKeyForContact($id);
        if ($key === null) {
            return false;
        }

        return delete_transient($key);
    }

    public function deleteOrgTransient(string $id): bool {
        $key = $this->getTransientKeyForOrg($id);
        if ($key === null) {
            return false;
        }

        return delete_transient($key);
    }

    private function deriveEndpointFromUrl(string $url): string {
        $parsed = wp_parse_url($url);
        $path = (string) ($parsed['path'] ?? '');

        $basePath = (string) wp_parse_url($this->baseUrl, PHP_URL_PATH);
        $relative = $basePath !== '' ? str_replace($basePath, '', $path) : $path;

        $parts = explode('/', trim($relative, '/'));
        $endpoint = $parts[0] !== '' ? $parts[0] : 'default';

        return $endpoint;
    }
}