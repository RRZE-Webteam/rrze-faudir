import {__} from '@wordpress/i18n';
import {InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {
  PanelBody,
  ToggleControl,
  TextControl,
} from '@wordpress/components';
import {useState, useEffect} from '@wordpress/element';
import CustomServerSideRender from "./components/CustomServerSideRender";
import apiFetch, {APIFetchOptions} from '@wordpress/api-fetch';
import {fieldMapping} from "./defaults";
import OrganizationNumberDetector from "./components/OrganizationNumberDetector";
import PersonSelector from "./components/PersonSelector";
import CategorySelector from "./components/CategorySelector";
import FormatSelector from "./components/FormatSelector";
import ShowHideSelector from "./components/ShowHideSelector";
import NameFormatSelector from "./components/NameFormatSelector";

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
  };
  setAttributes: (attributes: Partial<EditProps["attributes"]>) => void;
  clientId: string;
  blockProps: any;
}

interface WPCategory {
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
  business_card_title: string;
  person_roles: PersonRoles[];
  default_organization: DefaultOrganization | null;
}

interface PersonRoles {
  [roleKey: string]: string;
}

interface DefaultOrganization {
  orgnr?: number;
}

interface CustomPersonParams {
  per_page: number;
  _fields: string;
  orderby: string;
  order: string;
  custom_taxonomy?: string;
}

interface CustomPersonRESTApi {
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


export default function Edit({attributes, setAttributes}: EditProps) {
  const [categories, setCategories] = useState([]);
  const [posts, setPosts] = useState([]);
  const [isLoadingPosts, setIsLoadingPosts] = useState(false);
  const [defaultOrgNr, setDefaultOrgNr] = useState(null);

  const blockProps = useBlockProps();
  const {
    selectedCategory = '',
    selectedPosts = [],
    showCategory = false,
    showPosts = false,
    selectedPersonIds = [],
    role = '',
    orgnr = '',
  } = attributes;

  useEffect(() => {
    if (
      !attributes.selectedFields ||
      attributes.selectedFields.length === 0
    ) {
      apiFetch({
        path: '/wp/v2/settings/rrze_faudir_options',
      })
        .then((settings: any) => {
          console.log('DATA SETTINGS in component mount', settings);
          if (settings?.default_output_fields) {
            const mappedFields = settings.default_output_fields
              .map((field: string) => fieldMapping[field])
              .filter((field: string) => field !== undefined); // Remove any unmapped fields

            setAttributes({
              selectedFields: mappedFields,
            });
          }
        })
        .catch((error) => {
          console.error('Error fetching default fields:', error);
        });
    }
  }, []);

  useEffect(() => {
    apiFetch({path: '/wp/v2/custom_taxonomy?per_page=100'})
      .then((data: WPCategory[]) => {
        setCategories(data);
      })
      .catch((error) => {
        console.error('Error fetching categories:', error);
      });
  }, []);

  useEffect(() => {
    apiFetch({
      path: '/wp/v2/settings/rrze_faudir_options',
    })
      .then((settings: SettingsRESTApi) => {
        if (settings?.default_organization?.orgnr) {
          setDefaultOrgNr(settings.default_organization);
        }
      })
      .catch((error) => {
        console.error(
          'Error fetching default organization number:',
          error
        );
      });
  }, []);

  useEffect(() => {
    setIsLoadingPosts(true);
    const params: CustomPersonParams = {
      per_page: 100,
      _fields: 'id,title,meta',
      orderby: 'title',
      order: 'asc',
    };

    if (selectedCategory) {
      params.custom_taxonomy = selectedCategory;
    }

    apiFetch({
      path: '/wp/v2/custom_person?per_page=100',
      params: params,
    } as APIFetchOptions)
      .then((data: CustomPersonRESTApi[]) => {
        setPosts(data);
        if (selectedCategory) {
          const categoryPosts = data.map((post) => post.id);
          const categoryPersonIds = data
            .map((post) => post.meta?.person_id)
            .filter(Boolean);

          setAttributes({
            selectedPosts: categoryPosts,
            selectedPersonIds: categoryPersonIds,
          });
        }
        setIsLoadingPosts(false);
      })
      .catch((error) => {
        console.error('Error fetching posts:', error);
        setIsLoadingPosts(false);
      });
  }, [selectedCategory, orgnr]);

  const togglePostSelection = (postId: number) => {
    const updatedSelectedPosts = selectedPosts.includes(postId)
      ? selectedPosts.filter((id) => id !== postId)
      : [...selectedPosts, postId];
    const updatedPersonIds = updatedSelectedPosts
      .map((id) => {
        const post = posts.find((p) => p.id === id);
        return post?.meta?.person_id || null;
      })
      .filter(Boolean);

    setAttributes({
      selectedPosts: updatedSelectedPosts,
      selectedPersonIds: updatedPersonIds,
    });
  };

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Settings', 'rrze-faudir')}>

          <ToggleControl
            label={__('Show Category', 'rrze-faudir')}
            checked={showCategory}
            onChange={() =>
              setAttributes({showCategory: !showCategory})
            }
          />

          {showCategory && (
            <CategorySelector
              categories={categories}
              selectedCategory={selectedCategory}
              selectedPosts={selectedPosts}
              selectedPersonIds={selectedPersonIds}
              setAttributes={setAttributes}
            />
          )}

          <ToggleControl
            label={__('Show Persons', 'rrze-faudir')}
            checked={showPosts}
            onChange={() =>
              setAttributes({showPosts: !showPosts})
            }
          />

          { /* Person Selection */}
          {showPosts && (
            <PersonSelector
              isLoadingPosts={isLoadingPosts}
              posts={posts}
              selectedPosts={selectedPosts}
              togglePostSelection={togglePostSelection}
            />
          )}
          <FormatSelector attributes={attributes} setAttributes={setAttributes}/>
          <ShowHideSelector attributes={attributes} setAttributes={setAttributes}/>
          <OrganizationNumberDetector
            attributes={attributes}
            setAttributes={setAttributes}
          />
          {defaultOrgNr && !orgnr && (
            <div
              style={{
                padding: '8px',
                backgroundColor: '#f0f0f0',
                borderLeft: '4px solid #007cba',
                marginTop: '5px',
                marginBottom: '15px',
              }}
            >
							<span
                className="dashicons dashicons-info"
                style={{marginRight: '5px'}}
              ></span>
              {__('Default organization will be used if empty.', 'rrze-faudir')}
            </div>
          )}
          <TextControl
            label={__('Role', 'rrze-faudir')}
            value={role}
            onChange={(value) => setAttributes({role: value})}
            type="text"
          />
          <NameFormatSelector attributes={attributes} setAttributes={setAttributes}/>
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        {attributes.selectedPersonIds?.length > 0 ||
        attributes.selectedCategory ||
        attributes.orgnr ||
        (attributes.role &&
          (attributes.orgnr || defaultOrgNr)) ? (
          <CustomServerSideRender attributes={attributes}/>
        ) : (
          <div
            style={{
              padding: '20px',
              backgroundColor: '#f8f9fa',
              textAlign: 'center',
            }}
          >
            <p>
              {attributes.role
                ? defaultOrgNr
                  ? __('Using default organization.', 'rrze-faudir')
                  : __('Please configure a default organization in the plugin settings or add an organization ID to display results.', 'rrze-faudir')
                : __('Please select persons or a category to display using the sidebar controls.', 'rrze-faudir')}
            </p>
          </div>
        )}
      </div>
    </>
  );
}
