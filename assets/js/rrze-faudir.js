function fetchPersons() {
    return wp.apiFetch({
        path: '/wp/v2/custom_person?per_page=100&_fields=id,title,meta.person_id'
    }).then(persons => {
        console.log('Raw persons data:', persons);
        
        if (!Array.isArray(persons)) {
            console.error('Expected array of persons, got:', typeof persons);
            return [];
        }

        return persons.map(person => {
            const personName = person.meta?.person_name || 'Unnamed Person';
            const personId = person.meta?.person_id || '';
            
            return {
                label: personName,
                value: personId
            };
        });
    }).catch(error => {
        console.error('Error fetching persons:', error);
        return [];
    });
}

function fetchCategories() {
    return wp.apiFetch({
        path: '/wp/v2/custom_taxonomy?per_page=100'
    }).then(categories => {
        console.log('Raw categories data:', categories);
        return categories.map(category => ({
            label: category.name,
            value: category.id.toString()
        }));
    }).catch(error => {
        console.error('Error fetching categories:', error);
        return [];
    });
}

// Add this new component near the top of your file, after the imports
const FormatPreview = ({ format, attributes, persons }) => {
    const selectedPersons = persons.filter(p => attributes.identifier.includes(p.value));
    const firstPerson = selectedPersons[0] || { label: 'Example Person' };

    // Common preview styles
    const previewStyles = {
        padding: '15px',
        border: '1px solid #ddd',
        borderRadius: '4px',
        backgroundColor: '#f9f9f9'
    };

    switch (format) {
        case 'list':
            return wp.element.createElement(
                'div',
                { style: previewStyles },
                wp.element.createElement('ul', { style: { listStyle: 'none', padding: 0 } },
                    wp.element.createElement('li', null,
                        wp.element.createElement('strong', null, firstPerson.label),
                        wp.element.createElement('br'),
                        'Email: example@fau.de',
                        wp.element.createElement('br'),
                        'Phone: +49 123 456789'
                    )
                )
            );

        case 'card':
            return wp.element.createElement(
                'div',
                { 
                    style: {
                        ...previewStyles,
                        display: 'flex',
                        gap: '15px'
                    }
                },
                wp.element.createElement('div', { style: { width: '100px', height: '100px', backgroundColor: '#ddd' } }, 'Image'),
                wp.element.createElement('div', null,
                    wp.element.createElement('strong', null, firstPerson.label),
                    wp.element.createElement('br'),
                    'Email: example@fau.de',
                    wp.element.createElement('br'),
                    'Phone: +49 123 456789'
                )
            );

        case 'table':
            return wp.element.createElement(
                'div',
                { style: previewStyles },
                wp.element.createElement('table', { style: { width: '100%' } },
                    wp.element.createElement('tr', null,
                        wp.element.createElement('td', null, 'Name:'),
                        wp.element.createElement('td', null, firstPerson.label)
                    ),
                    wp.element.createElement('tr', null,
                        wp.element.createElement('td', null, 'Email:'),
                        wp.element.createElement('td', null, 'example@fau.de')
                    )
                )
            );

        case 'kompakt':
            return wp.element.createElement(
                'div',
                { 
                    style: {
                        ...previewStyles,
                        display: 'flex',
                        gap: '10px',
                        alignItems: 'center'
                    }
                },
                wp.element.createElement('div', { 
                    style: { 
                        width: '50px', 
                        height: '50px', 
                        backgroundColor: '#ddd' 
                    } 
                }, ''),
                wp.element.createElement('div', null,
                    wp.element.createElement('strong', null, firstPerson.label),
                    wp.element.createElement('br'),
                    'Email: example@fau.de'
                )
            );

        case 'page':
            return wp.element.createElement(
                'div',
                { style: previewStyles },
                wp.element.createElement('h3', null, firstPerson.label),
                wp.element.createElement('div', { style: { display: 'flex', gap: '20px' } },
                    wp.element.createElement('div', { style: { width: '150px', height: '150px', backgroundColor: '#ddd' } }, 'Image'),
                    wp.element.createElement('div', null,
                        'Contact Information',
                        wp.element.createElement('br'),
                        'Email: example@fau.de',
                        wp.element.createElement('br'),
                        'Phone: +49 123 456789',
                        wp.element.createElement('br'),
                        'Organization: FAU'
                    )
                )
            );

        default:
            return wp.element.createElement('div', null, 'Select a format');
    }
};

