import {
  __experimentalHeading as Heading,
  TextControl
} from "@wordpress/components";
import {__} from "@wordpress/i18n";
import {useState} from "@wordpress/element";
import {EditProps} from "../types";

interface OrganizationNumberDetectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
  label?: string;
  helpText?: string;
}

export default function OrganizationNumberDetector({
                                                     attributes,
                                                     setAttributes,
                                                     label,
                                                     helpText
                                                   }: OrganizationNumberDetectorProps
) {
  const [errorMessage, setErrorMessage] = useState<string>("");
  const [localValue, setLocalValue] = useState(attributes.orgnr ?? "");

  const handleOrgNrChange = (value: string) => {
    let sanitizedValue = value.replace(/\D/g, "");
    if (sanitizedValue.length > 10) {
      sanitizedValue = sanitizedValue.substring(0, 10);
    }

    setLocalValue(sanitizedValue);

    if (sanitizedValue.length === 0) {
      setAttributes({orgnr: ""});
      setErrorMessage("");
    } else if (sanitizedValue.length === 10) {
      setAttributes({orgnr: sanitizedValue});
      setErrorMessage("");
    } else {
      setAttributes({orgnr: ""});
      setErrorMessage(__("Your FAUOrg-Number needs to be exactly 10 digits.", "rrze-faudir"));
    }
  };

  return (
    <>
      <Heading level={3}>{__("Display Organization", "rrze-faudir")}</Heading>
      <TextControl
        label={label || __('FAUOrg Number', 'rrze-faudir')}
        value={localValue}
        onChange={handleOrgNrChange}
        type="text"
        help={
          errorMessage ||
          (helpText || __('To display all Persons from within your Organization, insert your FAUOrg Number (Cost center number).', 'rrze-faudir'))
        }
      />
    </>
  )
}