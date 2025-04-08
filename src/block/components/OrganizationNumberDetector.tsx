import {TextControl} from "@wordpress/components";
import {__} from "@wordpress/i18n";

interface OrganizationNumberDetectorProps {
  attributes: {
    orgnr: string;
    [key: string]: any;
  };
  setAttributes: (newAttrs: object) => void;
}

export default function OrganizationNumberDetector({attributes, setAttributes}: OrganizationNumberDetectorProps
) {
  const handleOrgNrChange = (value: string) => {
    setAttributes({orgnr: value});
  };

  return (
    <TextControl
      label={__('Organization Number', 'rrze-faudir')}
      value={attributes.orgnr}
      onChange={handleOrgNrChange}
      type="text"
      help={__('Please enter at least 10 digits.', 'rrze-faudir')}
    />
  )
}