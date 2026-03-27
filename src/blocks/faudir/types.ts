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
  post_language?: string;
  meta: {
    person_id: string;
  };
  custom_taxonomy?: number[];
  class_list: {
    [key: string]: string;
  };
  _links: any;
}