export interface EditProps {
  attributes: {
    selectedCategory: string;
    selectedPosts: number[];
    selectedPersonIds: string[];
    selectedFormat: string;
    selectedFields: string[];
    role: string;
    orgnr: string;
    url: string;
    showCategory: boolean;
    showPosts: boolean;
    sort: string;
    order: string;
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
  show_output_fields_person_default: string[];
  show_output_fields_person_page: string[];
  show_output_fields_org_default: string[];
  available_fields: Record<string, string>;
  available_fields_org: Record<string, string>;
  avaible_fields_byformat: {
    [format: string]: string[];
  };
  default_organization: DefaultOrganization | null;
  format_names: {
    [format: string]: string;
  };
}

export interface DefaultOrganization {
  ids?: string[];
  name?: string;
  orgnr?: string;
}

export interface CustomPersonParams {
  per_page?: number;
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
  };
  modified: string;
  modified_gmt: string;
  slug: string;
  status: string;
  type: string;
  link: string;
  title: {
    rendered: string;
  };
  content: {
    rendered: string;
    protected: boolean;
  };
  featured_media: number;
  template: string;
  meta: {
    person_id: string;
    person_name: string;
  };
  custom_taxonomy?: number[];
  class_list: {
    [key: string]: string;
  };
  _links: any;
}