import {__} from "@wordpress/i18n";

interface FormatFieldsSet {
  card: string[];
  table: string[];
  list: string[];
  compact: string[];
  page: string[];
  [key: string]: string[];
}

interface AvailableFieldsSet {
  [key: string]: string;
}

export const availableFields: AvailableFieldsSet = {
  image: __('Image', 'rrze-faudir'),
  displayName: __('Display Name', 'rrze-faudir'),
  honorificPrefix: __('Academic Title', 'rrze-faudir'),
  givenName: __('First Name', 'rrze-faudir'),
  familyName: __('Last Name', 'rrze-faudir'),
  honorificSuffix: __('Academic Suffix', 'rrze-faudir'),
  titleOfNobility: __('Title of Nobility', 'rrze-faudir'),
  email: __('Email', 'rrze-faudir'),
  phone: __('Phone', 'rrze-faudir'),
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
  consultationhours: __('Consultation Hours', 'rrze-faudir')
};

export const formatFields: FormatFieldsSet = {
  card: [
    'image',
    'displayName',
    'honorificPrefix',
    'givenName',
    'familyName',
    'honorificSuffix',
    'email',
    'phone',
    'jobTitle',
    'socialmedia',
    'titleOfNobility',
    'organization',
  ],
  table: [
    'image',
    'displayName',
    'honorificPrefix',
    'givenName',
    'familyName',
    'honorificSuffix',
    'email',
    'phone',
    'url',
    'socialmedia',
    'titleOfNobility',
    'floor',
    'room',
    'address',
    'organization',
  ],
  list: [
    'displayName',
    'honorificPrefix',
    'givenName',
    'familyName',
    'honorificSuffix',
    'email',
    'phone',
    'url',
    'teasertext',
    'titleOfNobility',
    'address',
  ],
  orgid: [
    'email',
    'phone',
    'organization',
    'url',
    'address',
    'street',
    'zip',
    'city',
    'faumap',
    'content'
  ],
  compact: Object.keys(availableFields),
  page: Object.keys(availableFields),
};

// Define required fields for each format
export const requiredFields = {
  card: ['displayname', 'honorificPrefix', 'givenName', 'familyName'],
  table: ['displayname', 'honorificPrefix', 'givenName', 'familyName'],
  list: ['displayname', 'honorificPrefix', 'givenName', 'familyName'],
  compact: ['displayname', 'honorificPrefix', 'givenName', 'familyName'],
  page: ['displayname', 'honorificPrefix', 'givenName', 'familyName'],
  orgid: ['displayname', 'honorificPrefix', 'givenName', 'familyName'],
};

export const fieldMapping: Record<string, string> = {
  image: 'image',
  displayname: 'displayName',
  honorificPrefix: 'honorificPrefix',
  givenName: 'givenName',
  familyName: 'familyName',
  honorificSuffix: 'honorificSuffix',
  titleOfNobility: 'titleOfNobility',
  email: 'email',
  phone: 'phone',
  organization: 'organization',
  jobTitle: 'jobTitle',
  url: 'url',
  content: 'content',
  teasertext: 'teasertext',
  socialmedia: 'socialmedia',
  room: 'room',
  floor: 'floor',
  street: 'street',
  zip: 'zip',
  city: 'city',
  faumap: 'faumap',
  officehours: 'officehours',
  consultationhours: 'consultationhours',
  address: 'address',
};