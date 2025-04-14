import {__} from "@wordpress/i18n";
import {TextControl} from "@wordpress/components";
import {EditProps} from "../types";

interface NameFormatSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
  hasFormatDisplayName: boolean;
}

export default function NameFormatSelector({attributes, setAttributes, hasFormatDisplayName}: NameFormatSelectorProps) {
  const {format_displayname} = attributes;
  const handleFormatDisplayNameChange = (value: string) => {
    setAttributes({format_displayname: value});
  };

  return (
    <>
      {hasFormatDisplayName &&
          <TextControl
              label={__('Change display format', 'rrze-faudir')}
              value={format_displayname}
              onChange={handleFormatDisplayNameChange}
              type="text"
              help={"Parameter: #givenName#" + ", " + "#displayname#" + ", " + "#familyName#" + ", " + "#honorificPrefix#" + ", " + "#honorificSuffix#" + ", " +  "#titleOfNobility#"}
          />
      }
    </>
  );
};