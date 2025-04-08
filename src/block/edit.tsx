import {__} from '@wordpress/i18n';
import {InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {
  PanelBody,
  CheckboxControl,
  ToggleControl,
  SelectControl,
  TextControl,
} from '@wordpress/components';
import {useState, useEffect} from '@wordpress/element';
import CustomServerSideRender from "./components/CustomServerSideRender";
import apiFetch, {APIFetchOptions} from '@wordpress/api-fetch';
import {availableFields, formatFields, fieldMapping} from "./defaults";

interface EditProps {
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

interface SettingsRESTApi {
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
  const [renderKey, setRenderKey] = useState(0);

  const blockProps = useBlockProps();
  const {
    selectedCategory = '',
    selectedPosts = [],
    showCategory = false,
    showPosts = false,
    selectedPersonIds = [],
    selectedFormat = 'compact',
    selectedFields = [],
    role = '',
    orgnr = '',
    format_displayname = '',
    sort = 'familyName',
  } = attributes;

  useEffect(() => {
    // Check if this is a new block (no fields selected yet)
    if (
      !attributes.selectedFields ||
      attributes.selectedFields.length === 0
    ) {
      apiFetch({
        path: '/wp/v2/settings/rrze_faudir_options',
      })
        .then((settings: any) => {
          console.log('DATA SETTINGS in component mount', settings);
          // Update the fields if defaults exist
          if (settings?.default_output_fields) {
            // Map PHP field names to JavaScript field names


            // Convert PHP field names to JavaScript field names
            const mappedFields = settings.default_output_fields
              .map((field: string) => fieldMapping[field])
              .filter((field: string) => field !== undefined); // Remove any unmapped fields

            setAttributes({
              selectedFields: mappedFields,
            });
          }
          //	if ( settings?.person_roles ) {
          //		setAttributes( {
          //			setPersonRoles: settings.person_roles ,
          //		} );
          //	}
        })
        .catch((error) => {
          console.error('Error fetching default fields:', error);
        });
    }
  }, []); // Empty dependency array means this runs once when the block is created

  useEffect(() => {
    // Fetch categories from the REST API
    apiFetch({path: '/wp/v2/custom_taxonomy?per_page=100'})
      .then((data: WPCategory[]) => {
        setCategories(data);
      })
      .catch((error) => {
        console.error('Error fetching categories:', error);
      });
  }, []);

  useEffect(() => {
    // Fetch default organization number on component mount
    apiFetch({
      path: '/wp/v2/settings/rrze_faudir_options',
    })
      .then((settings: SettingsRESTApi) => {
        //	    console.error('DATA SETTINGS in component mount', settings);
        if (settings?.default_organization?.orgnr) {
          setDefaultOrgNr(settings.default_organization);
        }
        //		if ( settings?.person_roles ) {
        //			setAttributes( {
        //			    setPersonRoles: settings.person_roles ,
        //			} );
        //		}
      })
      .catch((error) => {
        console.error(
          'Error fetching default organization number:',
          error
        );
      });
  }, []); // Empty dependency array means this runs once on mount

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
        //setDisplayedPosts(data.slice(0, 100));

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
    setRenderKey((prev) => prev + 1);
  };

  const toggleFieldSelection = (field: string) => {
    const isFieldSelected = selectedFields.includes(field);
    let updatedSelectedFields;
    let updatedHideFields = attributes.hideFields || [];

    // Define name-related fields
    const nameFields = [
      'personalTitle',
      'givenName',
      'familyName',
      'honorificSuffix',
      'titleOfNobility',
    ];

    if (field === 'displayName') {
      if (isFieldSelected) {
        // If unchecking displayName, remove it and all name-related fields
        updatedSelectedFields = selectedFields.filter(
          (f) => !nameFields.includes(f) && f !== 'displayName'
        );
        updatedHideFields = [
          ...updatedHideFields,
          'displayName',
          ...nameFields,
        ];
      } else {
        // If checking displayName, add it and all name-related fields
        updatedSelectedFields = [
          ...selectedFields.filter(
            (f) => !nameFields.includes(f)
          ),
          'displayName',
          ...nameFields,
        ];
        updatedHideFields = updatedHideFields.filter(
          (f) => !nameFields.includes(f) && f !== 'displayName'
        );
      }
    } else if (nameFields.includes(field)) {
      if (isFieldSelected) {
        // If unchecking a name field, remove it and displayName
        updatedSelectedFields = selectedFields.filter(
          (f) => f !== field && f !== 'displayName'
        );
        updatedHideFields = [...updatedHideFields, field];
      } else {
        // If checking a name field, add just that field
        updatedSelectedFields = [
          ...selectedFields.filter((f) => f !== 'displayName'),
          field,
        ];
        updatedHideFields = updatedHideFields.filter(
          (f) => f !== field
        );
      }
    } else {
      // Handle non-name fields as before
      if (isFieldSelected) {
        updatedSelectedFields = selectedFields.filter(
          (f) => f !== field
        );
        updatedHideFields = [...updatedHideFields, field];
      } else {
        updatedSelectedFields = [...selectedFields, field];
        updatedHideFields = updatedHideFields.filter(
          (f) => f !== field
        );
      }
    }

    setAttributes({
      selectedFields: updatedSelectedFields,
      hideFields: updatedHideFields,
    });

    // Force re-render when fields are changed
    setRenderKey((prev) => prev + 1);
  };

  // Modify the format change handler
  const handleFormatChange = (value: string) => {
    setAttributes({selectedFormat: value});

    // Force re-render
    setRenderKey((prev) => prev + 1);

    // Only reset fields if explicitly changing format and no fields are selected
    if (
      !attributes.selectedFields ||
      attributes.selectedFields.length === 0
    ) {
      apiFetch({
        path: '/wp/v2/settings/rrze_faudir_options',
      })
        .then((settings: SettingsRESTApi) => {
          if (settings?.default_output_fields) {
            const formatSpecificFields =
              formatFields[value] || [];
            const filteredDefaultFields =
              settings.default_output_fields.filter((field) =>
                formatSpecificFields.includes(field)
              );
            setAttributes({
              selectedFields: filteredDefaultFields,
            });
          }
        })
        .catch((error) => {
          console.error('Error fetching default fields:', error);
        });
    }
  };

  // Transform attributes to match the shortcode format
  const blockAttributes = {
    selectedPersonIds: attributes.selectedPersonIds,
    selectedFields: attributes.selectedFields,
    selectedFormat: attributes.selectedFormat,
    selectedCategory: attributes.selectedCategory,
    role: attributes.role,
    ...(attributes.orgnr && {orgnr: attributes.orgnr}), // Only include orgnr if it's not empty
    url: attributes.url,
    format_displayname: attributes.format_displayname,

  };

  // Add ServerSideRender with debounce
  const [key, setKey] = useState(0);
  useEffect(() => {
    const timer = setTimeout(() => {
      setKey((prev) => prev + 1);
    }, 300); // 300ms debounce
    return () => clearTimeout(timer);
  }, [...Object.values(blockAttributes), sort, format_displayname]); // Use blockAttributes instead of attributes

  // Also update the renderKey when sort changes
  const handleSortChange = (value: string) => {
    setAttributes({sort: value});
    setRenderKey((prev) => prev + 1); // Force re-render
  };

  // Also update the renderKey when format_displayname changes
  const handleFormatDisplaynameChange = (value: string) => {
    setAttributes({format_displayname: value});
    if (value.length > 6) {
      setRenderKey((prev) => prev + 1); // Force re-render
    }
  };

  // Also render again if the orgnr changes
  const handleOrgNrChange = (value: string) => {
    setAttributes({orgnr: value});
    if (value.length > 8) {
      setRenderKey((prev) => prev + 1); // Force re-render
    }
  };

  // 2. Implement debouncing for the ServerSideRender
  const debouncedRenderKey = useDebounce(renderKey, 500);

  // Delay for UserInput?
  function useDebounce(value: number, delay: number) {
    const [debouncedValue, setDebouncedValue] = useState(value);

    useEffect(() => {
      const handler = setTimeout(() => {
        setDebouncedValue(value);
      }, delay);

      return () => {
        clearTimeout(handler);
      };
    }, [value, delay]);

    return debouncedValue;
  }

  // Add debug output to the rendered component
  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Settings', 'rrze-faudir')}>
          { /* Toggle for Category */}
          <ToggleControl
            label={__('Show Category', 'rrze-faudir')}
            checked={showCategory}
            onChange={() =>
              setAttributes({showCategory: !showCategory})
            }
          />

          { /* Category Selection */}
          {showCategory && (
            <>
              <h4>{__('Select Category', 'rrze-faudir')}</h4>
              {categories.map((category) => (
                <CheckboxControl
                  key={category.id}
                  label={category.name}
                  checked={
                    selectedCategory === category.name
                  }
                  onChange={() => {
                    // If the category is already selected, unselect it by setting to empty string
                    const newCategory =
                      selectedCategory === category.name ? '' : category.name;
                    setAttributes({
                      selectedCategory: newCategory,
                      // Clear selected posts when unchecking category
                      selectedPosts:
                        newCategory === '' ? [] : selectedPosts,
                      selectedPersonIds:
                        newCategory === '' ? [] : selectedPersonIds,
                    });
                  }}
                />
              ))}
            </>
          )}

          { /* Toggle for Posts */}
          <ToggleControl
            label={__('Show Persons', 'rrze-faudir')}
            checked={showPosts}
            onChange={() =>
              setAttributes({showPosts: !showPosts})
            }
          />

          { /* Posts Selection */}
          {showPosts && (
            <>
              <h4>{__('Select Persons', 'rrze-faudir')}</h4>

              {isLoadingPosts ? (
                <p>
                  {__('Loading persons...', 'rrze-faudir')}
                </p>
              ) : posts.length > 0 ? (
                <>
                  {posts.map((post) => {
                    return (
                      <CheckboxControl
                        key={post.id}
                        label={post.title.rendered}
                        checked={
                          Array.isArray(
                            selectedPosts
                          ) &&
                          selectedPosts.includes(
                            post.id
                          )
                        }
                        onChange={() =>
                          togglePostSelection(
                            post.id
                          )
                        }
                      />
                    );
                  })}
                </>
              ) : (
                <p>
                  {__('No posts available.', 'rrze-faudir')}
                </p>
              )}
            </>
          )}

          { /* Format Selection */}
          <SelectControl
            label={__('Select Format', 'rrze-faudir')}
            value={selectedFormat || 'list'}
            options={[
              {
                value: 'list',
                label: __('List', 'rrze-faudir'),
              },
              {
                value: 'table',
                label: __('Table', 'rrze-faudir'),
              },
              {
                value: 'card',
                label: __('Card', 'rrze-faudir'),
              },
              {
                value: 'compact',
                label: __('Compact', 'rrze-faudir'),
              },
              {
                value: 'page',
                label: __('Page', 'rrze-faudir'),
              },
            ]}
            onChange={handleFormatChange}
          />

          { /* Fields Selection */}
          {Object.keys(formatFields).map((format) => {
            if (selectedFormat === format) {
              return (
                <div key={format}>
                  <h4>
                    {__('Select Fields', 'rrze-faudir')}
                  </h4>
                  {formatFields[format].map((field) => (
                    <div
                      key={field}
                      style={{marginBottom: '8px'}}
                    >
                      <CheckboxControl
                        label={String(availableFields[field])}
                        checked={selectedFields.includes(field)}
                        onChange={() => toggleFieldSelection(field)}
                      />
                    </div>
                  ))}
                </div>
              );
            }
            return null;
          })}

          { /* New Input Fields for Group Id, Function, Organization Nr */}

          <TextControl
            label={__('Organization Number', 'rrze-faudir')}
            value={orgnr}
            onChange={handleOrgNrChange}
            type="text"
            help={__('Please enter at least 10 digits.', 'rrze-faudir')}
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

          <SelectControl
            label={__('Sort by', 'rrze-faudir')}
            value={sort}
            options={[
              {
                value: 'familyName',
                label: __('Last Name', 'rrze-faudir'),
              },
              {
                value: 'title_familyName',
                label: __('Title and Last Name', 'rrze-faudir'),
              },
              {
                value: 'head_first',
                label: __('Head of Department First', 'rrze-faudir'),
              },
              {
                value: 'prof_first',
                label: __('Professors First', 'rrze-faudir'),
              },
              {
                value: 'identifier_order',
                label: __('Identifier Order', 'rrze-faudir'),
              },
            ]}
            onChange={handleSortChange}
          />
          <TextControl
            label={__('Role', 'rrze-faudir')}
            value={role}
            onChange={(value) => setAttributes({role: value})}
            type="text"
          />
          <TextControl
            label={__('Change display format', 'rrze-faudir')}
            value={format_displayname}
            onChange={handleFormatDisplaynameChange}
            type="text"
          />
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        {attributes.selectedPersonIds?.length > 0 ||
        attributes.selectedCategory ||
        attributes.orgnr ||
        (attributes.role &&
          (attributes.orgnr || defaultOrgNr)) ? (
          <CustomServerSideRender attributes={blockAttributes} debouncedRenderKey={key}/>
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
