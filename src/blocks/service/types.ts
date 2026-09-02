import type { BlockEditProps } from "@wordpress/blocks";

export interface ServiceBlockAttributes extends Record<string, unknown> {
  orgid?: string;
  displayText?: string;
  imageURL?: string;
  imageId?: number;
  imageWidth?: number;
  imageHeight?: number;
  visibleFields?: string[];
}

export type EditProps = Pick<
  BlockEditProps<ServiceBlockAttributes>,
  "attributes" | "setAttributes"
>;

export interface OrganizationResponseProps {
  data: {
    additionalType?: string;
    address?: {
      phone?: string;
      mail?: string;
      url?: string;
      street?: string;
      zip?: string;
      city?: string;
      faumap?: string;
    };
    alternateName?: string;
    consultationHours?: string[];
    consultationHoursByAgreement?: string;
    consultationHoursContactHint?: string;
    consultationHoursContactType?: string;
    content?: OrgDataShort[];
    disambiguatingDescription?: string;
    identifier?: string;
    internalAddress?: string[];
    longDescription?: {
      de?: string;
      en?: string;
    };
    name?: string;
    officeHours?: OfficeHour[];
    parentOrganization?: string[];
    postalAddress?: string[];
    subOrganization?: string[];
    socials?: string[];
  };
}

export interface OfficeHour {
  weekday?: string | number;
  from?: string;
  to?: string;
}

export interface OrgDataShort {
  type: string;
  function?: string;
  text?: {
    de?: string;
    en?: string;
  };
  functionLabel?: {
    de?: string;
    en?: string;
  };
  custom?: boolean;
}
