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
    const [buttonText, setButtonText] = useState(''); // Add state for button text

    const {
        selectedCategory='',
        selectedPosts= [],
        showCategory='',
        showPosts='',
        selectedPersonIds='',
        selectedFormat='kompakt',
        selectedFields = [], // Default to an empty array
        groupId='', // New attribute
        functionField='', // New attribute for function
        organizationNr='', 
        url='',// New attribute for the image URL
    } = attributes;

    const availableFields = {
        display_name: __('Display Name', 'rrze-faudir'),
        academic_title: __('Academic Title', 'rrze-faudir'),
        first_name: __('First Name', 'rrze-faudir'),
        last_name: __('Last Name', 'rrze-faudir'),
        academic_suffix: __('Academic Suffix', 'rrze-faudir'),
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
            'display_name',
            'academic_title',
            'first_name',
            'last_name',
            'academic_suffix',
            'email',
            'phone',
            'function',
            'socialmedia',
        ],
        table: [
            'display_name',
            'academic_title',
            'first_name',
            'last_name',
            'academic_suffix',
            'email',
            'phone',
            'url',
            'socialmedia',
        ],
        list: [
            'display_name',
            'academic_title',
            'first_name',
            'last_name',
            'academic_suffix',
            'email',
            'phone',
            'url',
            'teasertext',
        ],
        kompakt: Object.keys(availableFields),
        page: Object.keys(availableFields),
    };

    useEffect(() => {
        // Initialize selectedFields to include all fields if not already set
        if (!selectedFields.length) {
            // Fetch default fields from WordPress settings
            apiFetch({ 
                path: '/wp/v2/settings/rrze_faudir_options'
            }).then((settings) => {
                // Only set default fields if none are currently selected
                if (settings?.default_output_fields) {
                    setAttributes({
                        selectedFields: settings.default_output_fields
                    });
                }
            }).catch((error) => {
                console.error('Error fetching default fields:', error);
            });
        }
    }, []);

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
        const updatedSelectedFields = selectedFields.includes(field)
            ? selectedFields.filter((f) => f !== field) // Deselect field
            : [...selectedFields, field]; // Select field

        setAttributes({ selectedFields: updatedSelectedFields });
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
                        onChange={(value) => {
                            setAttributes({ selectedFormat: value });
                            setAttributes({ 
                                selectedFields: formatFields[value] || []
                            });
                        }}
                    />

                    {/* Fields Selection */}
                    {Object.keys(formatFields).map((format) => {
                        if (selectedFormat === format) {
                            return (
                                <div key={format}>
                                    <h4>{__('Select Fields', 'faudir-block')}</h4>
                                    {formatFields[format].map((field) => (
                                        <CheckboxControl
                                            key={field}
                                            label={availableFields[field]}
                                            checked={selectedFields.includes(field)}
                                            onChange={() => toggleFieldSelection(field)}
                                        />
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
                            value={buttonText}
                            onChange={(value) => setButtonText(value)}
                        />
                    )}
                </PanelBody>
            </InspectorControls>
            <div {...useBlockProps()}>
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
