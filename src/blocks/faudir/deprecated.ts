import { BlockDeprecation } from "@wordpress/blocks";
import type { FaudirBlockAttributes } from "./types";

interface AttributesV1 extends Record<string, unknown> {
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

function migrateV2_2_11(
  attributes: Record<string, unknown>,
): FaudirBlockAttributes {
  const previousAttributes = attributes as AttributesV1;
  const newAttributes: FaudirBlockAttributes = {
    ...previousAttributes,
    selectedPosts: previousAttributes.selectedPosts
      .map(Number)
      .filter(Number.isFinite),
    initialSetup: false,
    identifier: previousAttributes.identifier || "",
    order: "asc",
    display: "person",
    orgid: "",
  };

  if (newAttributes.selectedFormat === "kompakt") {
    newAttributes.selectedFormat = "compact";
  }

  return newAttributes;
}

const deprecated: BlockDeprecation<FaudirBlockAttributes>[] = [
  {
    attributes: {
      selectedCategory: {
        type: "string",
        default: "",
      },
      selectedPosts: {
        type: "array",
        default: [],
      },
      selectedPersonIds: {
        type: "array",
        default: [],
      },
      selectedFormat: {
        type: "string",
        default: "kompakt",
      },
      selectedFields: {
        type: "array",
        default: [],
      },
      role: {
        type: "string",
        default: "",
      },
      orgnr: {
        type: "string",
        default: "",
      },
      url: {
        type: "string",
        default: "",
      },
      showCategory: {
        type: "boolean",
        default: false,
      },
      showPosts: {
        type: "boolean",
        default: false,
      },
      sort: {
        type: "string",
        default: "familyName",
      },
      format_displayname: {
        type: "string",
        default: "",
      },
      identifier: {
        type: "string",
        default: "",
      },
    },
    save() {
      return null;
    },
    migrate: migrateV2_2_11,
    isEligible(attributes) {
      return (
        typeof attributes.initialSetup === "undefined" ||
        attributes.selectedFormat === "kompakt"
      );
    },
  },
];

export default deprecated;
