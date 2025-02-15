import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, CheckboxControl, ToggleControl, SelectControl, TextControl } from '@wordpress/components';
import { useState, useEffect, useMemo } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
    // Enhanced translation test
  

    const [categories, setCategories] = useState([]);
    const [posts, setPosts] = useState([]);
    const [isLoadingCategories, setIsLoadingCategories] = useState(true);
    const [isLoadingPosts, setIsLoadingPosts] = useState(false);
    const [defaultButtonText, setDefaultButtonText] = useState('');
    const [defaultOrgNr, setDefaultOrgNr] = useState(null);
    const [renderKey, setRenderKey] = useState(0);

    const blockProps = useBlockProps();
    const {
        selectedCategory = '',
        selectedPosts = [],
        showCategory = '',
        showPosts = '',
        selectedPersonIds = '',
        selectedFormat = 'kompakt',
        selectedFields = [],
        groupId = '',
        function: functionValue = '',
        orgnr = '',
        url = '',
        buttonText = '',
        hideFields = [],
        sort = 'last_name',
    } = attributes;

    const availableFields = {
        displayName: __('Display Name', 'rrze-faudir'),
        personalTitle: __('Academic Title', 'rrze-faudir'),
        givenName: __('First Name', 'rrze-faudir'),
        familyName: __('Last Name', 'rrze-faudir'),
        personalTitleSuffix: __('Academic Suffix', 'rrze-faudir'),
        titleOfNobility: __('Title of Nobility', 'rrze-faudir'),
        email: __('Email', 'rrze-faudir'),
        phone: __('Phone', 'rrze-faudir'),
        organization: __('Organization', 'rrze-faudir'),
        function: __('Function', 'rrze-faudir'),
        url: __('Url', 'rrze-faudir'),
        kompaktButton: __('Kompakt Button', 'rrze-faudir'),
        content: __('Content', 'rrze-faudir'),
        teasertext: __('Teasertext', 'rrze-faudir'),
        socialmedia: __('Social Media and Websites', 'rrze-faudir'),
        workplaces: __('Workplaces', 'rrze-faudir'),
        room: __('Room', 'rrze-faudir'),
        floor: __('Floor', 'rrze-faudir'),
        street: __('Street', 'rrze-faudir'),
        zip: __('ZIP Code', 'rrze-faudir'),
        city: __('City', 'rrze-faudir'),
        faumap: __('FAU Map', 'rrze-faudir'),
        officehours: __('Office Hours', 'rrze-faudir'),
        consultationhours: __('Consultation Hours', 'rrze-faudir'),
    };

    const formatFields = {
        card: [
            'displayName',
            'personalTitle',
            'givenName',
            'familyName',
            'personalTitleSuffix',
            'email',
            'phone',
            'function',
            'socialmedia',
            'titleOfNobility',
        ],
        table: [
            'displayName',
            'personalTitle',
            'givenName',
            'familyName',
            'personalTitleSuffix',
            'email',
            'phone',
            'url',
            'socialmedia',
            'titleOfNobility',
        ],
        list: [
            'displayName',
            'personalTitle',
            'givenName',
            'familyName',
            'personalTitleSuffix',
            'email',
            'phone',
            'url',
            'teasertext',
            'titleOfNobility',
        ],
        kompakt: Object.keys(availableFields),
        page: Object.keys(availableFields),
    };

    // Define required fields for each format
    const requiredFields = {
        card: ['display_name', 'academic_title', 'first_name', 'last_name'],
        table: ['display_name', 'academic_title', 'first_name', 'last_name'],
        list: ['display_name', 'academic_title', 'first_name', 'last_name'],
        kompakt: ['display_name', 'academic_title', 'first_name', 'last_name'],
        page: ['display_name', 'academic_title', 'first_name', 'last_name']
    };

    useEffect(() => {
        // Check if this is a new block (no fields selected yet)
        if (!attributes.selectedFields || attributes.selectedFields.length === 0) {
            apiFetch({ 
                path: '/wp/v2/settings/rrze_faudir_options'
            }).then((settings) => {
                // Update the fields if defaults exist
                if (settings?.default_output_fields) {
                    // Map PHP field names to JavaScript field names
                    const fieldMapping = {
                        'display_name': 'displayName',
                        'academic_title': 'personalTitle',
                        'first_name': 'givenName',
                        'last_name': 'familyName',
                        'academic_suffix': 'personalTitleSuffix',
                        'nobility_title': 'titleOfNobility',
                        'email': 'email',
                        'phone': 'phone',
                        'organization': 'organization',
                        'function': 'function',
                        'url': 'url',
                        'kompaktButton': 'kompaktButton',
                        'content': 'content',
                        'teasertext': 'teasertext',
                        'socialmedia': 'socialmedia',
                        'workplaces': 'workplaces',
                        'room': 'room',
                        'floor': 'floor',
                        'street': 'street',
                        'zip': 'zip',
                        'city': 'city',
                        'faumap': 'faumap',
                        'officehours': 'officehours',
                        'consultationhours': 'consultationhours'
                    };

                    // Convert PHP field names to JavaScript field names
                    const mappedFields = settings.default_output_fields
                        .map(field => fieldMapping[field])
                        .filter(field => field !== undefined); // Remove any unmapped fields

                    setAttributes({
                        selectedFields: mappedFields
                    });
                }
            }).catch((error) => {
                console.error('Error fetching default fields:', error);
            });
        }
    }, []); // Empty dependency array means this runs once when the block is created

    useEffect(() => {
        // Fetch categories from the REST API
        apiFetch({ path: '/wp/v2/custom_taxonomy?per_page=100' })
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
            path: '/wp/v2/settings/rrze_faudir_options'
        }).then((settings) => {
            if (settings?.default_organization?.orgnr) {
                setDefaultOrgNr(settings.default_organization);
               
            }
        }).catch((error) => {
            console.error('Error fetching default organization number:', error);
        });
    }, []); // Empty dependency array means this runs once on mount

    useEffect(() => {
        setIsLoadingPosts(true);
        const params = {
            per_page: 100,
            _fields: 'id,title,meta',
            orderby: 'title',
            order: 'asc'
        };

        if (selectedCategory) {
            params.custom_taxonomy = selectedCategory;
        }

    

        apiFetch({ 
            path: '/wp/v2/custom_person?per_page=100',
            params: params
        })
        .then((data) => {
            setPosts(data);
            setDisplayedPosts(data.slice(0, 100));
            
            if (selectedCategory) {
                const categoryPosts = data.map(post => post.id);
                const categoryPersonIds = data
                    .map(post => post.meta?.person_id)
                    .filter(Boolean);
                
                setAttributes({
                    selectedPosts: categoryPosts,
                    selectedPersonIds: categoryPersonIds
                });
            }
            setIsLoadingPosts(false);
        })
        .catch((error) => {
            console.error('Error fetching posts:', error);
            setIsLoadingPosts(false);
        });
    }, [selectedCategory, functionValue, orgnr]);

    useEffect(() => {
        if (!buttonText) {
            apiFetch({ 
                path: '/wp/v2/settings/rrze_faudir_options'
            }).then((settings) => {
                if (settings?.business_card_title) {
                    setDefaultButtonText(settings.business_card_title);
                    setAttributes({ buttonText: settings.business_card_title });
                }
            }).catch((error) => {
                console.error('Error fetching button text:', error);
            });
        }
    }, []); // Empty dependency array means this runs once on mount

    const togglePostSelection = (postId, personId) => {
        const updatedSelectedPosts = selectedPosts.includes(postId)
            ? selectedPosts.filter((id) => id !== postId)
            : [...selectedPosts, postId];
        const updatedPersonIds = updatedSelectedPosts.map(id => {
            const post = posts.find(p => p.id === id);
            return post?.meta?.person_id || null;
        }).filter(Boolean);

        setAttributes({
            selectedPosts: updatedSelectedPosts,
            selectedPersonIds: updatedPersonIds,
        });
        setRenderKey(prev => prev + 1);
    };
    

    const toggleFieldSelection = (field) => {
        const isFieldSelected = selectedFields.includes(field);
        let updatedSelectedFields;
        let updatedHideFields = attributes.hideFields || [];

        // Define name-related fields
        const nameFields = ['personalTitle', 'givenName', 'familyName', 'personalTitleSuffix', 'titleOfNobility'];

        if (field === 'displayName') {
            if (isFieldSelected) {
                // If unchecking displayName, remove it and all name-related fields
                updatedSelectedFields = selectedFields.filter(f => !nameFields.includes(f) && f !== 'displayName');
                updatedHideFields = [...updatedHideFields, 'displayName', ...nameFields];
            } else {
                // If checking displayName, add it and all name-related fields
                updatedSelectedFields = [
                    ...selectedFields.filter(f => !nameFields.includes(f)),
                    'displayName',
                    ...nameFields
                ];
                updatedHideFields = updatedHideFields.filter(f => !nameFields.includes(f) && f !== 'displayName');
            }
        } else if (nameFields.includes(field)) {
            if (isFieldSelected) {
                // If unchecking a name field, remove it and displayName
                updatedSelectedFields = selectedFields.filter(f => f !== field && f !== 'displayName');
                updatedHideFields = [...updatedHideFields, field];
            } else {
                // If checking a name field, add just that field
                updatedSelectedFields = [...selectedFields.filter(f => f !== 'displayName'), field];
                updatedHideFields = updatedHideFields.filter(f => f !== field);
            }
        } else {
            // Handle non-name fields as before
            if (isFieldSelected) {
                updatedSelectedFields = selectedFields.filter(f => f !== field);
                updatedHideFields = [...updatedHideFields, field];
            } else {
                updatedSelectedFields = [...selectedFields, field];
                updatedHideFields = updatedHideFields.filter(f => f !== field);
            }
        }

        setAttributes({ 
            selectedFields: updatedSelectedFields,
            hideFields: updatedHideFields
        });
        
        // Force re-render when fields are changed
        setRenderKey(prev => prev + 1);
    };

    // Modify the format change handler
    const handleFormatChange = (value) => {
        setAttributes({ selectedFormat: value });
        
        // Force re-render
        setRenderKey(prev => prev + 1);
        
        // Only reset fields if explicitly changing format and no fields are selected
        if (!attributes.selectedFields || attributes.selectedFields.length === 0) {
            apiFetch({ 
                path: '/wp/v2/settings/rrze_faudir_options'
            }).then((settings) => {
                if (settings?.default_output_fields) {
                    const formatSpecificFields = formatFields[value] || [];
                    const filteredDefaultFields = settings.default_output_fields.filter(
                        field => formatSpecificFields.includes(field)
                    );
                    setAttributes({ 
                        selectedFields: filteredDefaultFields
                    });
                }
            }).catch((error) => {
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
        groupId: attributes.groupId,
        function: attributes.function,
        ...(attributes.orgnr && { orgnr: attributes.orgnr }), // Only include orgnr if it's not empty
        url: attributes.url
    };

    // Add ServerSideRender with debounce
    const [key, setKey] = useState(0);
    useEffect(() => {
        const timer = setTimeout(() => {
            setKey(prev => prev + 1);
        }, 300); // 300ms debounce
        return () => clearTimeout(timer);
    }, [...Object.values(blockAttributes), sort]); // Use blockAttributes instead of attributes

    // Also update the renderKey when sort changes
    const handleSortChange = (value) => {
        setAttributes({ sort: value });
        setRenderKey(prev => prev + 1); // Force re-render
    };

    // 1. Add memoization for expensive computations
    const memoizedPosts = useMemo(() => posts.map((post) => ({
        id: post.id,
        title: post.title.rendered,
        personId: post.meta?.person_id
    })), [posts]);

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
                    {/* Toggle for Category */}
                    <ToggleControl
                        label={__('Show Category', 'rrze-faudir')}
                        checked={showCategory}
                        onChange={() => setAttributes({ showCategory: !showCategory })}
                    />

                    {/* Category Selection */}
                    {showCategory && (
                        <>
                            <h4>{__('Select Category', 'rrze-faudir')}</h4>
                            {categories.map((category) => (
                                <CheckboxControl
                                    key={category.id}
                                    label={category.name}
                                    checked={selectedCategory === category.name}
                                    onChange={() => {
                                        // If the category is already selected, unselect it by setting to empty string
                                        const newCategory = selectedCategory === category.name ? '' : category.name;
                                        setAttributes({ 
                                            selectedCategory: newCategory,
                                            // Clear selected posts when unchecking category
                                            selectedPosts: newCategory === '' ? [] : selectedPosts,
                                            selectedPersonIds: newCategory === '' ? [] : selectedPersonIds
                                        });
                                    }}
                                />
                            ))}
                        </>
                    )}

                    {/* Toggle for Posts */}
                    <ToggleControl
                        label={__('Show Persons', 'rrze-faudir')}
                        checked={showPosts}
                        onChange={() => setAttributes({ showPosts: !showPosts })}
                    />

                    {/* Posts Selection */}
                    {showPosts && (
                        <>
                            <h4>{__('Select Persons', 'rrze-faudir')}</h4>
            
                            {isLoadingPosts ? (
                                <p>{__('Loading persons...', 'rrze-faudir')}</p>
                            ) : posts.length > 0 ? (
                                <>
                                   
                                    {posts.map((post) => {
                                        return (
                                            <CheckboxControl
                                                key={post.id}
                                                label={post.title.rendered}
                                                checked={Array.isArray(selectedPosts) && selectedPosts.includes(post.id)}
                                                onChange={() => togglePostSelection(post.id, post.meta?.person_id)}
                                            />
                                        );
                                    })}
                                </>
                            ) : (
                                <p>{__('No posts available.', 'rrze-faudir')}</p>
                            )}
                        </>
                    )}

                    {/* Format Selection */}
                    <SelectControl
                        label={__('Select Format', 'rrze-faudir')}
                        value={selectedFormat || 'list'}
                        options={[
                            { value: 'list', label: __('List', 'rrze-faudir') },
                            { value: 'table', label: __('Table', 'rrze-faudir') },
                            { value: 'card', label: __('Card', 'rrze-faudir') },
                            { value: 'kompakt', label: __('Kompakt', 'rrze-faudir') },
                            { value: 'page', label: __('Page', 'rrze-faudir') },
                        ]}
                        onChange={handleFormatChange}
                    />



                    {/* Fields Selection */}
                    {Object.keys(formatFields).map((format) => {
                        if (selectedFormat === format) {
                            return (
                                <div key={format}>
                                    <h4>{__('Select Fields', 'rrze-faudir')}</h4>
                                    {formatFields[format].map((field) => (
                                        <div key={field} style={{ marginBottom: '8px' }}>
                                            <CheckboxControl
                                                label={
                                                    <>
                                                        {availableFields[field]}
                                                    
                                                    </>
                                                }
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

                    {/* New Input Fields for Group Id, Function, Organization Nr */}
                    <TextControl
                        label={__('Group Id', 'rrze-faudir')}
                        value={groupId}
                        onChange={(value) => setAttributes({ groupId: value })}
                    />

                    <TextControl
                        label={__('Function', 'rrze-faudir')}
                        value={attributes.function || ''}
                        onChange={(value) => setAttributes({ function: value })}
                    />

                    <TextControl
                        label={__('Organization Number', 'rrze-faudir')}
                        value={orgnr}
                        onChange={(value) => {
                   
                            setAttributes({ orgnr: value });
                        }}
                    />
                    {defaultOrgNr && !orgnr && (
                        <div style={{ 
                            padding: '8px', 
                            backgroundColor: '#f0f0f0', 
                            borderLeft: '4px solid #007cba',
                            marginTop: '5px',
                            marginBottom: '15px'
                        }}>
                            <span className="dashicons dashicons-info" style={{ marginRight: '5px' }}></span>
                            {__('Default organization will be used if empty.', 'rrze-faudir')}
                        </div>
                    )}
                     <TextControl
                        label={__('Custom url', 'rrze-faudir')}
                        value={url}
                        onChange={(value) => setAttributes({ url: value })}
                    />
                    {/* Button Text Field - Only for Kompakt Format */}
                    {selectedFormat === 'kompakt' && (
                        <TextControl
                            label={__('Button Text', 'rrze-faudir')}
                            help={__('Default: ', 'rrze-faudir') + defaultButtonText}
                            value={buttonText}
                            onChange={(value) => setAttributes({ buttonText: value })}
                            placeholder={defaultButtonText}
                        />
                    )}
                    <SelectControl
                        label={__('Sort by', 'rrze-faudir')}
                        value={sort}
                        options={[
                            { value: 'last_name', label: __('Last Name', 'rrze-faudir') },
                            { value: 'title_last_name', label: __('Title and Last Name', 'rrze-faudir') },
                            { value: 'function_head', label: __('Head of Department First', 'rrze-faudir') },
                            { value: 'function_proffesor', label: __('Professors First', 'rrze-faudir') },
                            { value: 'identifier_order', label: __('Identifier Order', 'rrze-faudir') },
                        ]}
                        onChange={handleSortChange} // Use the new handler instead of direct setAttributes
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {(attributes.selectedPersonIds?.length > 0) || 
                 (attributes.selectedCategory) || 
                 (attributes.function && (attributes.orgnr || defaultOrgNr)) ? (
                    <>
                        {/* Format console log as WordPress shortcode */}
                        
                        <ServerSideRender
                            key={debouncedRenderKey}
                            block="rrze-faudir/block"
                            attributes={{
                                // Case 1: function + orgnr
                                ...(attributes.function ? {
                                    function: attributes.function,
                                    ...(attributes.orgnr && { orgnr: attributes.orgnr }),
                                    selectedFormat: attributes.selectedFormat,
                                    selectedFields: attributes.selectedFields.length > 0 ? attributes.selectedFields : null, // Only pass if fields are selected
                                    hideFields: attributes.hideFields,  // Add hideFields
                                    buttonText: attributes.buttonText,
                                    url: attributes.url,
                                    sort: attributes.sort
                                } : 
                                // Case 2: category
                                attributes.selectedCategory ? {
                                    selectedCategory: attributes.selectedCategory,
                                    selectedPersonIds: attributes.selectedPersonIds,
                                    selectedFormat: attributes.selectedFormat,
                                    selectedFields: attributes.selectedFields.length > 0 ? attributes.selectedFields : null, // Only pass if fields are selected
                                    hideFields: attributes.hideFields,  // Add hideFields
                                    buttonText: attributes.buttonText,
                                    url: attributes.url,
                                    groupId: attributes.groupId,
                                    sort: attributes.sort
                                } :
                                // Case 3: selectedPersonIds
                                {
                                    selectedPersonIds: attributes.selectedPersonIds,
                                    selectedFields: attributes.selectedFields.length > 0 ? attributes.selectedFields : null, // Only pass if fields are selected
                                    hideFields: attributes.hideFields,  // Add hideFields
                                    selectedFormat: attributes.selectedFormat,
                                    buttonText: attributes.buttonText,
                                    url: attributes.url,
                                    groupId: attributes.groupId,
                                    sort: attributes.sort
                                })
                            }}
                        />
                    </>
                ) : (
                    <div style={{ padding: '20px', backgroundColor: '#f8f9fa', textAlign: 'center' }}>
                        <p>
                            {attributes.function 
                                ? (defaultOrgNr 
                                    ? __('Using default organization.', 'rrze-faudir')
                                    : __('Please configure a default organization in the plugin settings or add an organization ID to display results.', 'rrze-faudir'))
                                : __('Please select persons or a category to display using the sidebar controls.', 'rrze-faudir')}
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}
