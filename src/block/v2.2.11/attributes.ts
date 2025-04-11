import {BlockAttributes} from "@wordpress/blocks";

export const attributes: BlockAttributes = {
  selectedCategory: {
    type: "string",
    default: ""
  },
  selectedPosts: {
    type: "array",
    default: []
  },
  selectedPersonIds: {
    type: "array",
    default: []
  },
  selectedFormat: {
    type: "string",
    default: "kompakt"
  },
  selectedFields: {
    type: "array",
    default: []
  },
  role: {
    type: "string",
    default: ""
  },
  orgnr: {
    type: "string",
    default: ""
  },
  url: {
    type: "string",
    default: ""
  },

  hideFields: {
    type: "array",
    default: []
  },
  showCategory: {
    type: "boolean",
    default: false
  },
  showPosts: {
    type: "boolean",
    default: false
  },
  sort: {
    type: "string",
    default: "familyName"
  },
  format_displayname: {
    type: "string",
    default: ""
  }
}

export interface AttributesV1 {
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
  initialSetup: boolean;
}