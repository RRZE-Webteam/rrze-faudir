import {__} from '@wordpress/i18n';
import {
  Placeholder,
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
  __experimentalSpacer as Spacer,
  __experimentalHeading as Heading, Notice,
} from '@wordpress/components';
import {EditProps} from '../types';
import PersonSelector from './PersonSelector';
import {PersonSelectorProps} from "./PersonSelector";
import CategorySelector from "./CategorySelector";
import CustomServerSideRender from "./CustomServerSideRender";
import OrganizationNumberDetector from "./OrganizationNumberDetector";
import OrganizationIdDetector from "./OrganizationIdDetector";

interface CustomPlaceholderProps extends PersonSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
  isOrg: boolean;
  setIsOrg: (isOrg: boolean) => void;
  isLoadingPosts: boolean;
  categories: any[];
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
                                            categories
                                          }: CustomPlaceholderProps) {

  return (
    <>
      <Placeholder
        label={__('Setup your FAUdir Block', 'rrze-faudir')}
        instructions={__('Get started by selecting your desired configuration.')}
      >
        <div>
          <Spacer
            paddingBottom="1.5rem"
            paddingTop="1rem"
          >
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
          </Spacer>
          <hr/>
          {!isOrg ? (
            <>
              <Spacer paddingTop="1rem">
                <Heading level={2}>{__('Select Contacts to display', 'rrze-faudir')}</Heading>
              </Spacer>
              <Spacer paddingTop="1.5rem" paddingBottom="1rem">
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
              <Spacer paddingBottom="1rem">
                <OrganizationNumberDetector
                  attributes={attributes}
                  setAttributes={setAttributes}
                />
              </Spacer>

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
                label={__('Display via FAUOrg Number', 'rrze-faudir')}
                helpText={__('To display an Institution as contact, insert your FAUOrg Number (Cost center number).', 'rrze-faudir')}
              />
              <OrganizationIdDetector attributes={attributes} setAttributes={setAttributes}/>
              </Spacer>
            </>
          )}
          <hr/>
          <Spacer paddingTop="1rem" paddingBottom="1.5rem">
            <Heading level={2}>{__('Preview', 'rrze-faudir')}</Heading>
            <CustomServerSideRender attributes={attributes}/>
          </Spacer>
        </div>
      </Placeholder>
    </>);
}