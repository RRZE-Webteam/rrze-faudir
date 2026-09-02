import { __ } from "@wordpress/i18n";
import {
  InspectorControls,
  BlockControls,
  useBlockProps,
} from "@wordpress/block-editor";
import {
  PanelBody,
  ToolbarGroup,
  ToolbarItem,
  ToolbarButton,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
  __experimentalToggleGroupControl as ToggleGroupControl,
} from "@wordpress/components";
import { useState, useEffect } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { pencil as edit, check, postAuthor, styles } from "@wordpress/icons";

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
import { getDefaultFields, FaudirSettingsPayload } from "./defaults";
import { fetchAllPages } from "./helper";

import "../../scss/rrze-faudir.scss";
import "./editor.scss";

export default function Edit({ attributes, setAttributes }: EditProps) {
  const [categories, setCategories] = useState<WPCategory[]>([]);
  const [posts, setPosts] = useState<CustomPersonRESTApi[]>([]);
  const [isLoadingPosts, setIsLoadingPosts] = useState(false);
  const [isOrg, setIsOrg] = useState(attributes.display === "org");
  const [isAppearancePanelOpen, setIsAppearancePanelOpen] =
    useState<boolean>(false);
  const [hasFormatDisplayName, setHasFormatDisplayName] =
    useState<boolean>(false);

  const blockProps = useBlockProps();

  const {
    selectedCategory = "",
    selectedPosts = [],
    orgnr = "",
    initialSetup,
  } = attributes;

  const handleToolbarConfiguration = () => {
    setAttributes({
      initialSetup: !initialSetup,
    });
  };

  const handleDisplayToggle = (value: string | number | undefined) => {
    if (value === "person") {
      setIsOrg(false);
    } else {
      setIsOrg(true);
    }
  };

  const togglePostSelection = (postId: number) => {
    const updatedSelectedPosts = selectedPosts.includes(postId)
      ? selectedPosts.filter((id) => {
          return id !== postId;
        })
      : [...selectedPosts, postId];

    const updatedPersonIds = updatedSelectedPosts
      .map((id) => {
        const post = posts.find((p) => {
          return p.id === id;
        });
        return post?.meta?.person_id || null;
      })
      .filter((value): value is string => {
        return typeof value === "string" && value !== "";
      });

    setAttributes({
      selectedPosts: updatedSelectedPosts,
      selectedPersonIds: updatedPersonIds,
    });
  };

  useEffect(() => {
    setAttributes({
      display: isOrg ? "org" : "person",
    });
  }, [isOrg]);

  useEffect(() => {
    if (attributes.selectedFields && attributes.selectedFields.length > 0) {
      return;
    }

    apiFetch({
      path: "/wp/v2/settings/rrze_faudir_options",
    })
      .then((settings) => {
        const typedSettings = settings as FaudirSettingsPayload;
        const display = attributes.display || "person";
        const format = attributes.selectedFormat || "default";

        const defaultFields = getDefaultFields(typedSettings, display, format);

        if (defaultFields.length > 0) {
          setAttributes({ selectedFields: defaultFields });
        }
      })
      .catch((error) => {
        console.error("Error fetching default fields:", error);
      });
  }, [attributes.display, attributes.selectedFormat]);

  useEffect(() => {
    const ac = new AbortController();

    const loadCategories = async () => {
      try {
        const cats = await fetchAllPages<WPCategory>(
          "/wp/v2/custom_taxonomy",
          {},
          ac.signal,
        );

        if (!ac.signal.aborted) {
          setCategories(cats);
        }
      } catch (error) {
        if (!ac.signal.aborted) {
          console.error("Error fetching categories:", error);
        }
      }
    };

    loadCategories();

    return () => {
      ac.abort();
    };
  }, []);

  useEffect(() => {
    const ac = new AbortController();
    setIsLoadingPosts(true);

    const loadPosts = async () => {
      try {
        const query: CustomPersonParams = {
          _fields: "id,title,meta,post_language",
          orderby: "title",
          order: "asc",
        };

        const allPeople = await fetchAllPages<CustomPersonRESTApi>(
          "/wp/v2/custom_person",
          query,
          ac.signal,
        );

        if (!ac.signal.aborted) {
          setPosts(allPeople);
        }
      } catch (error) {
        if (!ac.signal.aborted) {
          console.error("Error fetching posts:", error);
        }
      } finally {
        if (!ac.signal.aborted) {
          setIsLoadingPosts(false);
        }
      }
    };

    loadPosts();

    return () => {
      ac.abort();
    };
  }, [selectedCategory, orgnr]);

  return (
    <div {...blockProps}>
      <BlockControls>
        <ToolbarGroup>
          {attributes.initialSetup && (
            <ToolbarItem>
              {() => {
                return (
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
                );
              }}
            </ToolbarItem>
          )}
          <ToolbarItem>
            {() => {
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
        <PanelBody
          title={__("Data Selection", "rrze-faudir")}
          initialOpen={!initialSetup}
        >
          <ToggleGroupControl
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            isBlock
            label={__(
              "What type of Contact do you want to display?",
              "rrze-faudir",
            )}
            help={__(
              "Do you want to output a Person entry or a FAUdir Institution/Folder?",
              "rrze-faudir",
            )}
            onChange={handleDisplayToggle}
            value={isOrg ? "org" : "person"}
          >
            <ToggleGroupControlOption
              label={__("Persons", "rrze-faudir")}
              value={"person"}
            />
            <ToggleGroupControlOption
              label={__("Organization or FAUdir-Folder", "rrze-faudir")}
              value={"org"}
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
              <RoleSelector
                attributes={attributes}
                setAttributes={setAttributes}
              />
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

        <PanelBody title={__("Appearance", "rrze-faudir")} initialOpen={false}>
          <FormatSelector
            attributes={attributes}
            setAttributes={setAttributes}
          />
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
          <PanelBody title={__("Sorting", "rrze-faudir")} initialOpen={false}>
            <SortSelector
              attributes={attributes}
              setAttributes={setAttributes}
            />
            <hr />
            <RoleSelector
              attributes={attributes}
              setAttributes={setAttributes}
            />
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
