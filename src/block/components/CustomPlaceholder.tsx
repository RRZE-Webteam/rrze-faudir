import {__} from '@wordpress/i18n';
import {
  Placeholder,
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
  __experimentalSpacer as Spacer,
  __experimentalHeading as Heading,
  Button,
  Panel,
  PanelBody
} from '@wordpress/components';
import {EditProps} from '../types';
import PersonSelector from './PersonSelector';
import {PersonSelectorProps} from "./PersonSelector";
import CategorySelector from "./CategorySelector";
import CustomServerSideRender from "./CustomServerSideRender";
import OrganizationNumberDetector from "./OrganizationNumberDetector";
import OrganizationIdentifierDetector from "./OrganizationIdentifierDetector";
import FormatSelector from "./FormatSelector";
import ShowHideSelector from "./ShowHideSelector";
import NameFormatSelector from "./NameFormatSelector";
import PersonIdentifierDetector from "./PersonIdentifierDetector";

interface CustomPlaceholderProps extends PersonSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
  isOrg: boolean;
  setIsOrg: (isOrg: boolean) => void;
  isLoadingPosts: boolean;
  categories: any[];
  isAppearancePanelOpen: boolean;
  setIsAppearancePanelOpen: (isAppearancePanelOpen: boolean) => void;
  setHasFormatDisplayName: (hasFormatDisplayName: boolean) => void;
  hasFormatDisplayName: boolean;
}

export default function CustomPlaceholder({
                                            attributes,
                                            setAttributes,
                                            isOrg,
                                            setIsOrg,
                                            isLoadingPosts,
                                            posts,
                                            selectedPosts,
                                            togglePostSelection,
                                            categories,
                                            isAppearancePanelOpen,
                                            setIsAppearancePanelOpen,
                                            setHasFormatDisplayName,
                                            hasFormatDisplayName,
                                          }: CustomPlaceholderProps) {
  //useState


  const onClickChangeAppearance = () => {
    setIsAppearancePanelOpen(true);
  }
  const onClickInitialSetupConfirm = () => {
    setAttributes({
      initialSetup: false,
    });
  }

  const onClickChangeData = () => {
    setIsAppearancePanelOpen(false);
  }

  return (
    <>
      <Placeholder
        label={__('Setup your FAUdir Block', 'rrze-faudir')}
      >
        {!isAppearancePanelOpen ? (
            <div style={{minWidth: "100%"}}>
              <Spacer
                paddingBottom="1.5rem"
                paddingTop="1rem"
              >
                  <Heading level={2}>{__('Which type of contact would you like to display?', 'rrze-faudir')}</Heading>
                <ToggleGroupControl
                  __next40pxDefaultSize
                  __nextHasNoMarginBottom
                  isBlock
                  label={__('Contact type', 'rrze-faudir')}
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
              </Spacer>
              <hr/>
              {!isOrg ? (
                <>
                  <Spacer paddingTop="1rem">
                    <Heading level={2}>{__('Select Contacts to display', 'rrze-faudir')}</Heading>
                  </Spacer>
                  <div style={{minWidth: '100%'}}>
                    <Panel>
                      <PanelBody title={__('Select Contacts from your WordPress Site', 'rrze-faudir')}
                                 initialOpen={false}>
                        <>
                          <Spacer
                            paddingTop="1rem"
                            paddingBottom="1.5rem"
                          >
                            <PersonSelector
                              isLoadingPosts={isLoadingPosts}
                              posts={posts}
                              selectedPosts={selectedPosts}
                              togglePostSelection={togglePostSelection}
                            />
                            <CategorySelector
                              categories={categories}
                              selectedCategory={attributes.selectedCategory}
                              setAttributes={setAttributes}
                            />
                          </Spacer>
                        </>
                      </PanelBody>
                      <PanelBody title={__('Select Contacts directly from FAUdir', 'rrze-faudir')} initialOpen={false}>
                        <Spacer
                          paddingTop="1rem"
                          paddingBottom="1.5rem"
                        >
			  <PersonIdentifierDetector attributes={attributes} setAttributes={setAttributes}/>
                          
			  <OrganizationNumberDetector
                            attributes={attributes}
                            setAttributes={setAttributes}
                          />
			  <OrganizationIdentifierDetector attributes={attributes} setAttributes={setAttributes}/>
			  
                          
                        </Spacer>
                      </PanelBody>
                    </Panel>
                  </div>
                </>
              ) : (
                <>
                  <Spacer paddingTop="1rem">
                    <Heading level={2}>{__('Select Organization or FAUdir-Folder to display', 'rrze-faudir')}</Heading>
                  </Spacer>
                  <Spacer paddingTop="1.5rem" paddingBottom="1rem">
                    <OrganizationNumberDetector
                      attributes={attributes}
                      setAttributes={setAttributes}
                      label={__('Via FAUOrg Number', 'rrze-faudir')}
                      helpText={__('To display an Institution as contact, insert your FAUOrg Number (Cost center number).', 'rrze-faudir')}
                    />
                    <OrganizationIdentifierDetector attributes={attributes} setAttributes={setAttributes}/>
                  </Spacer>
                </>
              )}
              <Spacer paddingTop="1rem" paddingBottom="1.5rem">
                <>
                  <Button
                    variant="tertiary"
                    onClick={onClickInitialSetupConfirm}
                  >
                    {__("Finish initial setup", "rrze-faudir")}
                  </Button>
                  <Button
                    variant="primary"
                    onClick={onClickChangeAppearance}
                  >
                    {__("Step 2: Change Appearance", "rrze-faudir")}
                  </Button>
                </>
              </Spacer>
              <hr/>
              <Spacer paddingTop="1rem" paddingBottom="1.5rem">
                <Heading level={2}>{__('Preview', 'rrze-faudir')}</Heading>
                <CustomServerSideRender attributes={attributes}/>
              </Spacer>
            </div>
          ) :
          (
            <>
              <div>
                <Spacer
                  paddingBottom="1.5rem"
                  paddingTop="1rem"
                >
                  <>
                    <Heading level={2}>{__('Configure the appearance of your Contact', 'rrze-faudir')}</Heading>
                    <FormatSelector attributes={attributes} setAttributes={setAttributes}/>
                    <ShowHideSelector attributes={attributes} setAttributes={setAttributes} setHasFormatDisplayName={setHasFormatDisplayName}/>
                    <NameFormatSelector attributes={attributes} setAttributes={setAttributes} hasFormatDisplayName={hasFormatDisplayName}/>
                    <Spacer paddingTop="1rem"/>
                    <Button
                      variant="tertiary"
                      onClick={onClickChangeData}
                    >
                      {__("Back to Data selection", "rrze-faudir")}
                    </Button>
                    <Button
                      variant="primary"
                      onClick={onClickInitialSetupConfirm}
                    >
                      {__("Finish initial setup", "rrze-faudir")}
                    </Button>
                    <hr/>
                    <Spacer paddingTop="1rem" paddingBottom="1.5rem">
                      <Heading level={2}>{__('Preview', 'rrze-faudir')}</Heading>
                      <CustomServerSideRender attributes={attributes}/>
                    </Spacer>
                  </>
                </Spacer>
              </div>
            </>
          )
        }
      </Placeholder>
    </>)
    ;
}