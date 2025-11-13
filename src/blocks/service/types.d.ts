export interface EditProps {
  attributes: {
    orgid: string;
    displayText: string;
    identifier: string;
    imageURL: string;
    imageId: number;
    imageWidth: number;
    imageHeight: number;
    contact: {
      phone: string;
      mail: string;
      url: string;
      street: string;
      zip: string;
      city: string;
    };
    name: string;
    visibleFields: string[];
    officeHours: OfficeHour[];
  };
  setAttributes: (attributes: Partial<EditProps["attributes"]>) => void;
  clientId: string;
  blockProps: any;
}

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
    alternateName: string;
    consultationHours?: string[];
    consultationHoursByAgreement?: string;
    consultationHoursContactHint?: string;
    consultationHoursContactType?: string;
    content: string[];
    disambiguatingDescription: string;
    identifier: string;
    internalAddress?: string[];
    longDescription: {
      de: string;
      en: string;
    };
    name: string;
    officeHours?: OfficeHour[];
    parentOrganization: string[];
    postalAddress: string[];
    subOrganization: string[];
    socials: string[];
  }
}

export interface OfficeHour {
  weekday?: string | number;
  from?: string;
  to?: string;
}