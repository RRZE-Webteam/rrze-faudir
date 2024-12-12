import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, CheckboxControl, ToggleControl, SelectControl, TextControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
    const [categories, setCategories] = useState([]);
    const [posts, setPosts] = useState([]);
    const [isLoadingCategories, setIsLoadingCategories] = useState(true);
    const [isLoadingPosts, setIsLoadingPosts] = useState(false);
    const [buttonText, setButtonText] = useState(''); // Add state for button text

    const {
        selectedCategory,
        selectedPosts,
        showCategory,
        showPosts,
        selectedFormat,
        selectedFields = [], // Default to an empty array
        groupId, // New attribute
        functionField, // New attribute for function
        organizationNr, 
        url,// New attribute for the image URL
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
        group_id: __('Group Id', 'rrze-faudir'), // New field
        organization_nr: __('Organization Nr', 'rrze-faudir'), // New field
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
            'group_id', // New field
            'organization_nr', // New field
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
            'group_id', // New field
            'organization_nr', // New field
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
            'group_id', // New field
            'organization_nr', // New field
        ],
        kompakt: Object.keys(availableFields),
        page: Object.keys(availableFields),
    };

    useEffect(() => {
        // Initialize selectedFields to include all fields if not already set
        if (!selectedFields.length) {
            setAttributes({
                selectedFields: Object.keys(availableFields),
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

    // Update the togglePostSelection function
    const togglePostSelection = (postId, personId) => {
        const updatedSelectedPosts = selectedPosts.includes(postId)
            ? selectedPosts.filter((id) => id !== postId) // Deselect post
            : [...selectedPosts, postId]; // Select post

        // Store both post ID and person ID
        setAttributes({ 
            selectedPosts: updatedSelectedPosts,
            selectedPersonIds: updatedSelectedPosts.map(id => {
                const post = posts.find(p => p.id === id);
                return post?.meta?.person_id || null;
            }).filter(Boolean)
        });
    };

    const toggleFieldSelection = (field) => {
        const updatedSelectedFields = selectedFields.includes(field)
            ? selectedFields.filter((f) => f !== field) // Deselect field
            : [...selectedFields, field]; // Select field

        setAttributes({ selectedFields: updatedSelectedFields });
    };

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
                                    checked={selectedCategory === category.id}
                                    onChange={() => setAttributes({ selectedCategory: category.id })}
                                />
                            ))}
                        </>
                    )}

                    {/* Toggle for Posts */}
                    <ToggleControl
                        label={__('Show Posts', 'faudir-block')}
                        checked={showPosts}
                        onChange={() => setAttributes({ showPosts: !showPosts })}
                    />

                    {/* Posts Selection */}
                    {showPosts && (
                        <>
                            <h4>{__('Select Posts', 'faudir-block')}</h4>
                            {isLoadingPosts ? (
                                <p>{__('Loading posts...', 'faudir-block')}</p>
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
                        value={selectedFormat}
                        options={[
                            { value: 'list', label: __('List', 'faudir-block') },
                            { value: 'table', label: __('Table', 'faudir-block') },
                            { value: 'card', label: __('Card', 'faudir-block') },
                            { value: 'kompakt', label: __('Kompakt', 'faudir-block') },
                            { value: 'page', label: __('Page', 'faudir-block') },
                        ]}
                        onChange={(value) => setAttributes({ selectedFormat: value })}
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
                <p>{__('Faudir Block – hello from the editor!', 'faudir-block')}</p>

                {/* Display Selected Category */}
                {showCategory && selectedCategory && (
                    <p>
                        <strong>{__('Selected Category:', 'faudir-block')}</strong>{' '}
                        {categories.find((cat) => cat.id === selectedCategory)?.name || ''}
                    </p>
                )}

                {/* Display Selected Posts */}
                {showPosts && selectedPosts.length > 0 && (
                    <div className="faudir-block-preview">
                        <h4>{__('Selected Posts:', 'faudir-block')}</h4>
                        <ul>
                            {posts
                                .filter((post) => selectedPosts.includes(post.id))
                                .map((post) => (
                                    <li key={post.id}>
                                        <strong>{post.title.rendered}</strong>
                                        <span>{post.meta?.person_id}</span>
                                    </li>
                                ))}
                        </ul>
                    </div>
                )}

                {/* Display Input Values */}
                {groupId && (
                    <p>
                        <strong>{__('Group Id:', 'faudir-block')}</strong> {groupId}
                    </p>
                )}
                {functionField && (
                    <p>
                        <strong>{__('Function:', 'faudir-block')}</strong> {functionField}
                    </p>
                )}
                {organizationNr && (
                    <p>
                        <strong>{__('Organization Nr:', 'faudir-block')}</strong> {organizationNr}
                    </p>
                )}
            </div>
        </>
    );
}
