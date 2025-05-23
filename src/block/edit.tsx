import {__} from '@wordpress/i18n';
import {InspectorControls, BlockControls, useBlockProps} from '@wordpress/block-editor';
import {
  PanelBody,
  ToolbarGroup,
  ToolbarItem,
  ToolbarButton,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
  __experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import {useState, useEffect} from '@wordpress/element';
import CustomServerSideRender from "./components/CustomServerSideRender";
import apiFetch, {APIFetchOptions} from '@wordpress/api-fetch';
import {fieldMapping} from "./defaults";
import OrganizationNumberDetector from "./components/OrganizationNumberDetector";
import PersonSelector from "./components/PersonSelector";
import CategorySelector from "./components/CategorySelector";
import FormatSelector from "./components/FormatSelector";
import ShowHideSelector from "./components/ShowHideSelector";
import NameFormatSelector from "./components/NameFormatSelector";
import {edit, check, postAuthor, styles} from "@wordpress/icons";
import '../scss/rrze-faudir.scss';
import './editor.scss';
import {
  EditProps,
  WPCategory,
  CustomPersonParams,
  CustomPersonRESTApi,
} from "./types";
import CustomPlaceholder from "./components/CustomPlaceholder";
import OrganizationIdentifierDetector from "./components/OrganizationIdentifierDetector";
import RoleSelector from "./components/RoleSelector";
import SortSelector from "./components/SortSelector";
import PersonIdentifierDetector from "./components/PersonIdentifierDetector";

export default function Edit({attributes, setAttributes}: EditProps) {
  const [categories, setCategories] = useState([]);
  const [posts, setPosts] = useState([]);
  const [isLoadingPosts, setIsLoadingPosts] = useState(false);
  const [isOrg, setIsOrg] = useState(attributes.display === 'org');
  const [isAppearancePanelOpen, setIsAppearancePanelOpen] = useState<boolean>(false);
  const [hasFormatDisplayName, setHasFormatDisplayName] = useState<boolean>(false);

  const blockProps = useBlockProps();
  const {
    selectedCategory = '',
    selectedPosts = [],
    orgnr = '',
    initialSetup
  } = attributes;

  const handleToolbarConfiguration = () => {
    setAttributes({
      initialSetup: !initialSetup,
    });
  }

  useEffect(() => {
    setAttributes({
      display: isOrg ? 'org' : 'person',
    })
  }, [isOrg]);

  useEffect(() => {
    if (
      !attributes.selectedFields ||
      attributes.selectedFields.length === 0
    ) {
      apiFetch({
        path: '/wp/v2/settings/rrze_faudir_options',
      })
        .then((settings: any) => {
          if (settings?.default_output_fields) {
            const mappedFields = settings.default_output_fields
              .map((field: string) => fieldMapping[field])
              .filter((field: string) => field !== undefined); // Remove any unmapped fields

            setAttributes({
              selectedFields: mappedFields,
            });
          }
        })
        .catch((error) => {
          console.error('Error fetching default fields:', error);
        });
    }
  }, []);

  useEffect(() => {
    apiFetch({path: '/wp/v2/custom_taxonomy?per_page=100'})
      .then((data: WPCategory[]) => {
        setCategories(data);
      })
      .catch((error) => {
        console.error('Error fetching categories:', error);
      });
  }, []);

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
  };

  return (
    <div {...blockProps}>
      <BlockControls>
        <ToolbarGroup>
          {attributes.initialSetup &&
              <ToolbarItem>
                {() => (
                  <>
                    <ToolbarButton
                      icon={!isAppearancePanelOpen ? styles : postAuthor}
                      label={
                        !isAppearancePanelOpen
                          ? __("Change the Appearance", "rrze-faudir")
                          : __("Change the Data", "rrze-faudir")
                      }
                      onClick={() => {
                        setIsAppearancePanelOpen(!isAppearancePanelOpen);
                      }}
                    />
                  </>
                )}
              </ToolbarItem>
          }
          <ToolbarItem>
            {() => (
              <>
                <ToolbarButton
                  icon={!attributes.initialSetup ? edit : check}
                  label={
                    !attributes.initialSetup
                      ? __("Configure your contact", "rrze-faudir")
                      : __("Finish configuration", "rrze-faudir")
                  }
                  onClick={handleToolbarConfiguration}
                />
              </>
            )}
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
            onChange={(value: string) => value === 'person' ? setIsOrg(false) : setIsOrg(true)}
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
              <CategorySelector
                categories={categories}
                selectedCategory={selectedCategory}
                setAttributes={setAttributes}
              />
              <hr />
              <RoleSelector setAttributes={setAttributes}/>
              <hr />
              <OrganizationNumberDetector
                attributes={attributes}
                setAttributes={setAttributes}
              />
              <hr />
              <PersonIdentifierDetector attributes={attributes} setAttributes={setAttributes} />
            </>
          ) : (
            <>
              <hr />
              <OrganizationNumberDetector
                attributes={attributes}
                setAttributes={setAttributes}
              />
              <hr />
              <OrganizationIdentifierDetector attributes={attributes} setAttributes={setAttributes}/>
            </>
          )}
        </PanelBody>
        <PanelBody title={__('Appearance', 'rrze-faudir')} initialOpen={false}>
          <FormatSelector attributes={attributes} setAttributes={setAttributes}/>
          <hr />
          <ShowHideSelector attributes={attributes} setAttributes={setAttributes} setHasFormatDisplayName={setHasFormatDisplayName}/>
          <hr />
          <NameFormatSelector attributes={attributes} setAttributes={setAttributes} hasFormatDisplayName={hasFormatDisplayName}/>
        </PanelBody>
        {attributes.display !== "org" &&
            <PanelBody title={__('Sorting', 'rrze-faudir')} initialOpen={false}>
                <SortSelector attributes={attributes} setAttributes={setAttributes}/>
                <hr />
                <RoleSelector setAttributes={setAttributes}/>
            </PanelBody>
        }
      </InspectorControls>
      <>
        {!initialSetup ? (
          <CustomServerSideRender attributes={attributes}/>
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
      </>
    </div>
  );
}
