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

export interface MediaMetadata {
  alt: string;
  author: string;
  authorLink: string;
  caption: string;
  compat: {
    item: string;
    meta: string;
  }
  context: string;
  date: Date;
  dateFormatted: string;
  description: string;
  editLink: string;
  filename: string;
  filesizeHumanReadable: string;
  filesizeInBytes: number;
  height: number;
  icon: string;
  id: number;
  link: string;
  menuOrder: number;
  meta: boolean;
  mime: string;
  modified: Date;
  name: string;
  nonces: {
    update: string;
    delete: string;
    edit: string;
  }
  orientation: string;
  sizes: {
    thumbnail: {
      height: number;
      orientation: string;
      url: string;
      width: number;
    };
    full: {
      height: number;
      orientation: string;
      url: string;
      width: number;
    };
    large: {
      height: number;
      orientation: string;
      url: string;
      width: number;
    };
    medium: {
      height: number;
      orientation: string;
      url: string;
      width: number;
    };
  }
  status: string;
  subtype: string;
  title: string;
  type: "image" | "video"
  uploadedTo: number;
  uploadedToLink: string;
  uploadedToTitle: string;
  url: string;
  width: number;
}