wp.blocks.registerBlockType('rrze/faudir-block', {
    apiVersion: 2,
    title: 'FAUDIR Block',
    icon: 'admin-users',
    category: 'rrze-blocks',
    supports: {
        html: false,
        reusable: true,
        lock: false,
    },
    attributes: {
        identifier: { 
            type: 'array',
            default: []
        },
        format: { type: 'string', default: 'kompakt' },
        url: { type: 'string', default: '' },
        show: { type: 'string', default: '' },
        image: { type: 'number', default: 0 },
        groupid: { type: 'string', default: '' },
        orgnr: { type: 'string', default: '' }
    },
    edit: function (props) {
        const blockProps = wp.blockEditor.useBlockProps();
        const [persons, setPersons] = wp.element.useState([]);
        const [filteredPersons, setFilteredPersons] = wp.element.useState([]);
        const [categories, setCategories] = wp.element.useState([]);
        const [isLoading, setIsLoading] = wp.element.useState(true);
        const [error, setError] = wp.element.useState(null);
        const [manualIdentifier, setManualIdentifier] = wp.element.useState('');
        const [isEditMode, setIsEditMode] = wp.element.useState(false);

        // Fetch data when component mounts
        wp.element.useEffect(() => {
            setIsLoading(true);
            setError(null);

            // Fetch persons with custom taxonomy information
            wp.apiFetch({
                path: '/wp/v2/custom_person?per_page=100&_fields=id,title,meta.person_id,meta.person_name,custom_taxonomy'
            }).then(response => {
                console.log('Persons response:', response);
                const formattedPersons = response.map(person => ({
                    label: person.meta?.person_name || person.title.rendered,
                    value: person.meta?.person_id || '',
                    categories: person.custom_taxonomy || []
                })).filter(person => person.value);
                setPersons(formattedPersons);
                setFilteredPersons(formattedPersons);
                setIsLoading(false);
            }).catch(err => {
                console.error('Error fetching persons:', err);
                setError('Error loading persons data');
                setIsLoading(false);
            });

            // Fetch custom taxonomy terms
            wp.apiFetch({
                path: '/wp/v2/custom_taxonomy?per_page=100'
            }).then(response => {
                const formattedCategories = response.map(cat => ({
                    label: cat.name,
                    value: cat.id.toString()
                }));
                setCategories(formattedCategories);
            }).catch(err => {
                console.error('Error fetching categories:', err);
            });
        }, []);

        // Filter and select persons when category changes
        const handleCategoryChange = (categoryId) => {
            // If no category selected, clear all selections
            props.setAttributes({ identifier: [] });

            // Find all persons in this category and add them to selections
            const personsInCategory = persons.filter(person => 
                person.categories.includes(parseInt(categoryId))
            );
            const personIds = personsInCategory.map(person => person.value);
            
            // Keep existing selections that aren't in the category
            const existingSelections = props.attributes.identifier || [];
            const uniqueSelections = [...new Set([...existingSelections, ...personIds])];
            
            props.setAttributes({ identifier: uniqueSelections });
        };

        // Handle individual person selection
        const handlePersonSelection = (value) => {
            if (value) {
                const currentIdentifiers = [...props.attributes.identifier];
                if (!currentIdentifiers.includes(value)) {
                    props.setAttributes({ 
                        identifier: [...currentIdentifiers, value]
                    });
                }
            }
        };

        const {
            attributes: { category, identifier, format, url, show, groupid, orgnr, image },
            setAttributes
        } = props;

        const onSelectImage = (newImage) => {
            setAttributes({ image: newImage.id });
        };

        // Array of available options with actual values and display texts
        const options = [
            { value: 'personalTitle', label: 'Personal Title' },
            { value: 'givenName', label: 'First Name' },
            { value: 'displayName', label: 'displayName Name' },
            { value: 'familyName', label: 'Family Name' },
            { value: 'name', label: 'Name' },
            { value: 'email', label: 'Email' },
            { value: 'phone', label: 'Phone' },
            { value: 'organization', label: 'Organization' },
            { value: 'function', label: 'Function' },
            { value: 'url', label: 'URL' },
        ];

        // Convert the comma-separated string into an array of selected values
        const selectedShowValues = props.attributes.show.split(', ').filter(Boolean);

        // Handle checkbox change for show fields
        const handleShowCheckboxChange = (optionValue) => {
            let updatedValues = [...selectedShowValues];
            if (updatedValues.includes(optionValue)) {
                // Remove the option if it's already selected
                updatedValues = updatedValues.filter((item) => item !== optionValue);
            } else {
                // Add the option if it's not selected
                updatedValues.push(optionValue);
            }
            // Update the show attribute with the new values as a string
            props.setAttributes({ show: updatedValues.join(', ') });
        };

        // Handle manual identifier input
        const handleManualInput = (event) => {
            const value = event.target.value;
            setManualIdentifier(value);
        };

        // Add manual identifier to the list
        const addManualIdentifier = () => {
            if (manualIdentifier && !identifier.includes(manualIdentifier)) {
                const newIdentifiers = [...identifier, manualIdentifier];
                setAttributes({ identifier: newIdentifiers });
                setManualIdentifier(''); // Clear the input
            }
        };

        // Remove identifier from the list
        const removeIdentifier = (idToRemove) => {
            const newIdentifiers = identifier.filter(id => id !== idToRemove);
            setAttributes({ identifier: newIdentifiers });
        };

        // Return loading state
        if (isLoading) {
            return wp.element.createElement(
                'div',
                { className: 'wp-block-rrze-faudir-block loading' },
                'Loading...'
            );
        }

        // Return error state
        if (error) {
            return wp.element.createElement(
                'div',
                { className: 'wp-block-rrze-faudir-block error' },
                error
            );
        }

        // Then update your BlockPreview component to use the FormatPreview
        const BlockPreview = ({ attributes, onClick, persons }) => {
            return wp.element.createElement(
                'div',
                { 
                    className: 'faudir-block-preview',
                    onClick: onClick
                },
                wp.element.createElement(
                    'div',
                    { className: 'preview-header' },
                    wp.element.createElement('h3', null, 'FAUDIR Block'),
                    wp.element.createElement(
                        'button',
                        {
                            className: 'edit-button',
                            onClick: (e) => {
                                e.stopPropagation();
                                onClick();
                            }
                        },
                        'Edit'
                    )
                ),
                wp.element.createElement(FormatPreview, {
                    format: attributes.format,
                    attributes: attributes,
                    persons: persons
                }),
                wp.element.createElement(
                    'div',
                    { className: 'preview-footer' },
                    wp.element.createElement('div', null, 
                        `Selected Persons: ${attributes.identifier.length}`
                    ),
                    wp.element.createElement('div', null,
                        `Showing: ${attributes.show || 'Default fields'}`
                    )
                )
            );
        };

        return wp.element.createElement(
            'div',
            { ...blockProps },
            !isEditMode ? (
                // Preview mode
                wp.element.createElement(BlockPreview, {
                    attributes: props.attributes,
                    onClick: () => setIsEditMode(true),
                    persons: persons
                })
            ) : (
                // Edit mode
                wp.element.createElement(
                    wp.element.Fragment,
                    null,
                    wp.element.createElement(
                        'div',
                        { className: 'block-label' },
                        wp.element.createElement(
                            wp.components.SelectControl,
                            {
                                label: 'Category',
                                value: props.attributes.category,
                                options: [
                                    { label: 'Select a category...', value: '' },
                                    ...categories
                                ],
                                onChange: handleCategoryChange
                            }
                        )
                    ),
                    // Person dropdown
                    wp.element.createElement(
                        'div',
                        { className: 'block-label' },
                        wp.element.createElement(
                            wp.components.SelectControl,
                            {
                                label: 'Add Person',
                                value: '',
                                options: [
                                    { label: 'Select a person...', value: '' },
                                    ...persons
                                ],
                                onChange: handlePersonSelection
                            }
                        )
                    ),
                    // Display selected persons with remove option
                    wp.element.createElement(
                        'div',
                        { className: 'selected-persons' },
                        wp.element.createElement('h4', null, 'Selected Persons:'),
                        props.attributes.identifier.length > 0 
                            ? props.attributes.identifier.map(id => {
                                const person = persons.find(p => p.value === id);
                                const isInCategory = person?.categories?.includes(parseInt(props.attributes.category));
                                return wp.element.createElement(
                                    'div',
                                    { 
                                        key: id,
                                        className: `selected-person${isInCategory ? ' in-category' : ''}`
                                    },
                                    wp.element.createElement('span', null, 
                                        person ? person.label : id
                                    ),
                                    wp.element.createElement(
                                        'button',
                                        {
                                            onClick: () => {
                                                const newIdentifiers = props.attributes.identifier
                                                    .filter(identifier => identifier !== id);
                                                props.setAttributes({ identifier: newIdentifiers });
                                            },
                                            className: 'remove-person'
                                        },
                                        '×'
                                    )
                                );
                            })
                            : wp.element.createElement('p', null, 'No persons selected')
                    ),
                    // Format selection dropdown
                    wp.element.createElement(
                        'label',
                        { className: 'block-label' },
                        null,
                        'Format',
                        wp.element.createElement('label', {
                            className: 'block-label',
                            type: 'text',
                            value: 'Format',
                        }),
                        wp.element.createElement('select', {
                            className: 'block-label',
                            value: format,
                            onChange: function(event) {
                                setAttributes({ format: event.target.value });
                            }
                        },
                        wp.element.createElement('option', { value: 'list' }, 'List'),
                        wp.element.createElement('option', { value: 'table' }, 'Table'),
                        wp.element.createElement('option', { value: 'card' }, 'Card'),
                        wp.element.createElement('option', { value: 'kompakt' }, 'Kompakt'),
                        wp.element.createElement('option', { value: 'page' }, 'Page')
                        )
                    ),
                     // Url field input
                     wp.element.createElement(
                        'label',
                        { className: 'block-label' },
                        null,
                        'Url',
                        wp.element.createElement('label', {
                            className: 'block-label',
                            type: 'text',
                            value: 'Url',
                        }),
                        wp.element.createElement('input', {
                            className: 'block-label',
                            type: 'text',
                            value: url,
                            onChange: function(event) {
                                setAttributes({ url: event.target.value });
                            }
                        })
                    ),
                    // Show fields input
                    wp.element.createElement(
                        'div',
                        { className: 'block-container' },
                        wp.element.createElement('label', { className: 'block-label' }, 'Show Fields'),
                        options.map((option) =>
                            wp.element.createElement(
                                'div',
                                { key: option.value, className: 'checkbox-container block-label' },
                                wp.element.createElement('input', {
                                    type: 'checkbox',
                                    className: 'checkbox-input',
                                    checked: selectedShowValues.includes(option.value),
                                    onChange: () => handleShowCheckboxChange(option.value),
                                }),
                                wp.element.createElement('span', null, option.label) // Display the label text
                            )
                        )
                    ),
                    // GroupId field input
                    wp.element.createElement(
                        'label',
                        { className: 'block-label' },
                        null,
                        'Group Id',
                        wp.element.createElement('label', {
                            className: 'block-label',
                            type: 'text',
                            value: 'Group Id',
                        }), 
                        wp.element.createElement('input', {
                            className: 'block-label',
                            type: 'text',
                            value: groupid,
                            onChange: function(event) {
                                setAttributes({ groupid: event.target.value });
                            }
                        })
                    ),
                    // Organization number field input
                    wp.element.createElement(
                        'label',
                        { className: 'block-label' },
                        null,
                        'Organization number',
                        wp.element.createElement('label', {
                            className: 'block-label',
                            type: 'text',
                            value: 'Organization number',
                        }), 
                        wp.element.createElement('input', {
                            className: 'block-label',
                            type: 'text',
                            value: orgnr,
                            onChange: function(event) {
                                setAttributes({ orgnr: event.target.value });
                            }
                        })
                    ),
                    // MediaUpload for selecting an image
                    wp.element.createElement(
                        wp.blockEditor.MediaUpload,
                        {
                            onSelect: onSelectImage,
                            allowedTypes: ['image'],
                            value: image,
                            render: function(obj) {
                                return wp.element.createElement(
                                    'div',
                                    null,
                                    wp.element.createElement(
                                        'button',
                                        {
                                            onClick: obj.open,
                                            className: 'button button-secondary block-label',
                                        },
                                        image ? 'Change Image' : 'Select Image'
                                    ),
                                    image && wp.element.createElement('img', {
                                        src: wp.media.attachment(image).attributes.url,
                                        alt: 'Selected Image',
                                        style: { maxWidth: '100%', height: 'auto' }
                                    })
                                );
                            }
                        }
                    ),
                    // Add a "Done" button at the bottom
                    wp.element.createElement(
                        'button',
                        {
                            className: 'components-button is-primary',
                            onClick: () => setIsEditMode(false)
                        },
                        'Done'
                    )
                )
            )
        );
    },
    save: function() {
        // This block will be rendered dynamically on the server-side, so no need to save anything here
        return null;
    },
});


jQuery(document).ready(function ($) {
    console.log('RRZE FAUDIR JS from src directory');
    $('#person_id').on('change', function() {
        var personId = $(this).val();

        if (personId) {
            $.ajax({
                url: customPerson.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_person_attributes',
                    person_id: personId,
                    nonce: customPerson.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('#person_name').val(data.person_name);
                        $('#person_email').val(data.person_email);
                        $('#person_given_name').val(data.person_given_name);
                        $('#person_family_name').val(data.person_family_name);
                        $('#person_title').val(data.person_title);
                        $('#person_organization').val(data.person_organization);
                        $('#person_function').val(data.person_function);
                        // Update other fields as needed
                    } else {
                        alert(response.data);
                    }
                }
            });
        }
    });
});
