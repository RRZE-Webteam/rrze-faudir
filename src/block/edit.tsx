import {__} from '@wordpress/i18n';
import {InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {
  PanelBody,
  CheckboxControl,
  ToggleControl,
  SelectControl,
  TextControl,
} from '@wordpress/components';
import {useState, useEffect, useMemo} from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';
import './editor.scss';
import {availableFields, requiredFields, formatFields, fieldMapping} from "./defaults";

interface EditProps {
  attributes: {
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
  };
  setAttributes: (attributes: Partial<EditProps["attributes"]>) => void;
  clientId: string;
  blockProps: any;
}

export default function Edit({attributes, setAttributes}: EditProps) {
  const [categories, setCategories] = useState([]);
  const [posts, setPosts] = useState([]);
  const [displayedPosts, setDisplayedPosts] = useState([]);
  const [isLoadingCategories, setIsLoadingCategories] = useState(true);
  const [isLoadingPosts, setIsLoadingPosts] = useState(false);
  const [defaultOrgNr, setDefaultOrgNr] = useState(null);
  const [renderKey, setRenderKey] = useState(0);

  const blockProps = useBlockProps();
  const {
    selectedCategory = '',
    selectedPosts = [],
    showCategory = '',
    showPosts = '',
    selectedPersonIds = '',
    selectedFormat = 'compact',
    selectedFields = [],
    role = '',
    orgnr = '',
    format_displayname = '',
    url = '',
    hideFields = [],
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
        .then((settings) => {
          console.log('DATA SETTINGS in component mount', settings);
          // Update the fields if defaults exist
          if (settings?.default_output_fields) {
            // Map PHP field names to JavaScript field names


            // Convert PHP field names to JavaScript field names
            const mappedFields = settings.default_output_fields
              .map((field) => fieldMapping[field])
              .filter((field) => field !== undefined); // Remove any unmapped fields

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
      .then((data) => {
        setCategories(data);
        setIsLoadingCategories(false);
      })
      .catch((error) => {
        console.error('Error fetching categories:', error);
        setIsLoadingCategories(false);
      });
  }, []);

  useEffect(() => {
    // Fetch default organization number on component mount
    apiFetch({
      path: '/wp/v2/settings/rrze_faudir_options',
    })
      .then((settings) => {
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
    const params = {
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
    })
      .then((data) => {
        setPosts(data);
        setDisplayedPosts(data.slice(0, 100));

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

  const togglePostSelection = (postId, personId) => {
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

  const toggleFieldSelection = (field) => {
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
  const handleFormatChange = (value) => {
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
        .then((settings) => {
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
  const handleSortChange = (value) => {
    setAttributes({sort: value});
    setRenderKey((prev) => prev + 1); // Force re-render
  };

  // Also update the renderKey when format_displayname changes
  const handleFormatDisplaynameChange = (value) => {
    setAttributes({format_displayname: value});
    if (value.length > 6) {
      setRenderKey((prev) => prev + 1); // Force re-render
    }
  };

  // Also render again if the orgnr changes
  const handleOrgNrChange = (value) => {
    setAttributes({orgnr: value});
    if (value.length > 8) {
      setRenderKey((prev) => prev + 1); // Force re-render
    }
  };

  // 1. Add memoization for expensive computations
  const memoizedPosts = useMemo(
    () =>
      posts.map((post) => ({
        id: post.id,
        title: post.title.rendered,
        personId: post.meta?.person_id,
      })),
    [posts]
  );

  // 2. Implement debouncing for the ServerSideRender
  const debouncedRenderKey = useDebounce(renderKey, 500);

  // Add this custom hook at the top of your file
  function useDebounce(value, delay) {
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
                            post.id,
                            post.meta?.person_id
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
                        label={
                          <>
                            {
                              availableFields[field]
                            }
                          </>
                        }
                        checked={selectedFields.includes(field)}
                        onChange={() => toggleFieldSelection(field)
                        }
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
            inputProps={{pattern: '\\d{10,}'}}
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
          <>
            { /* Format console log as WordPress shortcode */}

            <ServerSideRender
              key={debouncedRenderKey}
              block="rrze-faudir/block"
              attributes={{
                // Case 1: function + orgnr
                ...(attributes.role
                    ? {
                      role: attributes.role,
                      ...(attributes.orgnr && {
                        orgnr: attributes.orgnr,
                      }),
                      selectedFormat:
                      attributes.selectedFormat,
                      selectedFields:
                        attributes.selectedFields
                          .length > 0
                          ? attributes.selectedFields
                          : null, // Only pass if fields are selected
                      hideFields: attributes.hideFields, // Add hideFields
                      url: attributes.url,
                      sort: attributes.sort,
                      format_displayname: attributes.format_displayname,
                    }
                    : // Case 2: category
                    attributes.selectedCategory
                      ? {
                        selectedCategory:
                        attributes.selectedCategory,
                        selectedPersonIds:
                        attributes.selectedPersonIds,
                        selectedFormat:
                        attributes.selectedFormat,
                        selectedFields:
                          attributes.selectedFields
                            .length > 0
                            ? attributes.selectedFields
                            : null, // Only pass if fields are selected
                        hideFields: attributes.hideFields, // Add hideFields
                        url: attributes.url,
                        sort: attributes.sort,
                        format_displayname: attributes.format_displayname,
                      }
                      : // Case 3: selectedPersonIds
                      attributes.selectedPersonIds?.length > 0
                        ? {
                          selectedPersonIds:
                          attributes.selectedPersonIds,
                          selectedFields:
                            attributes.selectedFields
                              .length > 0
                              ? attributes.selectedFields
                              : null, // Only pass if fields are selected
                          hideFields: attributes.hideFields, // Add hideFields
                          selectedFormat:
                          attributes.selectedFormat,
                          url: attributes.url,
                          sort: attributes.sort,
                          format_displayname: attributes.format_displayname,
                        }
                        : // Case 4: orgnr als eigenständiger Fall
                        attributes.orgnr
                          ? {
                            orgnr: attributes.orgnr,
                            selectedFormat: attributes.selectedFormat,
                            selectedFields: attributes.selectedFields.length > 0 ? attributes.selectedFields : null,
                            hideFields: attributes.hideFields,
                            url: attributes.url,
                            sort: attributes.sort,
                            format_displayname: attributes.format_displayname,
                          }
                          : // Falls keiner der Fälle greift, leere Attribute übergeben
                          {
                            selectedFormat: attributes.selectedFormat,
                            selectedFields: attributes.selectedFields.length > 0 ? attributes.selectedFields : null,
                            hideFields: attributes.hideFields,
                            url: attributes.url,
                            sort: attributes.sort,
                            format_displayname: attributes.format_displayname,
                          }

                ),
              }}
            />
          </>
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
