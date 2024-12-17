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

    const {
        selectedCategory='',
        selectedPosts=[],
        showCategory='',
        showPosts='',
        selectedPersonIds='',
        selectedFormat='kompakt',
        selectedFields=[],
        groupId='',
        functionField='',
        organizationNr='',
        url='',
        buttonText='',
        hideFields=[],
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
            'academic_title',
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
        // Fetch all posts from the custom post type
        setIsLoadingPosts(true);
        apiFetch({ path: '/wp/v2/custom_person?per_page=100&_fields=id,title,meta' })
            .then((data) => {
                setPosts(data);
                setIsLoadingPosts(false);
            })
            .catch((error) => {
                console.error('Error fetching posts:', error);
                setIsLoadingPosts(false);
            });
    }, []);

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
            ? selectedPosts.filter((id) => id !== postId) // Deselect post
            : [...selectedPosts, postId]; // Select post
            const updatedPersonIds = updatedSelectedPosts.map(id => {
                const post = posts.find(p => p.id === id);
                // Ensure the person_id is extracted and filtered properly
                return post?.meta?.person_id || null;}).filter(Boolean)
    
        // Store both post ID and person ID
        setAttributes({
            selectedPosts: updatedSelectedPosts,
            selectedPersonIds: updatedPersonIds,// Remove any null values from the person IDs array
        });
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
        functionField: attributes.functionField,
        organizationNr: attributes.organizationNr,
        url: attributes.url
    };

    console.log('Block attributes:', blockAttributes);

    // Add debug output to the rendered component
    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'faudir-block')}>
                    {/* Toggle for Category */}
                    <ToggleControl
                        label={__('Show Category', 'faudir-block')}
                        checked={showCategory}
                        onChange={() => setAttributes({ showCategory: !showCategory })}
                    />

                    {/* Category Selection */}
                    {showCategory && (
                        <>
                            <h4>{__('Select Category', 'faudir-block')}</h4>
                            {categories.map((category) => (
                                <CheckboxControl
                                    key={category.id}
                                    label={category.name}
                                    checked={selectedCategory === category.name}
                                    onChange={() => setAttributes({ selectedCategory: category.name })}
                                />
                            ))}
                        </>
                    )}

                    {/* Toggle for Posts */}
                    <ToggleControl
                        label={__('Show Persons', 'faudir-block')}
                        checked={showPosts}
                        onChange={() => setAttributes({ showPosts: !showPosts })}
                    />

                    {/* Posts Selection */}
                    {showPosts && (
                        <>
                            <h4>{__('Select Persons', 'faudir-block')}</h4>
                            {isLoadingPosts ? (
                                <p>{__('Loading persons...', 'faudir-block')}</p>
                            ) : posts.length > 0 ? (
                                posts.map((post) => (
                                    <CheckboxControl
                                        key={post.id}
                                        label={post.title.rendered}
                                        checked={selectedPosts.includes(post.id)}
                                        onChange={() => togglePostSelection(post.id, post.meta?.person_id)}
                                    />
                                ))
                            ) : (
                                <p>{__('No posts available.', 'faudir-block')}</p>
                            )}
                        </>
                    )}

                    {/* Format Selection */}
                    <SelectControl
                        label={__('Select Format', 'faudir-block')}
                        value={selectedFormat || 'list'}
                        options={[
                            { value: 'list', label: __('List', 'faudir-block') },
                            { value: 'table', label: __('Table', 'faudir-block') },
                            { value: 'card', label: __('Card', 'faudir-block') },
                            { value: 'kompakt', label: __('Kompakt', 'faudir-block') },
                            { value: 'page', label: __('Page', 'faudir-block') },
                        ]}
                        onChange={handleFormatChange}
                    />



                    {/* Fields Selection */}
                    {Object.keys(formatFields).map((format) => {
                        if (selectedFormat === format) {
                            return (
                                <div key={format}>
                                    <h4>{__('Select Fields', 'faudir-block')}</h4>
                                    {formatFields[format].map((field) => (
                                        <div key={field} style={{ marginBottom: '8px' }}>
                                            <CheckboxControl
                                                label={
                                                    <>
                                                        {availableFields[field]}
                                                        <span style={{ 
                                                            marginLeft: '8px',
                                                            color: selectedFields.includes(field) ? '#4CAF50' : '#f44336',
                                                            fontSize: '12px'
                                                        }}>
                                                            ({selectedFields.includes(field) ? 'show' : 'hide'})
                                                        </span>
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
                        value={functionField}
                        onChange={(value) => setAttributes({ functionField: value })}
                    />

                    <TextControl
                        label={__('Organization Nr', 'rrze-faudir')}
                        value={organizationNr}
                        onChange={(value) => setAttributes({ organizationNr: value })}
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
                </PanelBody>
            </InspectorControls>
            <div {...useBlockProps()}>
                {/* Add debug info to the block preview */}
     
                {attributes.selectedPersonIds && attributes.selectedPersonIds.length > 0 ? (
                    <>
                        <div style={{ marginBottom: '10px', padding: '10px', backgroundColor: '#f8f9fa' }}>
                            <strong>Selected Person IDs:</strong> {attributes.selectedPersonIds.join(', ')}
                        </div>
                        <ServerSideRender
                            block="rrze-faudir/block"
                            attributes={attributes}
                            EmptyResponsePlaceholder={() => (
                                <div style={{ padding: '20px', backgroundColor: '#fff3cd', color: '#856404' }}>
                                    <p>No content returned from server.</p>
                                    <details>
                                        <summary>Debug Information</summary>
                                        <pre>{JSON.stringify(attributes, null, 2)}</pre>
                                    </details>
                                </div>
                            )}
                            ErrorResponsePlaceholder={({ response }) => (
                                <div style={{ padding: '20px', backgroundColor: '#f8d7da', color: '#721c24' }}>
                                    <p><strong>Error loading content:</strong></p>
                                    <p>{response?.errorMsg || 'Unknown error occurred'}</p>
                                    <details>
                                        <summary>Debug Information</summary>
                                        <pre>Block: rrze-faudir/block</pre>
                                        <pre>Response: {JSON.stringify(response, null, 2)}</pre>
                                        <pre>Attributes: {JSON.stringify(attributes, null, 2)}</pre>
                                    </details>
                                </div>
                            )}
                        />
                    </>
                ) : (
                    <div style={{ padding: '20px', backgroundColor: '#f8f9fa', textAlign: 'center' }}>
                        <p>Please select persons to display using the sidebar controls.</p>
                    </div>
                )}
            </div>
        </>
    );
}
