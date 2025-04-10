import {__} from "@wordpress/i18n";
import {TextControl} from "@wordpress/components";
import { EditProps } from "../types";

interface NameFormatSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
}

export default function NameFormatSelector({attributes, setAttributes}: NameFormatSelectorProps){
  const {format_displayname} = attributes;
  const handleFormatDisplayNameChange = (value: string) => {
    setAttributes({format_displayname: value});
  };

  return (
    <TextControl
      label={__('Change display format', 'rrze-faudir')}
      value={format_displayname}
      onChange={handleFormatDisplayNameChange}
      type="text"
    />
  );
};