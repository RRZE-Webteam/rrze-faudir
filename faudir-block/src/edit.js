import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, CheckboxControl, ToggleControl, SelectControl, TextControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
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
        socialmedia: __('Social Media', 'rrze-faudir'),
        workplaces: __('Workplaces', 'rrze-faudir'),
        room: __('Room', 'rrze-faudir'),
        floor: __('Floor', 'rrze-faudir'),
        street: __('Street', 'rrze-faudir'),
        zip: __('Zip', 'rrze-faudir'),
        city: __('City', 'rrze-faudir'),
        faumap: __('Fau Map', 'rrze-faudir'),
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
        // Only fetch and set default fields if this is a new block (no selectedFields set)
        if (!attributes.selectedFields || attributes.selectedFields.length === 0) {
            apiFetch({ 
                path: '/wp/v2/settings/rrze_faudir_options'
            }).then((settings) => {
                if (settings?.default_output_fields) {
                    setAttributes({
                        selectedFields: settings.default_output_fields
                    });
                }
            }).catch((error) => {
                console.error('Error fetching default fields:', error);
            });
        }
    }, []); // Empty dependency array means this only runs once when component mounts

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
                setDefaultOrgNr(settings.default_organization.orgnr);
                // If we have a functionField but no organizationNr, set the default
                if (functionValue && !orgnr) {
                    setAttributes({ orgnr: settings.default_organization.orgnr });
                }
            }
        }).catch((error) => {
            console.error('Error fetching default organization number:', error);
        });
    }, []); // Empty dependency array means this runs once on mount

    useEffect(() => {
        // Fetch all posts from the custom post type with minimal fields
        setIsLoadingPosts(true);
        const params = {
            per_page: 100,
            _fields: 'id,title,meta',
            orderby: 'title',
            order: 'asc'
        };

        // Add category filter if category is selected
        if (selectedCategory) {
            params.custom_taxonomy = selectedCategory;
        }

        // Modified logic for function and organization number
        if (functionValue) {
            params.function = functionValue;
            // Use organizationNr if set, otherwise fall back to defaultOrgNr
            const orgNr = orgnr || defaultOrgNr;
            if (orgNr) {
                params.organization_nr = orgNr;
            }
        }

        apiFetch({ 
            path: '/wp/v2/custom_person',
            params: params
        })
        .then((data) => {
            setPosts(data);
            // Only auto-select posts for category selection, not for function
            if (selectedCategory && (!selectedPosts || selectedPosts.length === 0)) {
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
    }, [selectedCategory, functionValue, orgnr, defaultOrgNr]);

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
        console.log('Toggling field:', field); // Debug log
        console.log('Current selectedFields:', selectedFields); // Debug log
        console.log('Current hideFields:', attributes.hideFields); // Debug log

        const isFieldSelected = selectedFields.includes(field);
        let updatedSelectedFields;
        let updatedHideFields = attributes.hideFields || [];

        if (isFieldSelected) {
            // Remove from selected fields and add to hide fields
            updatedSelectedFields = selectedFields.filter((f) => f !== field);
            updatedHideFields = [...updatedHideFields, field];
        } else {
            // Add to selected fields and remove from hide fields
            updatedSelectedFields = [...selectedFields, field];
            updatedHideFields = updatedHideFields.filter((f) => f !== field);
        }

        console.log('Updated selectedFields:', updatedSelectedFields); // Debug log
        console.log('Updated hideFields:', updatedHideFields); // Debug log

        setAttributes({ 
            selectedFields: updatedSelectedFields,
            hideFields: updatedHideFields
        });
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
                    // Filter default fields based on the selected format
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

    // Add debug logging
    console.log('Edit component rendering with attributes:', attributes);

    // Transform attributes to match the shortcode format
    const blockAttributes = {
        selectedPersonIds: attributes.selectedPersonIds,
        selectedFields: attributes.selectedFields,
        selectedFormat: attributes.selectedFormat,
        selectedCategory: attributes.selectedCategory,
        groupId: attributes.groupId,
        function: attributes.function,
        orgnr: attributes.orgnr,
        url: attributes.url
    };

    console.log('Block attributes:', blockAttributes);

    // Add ServerSideRender with debounce
    const [key, setKey] = useState(0);
    useEffect(() => {
        const timer = setTimeout(() => {
            setKey(prev => prev + 1);
        }, 300); // 300ms debounce
        return () => clearTimeout(timer);
    }, [...Object.values(attributes), sort]); // Add sort to the dependency array

    // Also update the renderKey when sort changes
    const handleSortChange = (value) => {
        setAttributes({ sort: value });
        setRenderKey(prev => prev + 1); // Force re-render
    };

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
                                posts.map((post) => (
                                    <CheckboxControl
                                        key={post.id}
                                        label={post.title.rendered}
                                        checked={Array.isArray(selectedPosts) && selectedPosts.includes(post.id)}
                                        onChange={() => togglePostSelection(post.id, post.meta?.person_id)}
                                    />
                                ))
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
                        label={__('Organization Nr', 'rrze-faudir')}
                        value={orgnr}
                        onChange={(value) => {
                            console.log('Setting orgnr:', value); // Debug log
                            setAttributes({ orgnr: value });
                        }}
                    />
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
                 (attributes.function && attributes.orgnr) ? (
                    <ServerSideRender
                        key={renderKey}
                        block="rrze-faudir/block"
                        attributes={{
                            // Case 1: function + orgnr
                            ...(attributes.function && attributes.orgnr ? {
                                function: attributes.function,
                                orgnr: attributes.orgnr,
                                selectedFormat: attributes.selectedFormat,
                                selectedFields: attributes.selectedFields,
                                buttonText: attributes.buttonText,
                                url: attributes.url,
                                sort: attributes.sort
                            } : 
                            // Case 2: category
                            attributes.selectedCategory ? {
                                selectedCategory: attributes.selectedCategory,
                                selectedFormat: attributes.selectedFormat,
                                selectedFields: attributes.selectedFields,
                                buttonText: attributes.buttonText,
                                url: attributes.url,
                                groupId: attributes.groupId,
                                sort: attributes.sort
                            } :
                            // Case 3: selectedPersonIds
                            {
                                selectedPersonIds: attributes.selectedPersonIds,
                                selectedFields: attributes.selectedFields,
                                selectedFormat: attributes.selectedFormat,
                                buttonText: attributes.buttonText,
                                url: attributes.url,
                                groupId: attributes.groupId,
                                sort: attributes.sort
                            })
                        }}
                    />
                ) : (
                    <div style={{ padding: '20px', backgroundColor: '#f8f9fa', textAlign: 'center' }}>
                        <p>
                            {attributes.function 
                                ? __('Please add an organization ID to display results.', 'rrze-faudir')
                                : __('Please select persons or a category to display using the sidebar controls.', 'rrze-faudir')}
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}
