import { BlockDeprecation } from '@wordpress/blocks';

interface AttributesV1 {
  selectedCategory: string;
  selectedPosts: string[];
  selectedPersonIds: string[];
  selectedFormat: string;
  selectedFields: string[];
  role: string;
  orgnr: string;
  url: string;
  showCategory: boolean;
  showPosts: boolean;
  sort: string;
  format_displayname: string;
  identifier: string;
}

interface MigratedAttributes extends AttributesV1 {
  initialSetup: boolean;
}

function migrateV2_2_11(attributes: AttributesV1): MigratedAttributes {
  const newAttributes: MigratedAttributes = {
    ...attributes,
    initialSetup: false,
    identifier: attributes.identifier || ''
  };

  if (newAttributes.selectedFormat === 'kompakt') {
    newAttributes.selectedFormat = 'compact';
  }

  return newAttributes;
}

const deprecated: BlockDeprecation<AttributesV1>[] = [
  {
    attributes: {
      selectedCategory: {
        type: 'string',
        default: '',
      },
      selectedPosts: {
        type: 'array',
        default: [],
      },
      selectedPersonIds: {
        type: 'array',
        default: [],
      },
      selectedFormat: {
        type: 'string',
        default: 'kompakt',
      },
      selectedFields: {
        type: 'array',
        default: [],
      },
      role: {
        type: 'string',
        default: '',
      },
      orgnr: {
        type: 'string',
        default: '',
      },
      url: {
        type: 'string',
        default: '',
      },
      showCategory: {
        type: 'boolean',
        default: false,
      },
      showPosts: {
        type: 'boolean',
        default: false,
      },
      sort: {
        type: 'string',
        default: 'familyName',
      },
      format_displayname: {
        type: 'string',
        default: '',
      },
      identifier: {
        type: 'string',
        default: '',
      },
    },
    save() {
      return null;
    },
    migrate: migrateV2_2_11,
    isEligible(attributes) {
      return (
        typeof (attributes as { initialSetup?: boolean }).initialSetup === 'undefined' ||
        attributes.selectedFormat === 'kompakt'
      );
    },
  },
];

export default deprecated;