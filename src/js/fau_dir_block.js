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

wp.blocks.registerBlockType('rrze/faudir-block', {
    apiVersion: 2,
    title: 'FAUDIR Block',
    icon: 'admin-users',
    category: 'rrze-blocks',
    attributes: {

        identifier: { 
            type: 'array',
            default: []
        },
        format: { type: 'string', default: 'list' },
        url: { type: 'string', default: '' },
        show: { type: 'string', default: '' },
        hide: { type: 'string', default: '' },
        image: { type: 'number', default: 0 },
        groupid: { type: 'string', default: '' },
        orgnr: { type: 'string', default: '' }
    },
    edit: function (props) {
        const [persons, setPersons] = wp.element.useState([]);
        const [filteredPersons, setFilteredPersons] = wp.element.useState([]);
        const [categories, setCategories] = wp.element.useState([]);
        const [isLoading, setIsLoading] = wp.element.useState(true);
        const [error, setError] = wp.element.useState(null);
        const [manualIdentifier, setManualIdentifier] = wp.element.useState('');

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
            { value: 'firstName', label: 'First Name' },
            { value: 'familyName', label: 'Family Name' },
            { value: 'name', label: 'Name' },
            { value: 'email', label: 'Email' },
            { value: 'phone', label: 'Phone' },
            { value: 'organization', label: 'Organization' },
            { value: 'function', label: 'Function' },
        ];

        // Convert the comma-separated string into an array of selected values
        const selectedValues = show.split(', ').filter(Boolean);

        // Handle checkbox change
        const handleCheckboxChange = (optionValue) => {
            let updatedValues = [...selectedValues];
            if (updatedValues.includes(optionValue)) {
                // Remove the option if it's already selected
                updatedValues = updatedValues.filter((item) => item !== optionValue);
            } else {
                // Add the option if it's not selected
                updatedValues.push(optionValue);
            }
            // Update the show attribute with the new values as a string
            setAttributes({ show: updatedValues.join(', ') });
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

        return wp.element.createElement(
            'div',
            { className: 'wp-block-rrze-faudir-block' },
            // Category dropdown
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
            // Person dropdown (showing all persons)
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
                            ...persons // Show all persons
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
                            checked: selectedValues.includes(option.value),
                            onChange: () => handleCheckboxChange(option.value),
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
            )
        );
    },
    save: function() {
        // This block will be rendered dynamically on the server-side, so no need to save anything here
        return null;
    },
});
