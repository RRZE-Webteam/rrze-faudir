import {__} from '@wordpress/i18n';
import {
  Placeholder,
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
  __experimentalHeading as Heading
} from '@wordpress/components';
import {EditProps} from '../types';

interface CustomPlaceholderProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
  isOrg: boolean;
  setIsOrg: (isOrg: boolean) => void;
}

export default function CustomPlaceholder({attributes, setAttributes, isOrg, setIsOrg}: CustomPlaceholderProps) {
  return (<>
    <Placeholder
      label={__('Setup your FAUdir Block', 'rrze-faudir')}
      instructions={__('Get started by selecting your desired configuration.')}
    >
      <div>
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
            label={__('Person', 'rrze-faudir')}
            value={'person'}
          />
          <ToggleGroupControlOption
            label={__('Organization or Folder', 'rrze-faudir')}
            value={'org'}
          />
        </ToggleGroupControl>
        <hr />
        <Heading>__{'Select a Contact for display', 'rrze-faudir'}</Heading>


      </div>

    </Placeholder>
  </>);
}