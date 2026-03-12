import { __ } from '@wordpress/i18n';

export interface AvailableFieldsSet {
  [key: string]: string;
}

export interface FormatFieldsSet {
  [key: string]: string[];
}

export interface FormatNamesSet {
  [key: string]: string;
}

export interface FaudirSettingsPayload {
  show_output_fields_person_default?: string[];
  show_output_fields_person_page?: string[];
  show_output_fields_org_default?: string[];
  available_fields?: AvailableFieldsSet;
  available_fields_org?: AvailableFieldsSet;
  avaible_fields_byformat?: FormatFieldsSet;
  default_organization?: Record<string, unknown> | null;
  format_names?: FormatNamesSet;
}

export const fallbackAvailableFields: AvailableFieldsSet = {
  image: __('Image', 'rrze-faudir'),
  displayname: __('Display Name', 'rrze-faudir'),
  honorificPrefix: __('Academic Title', 'rrze-faudir'),
  honorificSuffix: __('Academic Suffix', 'rrze-faudir'),
  givenName: __('First Name', 'rrze-faudir'),
  titleOfNobility: __('Title of Nobility', 'rrze-faudir'),
  familyName: __('Family Name', 'rrze-faudir'),
  email: __('Email', 'rrze-faudir'),
  phone: __('Phone', 'rrze-faudir'),
  fax: __('Fax', 'rrze-faudir'),
  organization: __('Organization', 'rrze-faudir'),
  jobTitle: __('Jobtitle', 'rrze-faudir'),
  url: __('URL', 'rrze-faudir'),
  content: __('Content', 'rrze-faudir'),
  teasertext: __('Teasertext', 'rrze-faudir'),
  socialmedia: __('Social Media and Websites', 'rrze-faudir'),
  room: __('Room', 'rrze-faudir'),
  floor: __('Floor', 'rrze-faudir'),
  address: __('Address', 'rrze-faudir'),
  street: __('Street', 'rrze-faudir'),
  zip: __('ZIP Code', 'rrze-faudir'),
  city: __('City', 'rrze-faudir'),
  faumap: __('FAU Map', 'rrze-faudir'),
  officehours: __('Office Hours', 'rrze-faudir'),
  consultationhours: __('Consultation Hours', 'rrze-faudir'),
  link: __('Link to Profil', 'rrze-faudir'),
  alternateName: __('Alternate Name', 'rrze-faudir'),
  text: __('Text', 'rrze-faudir'),
  postalAddress: __('Postal Address', 'rrze-faudir'),
};

export const fallbackAvailableFieldsOrg: AvailableFieldsSet = {
  name: __('Name', 'rrze-faudir'),
  alternateName: __('Alternate Name', 'rrze-faudir'),
  disambiguatingDescription: __('Disambiguating Description', 'rrze-faudir'),
  longDescription: __('Description', 'rrze-faudir'),
  email: __('Email', 'rrze-faudir'),
  phone: __('Phone', 'rrze-faudir'),
  fax: __('Fax', 'rrze-faudir'),
  faumap: __('FAU Map', 'rrze-faudir'),
  url: __('URL', 'rrze-faudir'),
  text: __('Text', 'rrze-faudir'),
  socialmedia: __('Social Media and Websites', 'rrze-faudir'),
  address: __('Address', 'rrze-faudir'),
  postalAddress: __('Postal Address', 'rrze-faudir'),
  internalAddress: __('Internal Address', 'rrze-faudir'),
  officehours: __('Office Hours', 'rrze-faudir'),
  consultationhours: __('Consultation Hours', 'rrze-faudir'),
};

export const fallbackFormatNames: FormatNamesSet = {
  default: __('Default', 'rrze-faudir'),
  compact: __('Compact', 'rrze-faudir'),
  table: __('Table', 'rrze-faudir'),
  list: __('List', 'rrze-faudir'),
  page: __('Page', 'rrze-faudir'),
  card: __('Card', 'rrze-faudir'),
  'org-default': __('Default', 'rrze-faudir'),
  'org-compact': __('Compact', 'rrze-faudir'),
};

export const fieldMapping: Record<string, string> = {
  image: 'image',
  displayname: 'displayname',
  honorificPrefix: 'honorificPrefix',
  honorificSuffix: 'honorificSuffix',
  givenName: 'givenName',
  titleOfNobility: 'titleOfNobility',
  familyName: 'familyName',
  email: 'email',
  phone: 'phone',
  fax: 'fax',
  organization: 'organization',
  jobTitle: 'jobTitle',
  url: 'url',
  content: 'content',
  teasertext: 'teasertext',
  socialmedia: 'socialmedia',
  room: 'room',
  floor: 'floor',
  address: 'address',
  street: 'street',
  zip: 'zip',
  city: 'city',
  faumap: 'faumap',
  officehours: 'officehours',
  consultationhours: 'consultationhours',
  link: 'link',
  alternateName: 'alternateName',
  text: 'text',
  postalAddress: 'postalAddress',
  name: 'name',
  disambiguatingDescription: 'disambiguatingDescription',
  longDescription: 'longDescription',
  internalAddress: 'internalAddress',
};

