// deprecated.ts
import { BlockDeprecation } from '@wordpress/blocks';

// The old attributes from the previous Version
interface AttributesV1 {
  selectedCategory: string;
  selectedPosts: string[];
  selectedPersonIds: string[];
  selectedFormat: string;
  selectedFields: string[];
  role: string;
  orgnr: string;
  url: string;
  hideFields: string[];
  showCategory: boolean;
  showPosts: boolean;
  sort: string;
  format_displayname: string;
  identifier: string;
}

const migrateV2_2_11 = ( attributes: AttributesV1 ) => {
  const newAttributes = {
    ...attributes,
    initialSetup: false,
    identifier: attributes.identifier || ''
  };

  if (newAttributes.selectedFormat === 'kompakt') {
    newAttributes.selectedFormat = 'compact';
  }

  return newAttributes;
};

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
      hideFields: {
        type: 'array',
        default: [],
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
        default: ''
      },
    },
    save: () => null,
    migrate: migrateV2_2_11,
    isEligible( { initialSetup } ) {
      return typeof initialSetup === 'undefined';
    },
  },
];

export default deprecated;
