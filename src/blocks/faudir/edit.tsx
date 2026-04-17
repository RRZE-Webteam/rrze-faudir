import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls, useBlockProps } from '@wordpress/block-editor';
import {
    PanelBody,
    ToolbarGroup,
    ToolbarItem,
    ToolbarButton,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
    __experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { edit, check, postAuthor, styles } from '@wordpress/icons';

import CustomServerSideRender from "../components/CustomServerSideRender";
import OrganizationNumberDetector from "../components/OrganizationNumberDetector";
import PersonSelector from "../components/PersonSelector";
import CategorySelector from "../components/CategorySelector";
import FormatSelector from "../components/FormatSelector";
import ShowHideSelector from "../components/ShowHideSelector";
import NameFormatSelector from "../components/NameFormatSelector";
import CustomPlaceholder from "../components/CustomPlaceholder";
import OrganizationIdentifierDetector from "../components/OrganizationIdentifierDetector";
import RoleSelector from "../components/RoleSelector";
import SortSelector from "../components/SortSelector";
import PersonIdentifierDetector from "../components/PersonIdentifierDetector";

import {
    EditProps,
    WPCategory,
    CustomPersonParams,
    CustomPersonRESTApi,
} from "./types";
import {
    getDefaultFields,
    FaudirSettingsPayload,
} from "./defaults";
import { fetchAllPages } from './helper';

import '../../scss/rrze-faudir.scss';
import './editor.scss';

export default function Edit({ attributes, setAttributes }: EditProps) {
    const [categories, setCategories] = useState<WPCategory[]>([]);
    const [posts, setPosts] = useState<CustomPersonRESTApi[]>([]);
    const [isLoadingPosts, setIsLoadingPosts] = useState(false);
    const [isOrg, setIsOrg] = useState(attributes.display === 'org');
    const [isAppearancePanelOpen, setIsAppearancePanelOpen] = useState<boolean>(false);
    const [hasFormatDisplayName, setHasFormatDisplayName] = useState<boolean>(false);

    const blockProps = useBlockProps();

    const {
        selectedCategory = '',
        selectedPosts = [],
        orgnr = '',
        initialSetup,
    } = attributes;

    function handleToolbarConfiguration() {
        setAttributes({
            initialSetup: !initialSetup,
        });
    }

    function handleDisplayToggle(value: string) {
        if (value === 'person') {
            setIsOrg(false);
        } else {
            setIsOrg(true);
        }
    }

    function togglePostSelection(postId: number) {
        var updatedSelectedPosts = selectedPosts.includes(postId)
            ? selectedPosts.filter(function(id) {
                return id !== postId;
            })
            : [...selectedPosts, postId];

        var updatedPersonIds = updatedSelectedPosts
            .map(function(id) {
                var post = posts.find(function(p) {
                    return p.id === id;
                });
                return post?.meta?.person_id || null;
            })
            .filter(function(value) {
                return Boolean(value);
            });

        setAttributes({
            selectedPosts: updatedSelectedPosts,
            selectedPersonIds: updatedPersonIds,
        });
    }

    useEffect(function() {
        setAttributes({
            display: isOrg ? 'org' : 'person',
        });
    }, [isOrg]);

    useEffect(function() {
        if (attributes.selectedFields && attributes.selectedFields.length > 0) {
            return;
        }

        apiFetch({
            path: '/wp/v2/settings/rrze_faudir_options',
        })
            .then(function(settings) {
                var typedSettings = settings as FaudirSettingsPayload;
                var display = attributes.display || 'person';
                var format = attributes.selectedFormat || 'default';

                var defaultFields = getDefaultFields(
                    typedSettings,
                    display,
                    format
                );

                if (defaultFields.length > 0) {
                    setAttributes({ selectedFields: defaultFields });
                }
            })
            .catch(function(error) {
                console.error('Error fetching default fields:', error);
            });
    }, [attributes.display, attributes.selectedFormat]);

    useEffect(function() {
        var ac = new AbortController();

        async function loadCategories() {
            try {
                var cats = await fetchAllPages<WPCategory>(
                    '/wp/v2/custom_taxonomy',
                    {},
                    ac.signal
                );
		
                if (!ac.signal.aborted) {
                    setCategories(cats);
                }
            } catch (error) {
                if (!ac.signal.aborted) {
                    console.error('Error fetching categories:', error);
                }
            }
        }

        loadCategories();

        return function() {
            ac.abort();
        };
    }, []);

    useEffect(function() {
        var ac = new AbortController();
        setIsLoadingPosts(true);

        async function loadPosts() {
            try {
                var query: CustomPersonParams = {
                    _fields: 'id,title,meta,post_language',
                    orderby: 'title',
                    order: 'asc',
                };

                var allPeople = await fetchAllPages<CustomPersonRESTApi>(
                    '/wp/v2/custom_person',
                    query,
                    ac.signal
                );

                if (!ac.signal.aborted) {
		    
                    setPosts(allPeople);
                }
            } catch (error) {
                if (!ac.signal.aborted) {
                    console.error('Error fetching posts:', error);
                }
            } finally {
                if (!ac.signal.aborted) {
                    setIsLoadingPosts(false);
                }
            }
        }

        loadPosts();

        return function() {
            ac.abort();
        };
    }, [selectedCategory, orgnr]);

    return (
        <div {...blockProps}>
            <BlockControls>
                <ToolbarGroup>
                    {attributes.initialSetup && (
                        <ToolbarItem>
                            {function() {
                                return (
                                    <ToolbarButton
                                        icon={!isAppearancePanelOpen ? styles : postAuthor}
                                        label={
                                            !isAppearancePanelOpen
                                                ? __("Change the Appearance", "rrze-faudir")
                                                : __("Change the Data", "rrze-faudir")
                                        }
                                        onClick={function() {
                                            setIsAppearancePanelOpen(!isAppearancePanelOpen);
                                        }}
                                    />
                                );
                            }}
                        </ToolbarItem>
                    )}
                    <ToolbarItem>
                        {function() {
                            return (
                                <ToolbarButton
                                    icon={!attributes.initialSetup ? edit : check}
                                    label={
                                        !attributes.initialSetup
                                            ? __("Configure your contact", "rrze-faudir")
                                            : __("Finish configuration", "rrze-faudir")
                                    }
                                    onClick={handleToolbarConfiguration}
                                />
                            );
                        }}
                    </ToolbarItem>
                </ToolbarGroup>
            </BlockControls>

            <InspectorControls>
                <PanelBody title={__('Data Selection', 'rrze-faudir')} initialOpen={!initialSetup}>
                    <ToggleGroupControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        isBlock
                        label={__('What type of Contact do you want to display?', 'rrze-faudir')}
                        help={__('Do you want to output a Person entry or a FAUdir Institution/Folder?', 'rrze-faudir')}
                        onChange={handleDisplayToggle}
                        value={isOrg ? 'org' : 'person'}
                    >
                        <ToggleGroupControlOption
                            label={__('Persons', 'rrze-faudir')}
                            value={'person'}
                        />
                        <ToggleGroupControlOption
                            label={__('Organization or FAUdir-Folder', 'rrze-faudir')}
                            value={'org'}
                        />
                    </ToggleGroupControl>

                    {!isOrg ? (
                        <>
                            <hr />
                            <PersonSelector
                                isLoadingPosts={isLoadingPosts}
                                posts={posts}
                                selectedPosts={selectedPosts}
                                togglePostSelection={togglePostSelection}
                            />
			    <hr />
                            <PersonIdentifierDetector
                                attributes={attributes}
                                setAttributes={setAttributes}
                            />
                            <hr />
                            <CategorySelector
                                categories={categories}
                                selectedCategory={selectedCategory}
                                setAttributes={setAttributes}
                            />
                            <hr />
                            <RoleSelector attributes={attributes} setAttributes={setAttributes} />
                            <hr />
                            <OrganizationNumberDetector
                                attributes={attributes}
                                setAttributes={setAttributes}
                            />
                            <hr />
                            <OrganizationIdentifierDetector
                                attributes={attributes}
                                setAttributes={setAttributes}
                            />
                        </>
                    ) : (
                        <>
                            <hr />
                            <OrganizationNumberDetector
                                attributes={attributes}
                                setAttributes={setAttributes}
                            />
                            <hr />
                            <OrganizationIdentifierDetector
                                attributes={attributes}
                                setAttributes={setAttributes}
                            />
                        </>
                    )}
                </PanelBody>

                <PanelBody title={__('Appearance', 'rrze-faudir')} initialOpen={false}>
                    <FormatSelector attributes={attributes} setAttributes={setAttributes} />
                    <hr />
                    <ShowHideSelector
                        attributes={attributes}
                        setAttributes={setAttributes}
                        setHasFormatDisplayName={setHasFormatDisplayName}
                    />
                    <hr />
                    <NameFormatSelector
                        attributes={attributes}
                        setAttributes={setAttributes}
                        hasFormatDisplayName={hasFormatDisplayName}
                    />
                </PanelBody>

                {attributes.display !== "org" && (
                    <PanelBody title={__('Sorting', 'rrze-faudir')} initialOpen={false}>
                        <SortSelector attributes={attributes} setAttributes={setAttributes} />
                        <hr />
                        <RoleSelector attributes={attributes} setAttributes={setAttributes} />
                    </PanelBody>
                )}
            </InspectorControls>

            {!initialSetup ? (
                <CustomServerSideRender attributes={attributes} />
            ) : (
                <CustomPlaceholder
                    attributes={attributes}
                    setAttributes={setAttributes}
                    isOrg={isOrg}
                    setIsOrg={setIsOrg}
                    isLoadingPosts={isLoadingPosts}
                    posts={posts}
                    selectedPosts={selectedPosts}
                    togglePostSelection={togglePostSelection}
                    categories={categories}
                    isAppearancePanelOpen={isAppearancePanelOpen}
                    setIsAppearancePanelOpen={setIsAppearancePanelOpen}
                    setHasFormatDisplayName={setHasFormatDisplayName}
                    hasFormatDisplayName={hasFormatDisplayName}
                />
            )}
        </div>
    );
}