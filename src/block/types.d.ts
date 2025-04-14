export interface EditProps {
  attributes: {
    selectedCategory: string;
    selectedPosts: number[];
    selectedPersonIds: number[];
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
    display: "person" | "org";
    orgid: string;
    identifier: string;
  };
  setAttributes: (attributes: Partial<EditProps["attributes"]>) => void;
  clientId: string;
  blockProps: any;
}

export interface WPCategory {
  id: number;
  count: number;
  description: string;
  link: string;
  name: string;
  slug: string;
  taxonomy: string;
  parent: number;
  meta?: any;
  _links?: any;
}

export interface SettingsRESTApi {
  default_output_fields: string[];
  available_fields: Record<string, string>;
  avaible_fields_byformat: {
    [format: string]: string[];
  };
  person_roles: PersonRoles[];
  default_organization: DefaultOrganization | null;
  available_formats_by_display: {
    person: string[];
    org: string[];
  };
  format_names: {
    [format: string]: string;
  };
}

export interface PersonRoles {
  [roleKey: string]: string;
}

export interface DefaultOrganization {
  orgnr?: string;
}

export interface CustomPersonParams {
  per_page: number;
  _fields: string;
  orderby: string;
  order: string;
  custom_taxonomy?: string;
}

export interface CustomPersonRESTApi {
  id: number;
  date: string;
  date_gmt: string;
  guid: {
    rendered: string;
  }
  modified: string;
  modified_gmt: string;
  slug: string;
  status: string;
  type: string;
  link: string;
  title: {
    rendered: string;
  }
  content: {
    rendered: string;
    protected: boolean;
  }
  featured_media: number;
  template: string;
  meta: {
    person_id: number;
    person_name: string;
  }
  custom_taxonomy?: number[];
  class_list: {
    [key: string]: string;
  }
  _links: any;
}