export function normalizeDisplay(
  settings: FaudirSettingsPayload,
  display: string = 'person'
): string {
  var normalizedDisplay = String(display || '').trim().toLowerCase();
  var byFormat = settings.avaible_fields_byformat || {};
  var detectedDisplays = getDisplaysFromAvailableFormats(byFormat);

  if (normalizedDisplay !== '' && detectedDisplays.indexOf(normalizedDisplay) !== -1) {
    return normalizedDisplay;
  }

  if (detectedDisplays.indexOf('person') !== -1) {
    return 'person';
  }

  if (detectedDisplays.indexOf('org') !== -1) {
    return 'org';
  }

  return 'person';
}

export function normalizeFormatForDisplay(
  settings: FaudirSettingsPayload,
  format: string = '',
  display: string = 'person'
): string {
  var normalizedDisplay = normalizeDisplay(settings, display);
  var normalizedFormat = String(format || '').trim().toLowerCase();
  var availableFormats = getAvailableFormats(settings, normalizedDisplay);

  if (normalizedFormat !== '' && availableFormats.indexOf(normalizedFormat) !== -1) {
    return normalizedFormat;
  }

  if (availableFormats.indexOf('default') !== -1) {
    return 'default';
  }

  if (availableFormats.indexOf('compact') !== -1) {
    return 'compact';
  }

  if (availableFormats.length > 0) {
    return availableFormats[0];
  }

  return 'default';
}

export function getAvailableFormats(
  settings: FaudirSettingsPayload,
  display: string = 'person'
): string[] {
  var normalizedDisplay = normalizeDisplay(settings, display);
  var byFormat = settings.avaible_fields_byformat || {};
  var formats: string[] = [];
  var key: string;

  for (key in byFormat) {
    if (!Object.prototype.hasOwnProperty.call(byFormat, key)) {
      continue;
    }

    if (normalizedDisplay === 'org') {
      if (key.indexOf('org-') === 0) {
        formats.push(key.substring(4));
      }
    } else {
      if (key.indexOf('org-') !== 0) {
        formats.push(key);
      }
    }
  }

  return uniqueStrings(formats);
}

export function getAvailableFields(
  settings: FaudirSettingsPayload,
  display: string = 'person',
  format: string = 'default'
): string[] {
  var normalizedDisplay = normalizeDisplay(settings, display);
  var normalizedFormat = normalizeFormatForDisplay(settings, format, normalizedDisplay);
  var byFormat = settings.avaible_fields_byformat || {};
  var configKey = getFormatConfigKey(normalizedDisplay, normalizedFormat);
  var fields = byFormat[configKey];

  if (Array.isArray(fields)) {
    return fields.slice();
  }

  if (normalizedDisplay === 'org' && Array.isArray(byFormat['org-default'])) {
    return byFormat['org-default'].slice();
  }

  if (Array.isArray(byFormat.default)) {
    return byFormat.default.slice();
  }

  return [];
}

export function getDefaultFields(
  settings: FaudirSettingsPayload,
  display: string = 'person',
  format: string = 'default'
): string[] {
  var normalizedDisplay = normalizeDisplay(settings, display);
  var normalizedFormat = normalizeFormatForDisplay(settings, format, normalizedDisplay);

  if (normalizedDisplay === 'org') {
    if (Array.isArray(settings.show_output_fields_org_default)) {
      return settings.show_output_fields_org_default.slice();
    }
    return [];
  }

  if (normalizedFormat === 'page') {
    if (Array.isArray(settings.show_output_fields_person_page)) {
      return settings.show_output_fields_person_page.slice();
    }
    return [];
  }

  if (Array.isArray(settings.show_output_fields_person_default)) {
    return settings.show_output_fields_person_default.slice();
  }

  return [];
}

export function getRequiredFields(
  settings: FaudirSettingsPayload,
  display: string = 'person',
  format: string = 'default'
): string[] {
  return getDefaultFields(settings, display, format);
}

export function getAvailableFieldLabels(
  settings: FaudirSettingsPayload,
  display: string = 'person'
): AvailableFieldsSet {
  var normalizedDisplay = normalizeDisplay(settings, display);

  if (normalizedDisplay === 'org') {
    if (settings.available_fields_org && typeof settings.available_fields_org === 'object') {
      return settings.available_fields_org;
    }
    return fallbackAvailableFieldsOrg;
  }

  if (settings.available_fields && typeof settings.available_fields === 'object') {
    return settings.available_fields;
  }

  return fallbackAvailableFields;
}

export function getFormatNames(settings: FaudirSettingsPayload): FormatNamesSet {
  if (settings.format_names && typeof settings.format_names === 'object') {
    return settings.format_names;
  }

  return fallbackFormatNames;
}

function getDisplaysFromAvailableFormats(byFormat: FormatFieldsSet): string[] {
  var displays: string[] = [];
  var key: string;

  for (key in byFormat) {
    if (!Object.prototype.hasOwnProperty.call(byFormat, key)) {
      continue;
    }

    if (key.indexOf('org-') === 0) {
      displays.push('org');
    } else {
      displays.push('person');
    }
  }

  return uniqueStrings(displays);
}

function getFormatConfigKey(display: string, format: string): string {
  if (display === 'org') {
    return 'org-' + format;
  }

  return format;
}

function uniqueStrings(values: string[]): string[] {
  var seen: Record<string, boolean> = {};
  var result: string[] = [];
  var i: number;
  var value: string;

  for (i = 0; i < values.length; i++) {
    value = String(values[i] || '').trim();
    if (value === '') {
      continue;
    }

    if (seen[value]) {
      continue;
    }

    seen[value] = true;
    result.push(value);
  }

  return result;
}