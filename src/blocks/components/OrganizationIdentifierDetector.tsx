import { __experimentalHeading as Heading, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState } from "@wordpress/element";

interface OrganizationIdDetectorProps {
  attributes: {
    orgid?: string;
  };
  setAttributes: (attributes: Partial<OrganizationIdDetectorProps["attributes"]>) => void;
  label?: string;
  helpText?: string;
}

export default function OrganizationIdentifierDetector({
                                                 attributes,
                                                 setAttributes,
                                                 label,
                                                 helpText
                                               }: OrganizationIdDetectorProps) {
  const [localValue, setLocalValue] = useState<string>(attributes.orgid || "");
  const [errorMessage, setErrorMessage] = useState<string>("");

  const handleOrgIdChange = (value: string) => {
    const trimmedValue = value.trim();

    // RegEx for "https://faudir.fau.de/public/org/<id>"
    const match = trimmedValue.match(/^https?:\/\/faudir\.fau\.de\/public\/org\/([^/]+)\/?$/);
    let extractedId = match ? match[1] : trimmedValue;

    if (!extractedId || extractedId.length < 10) {
      setAttributes({ orgid: "" });
      setErrorMessage(__("Please enter a valid FAUdir-URL or the FAUdir-identifier.", "rrze-faudir"));
    } else {
      setAttributes({ orgid: extractedId });
      setErrorMessage("");
    }

    setLocalValue(value);
  };

  return (
    <>
      <Heading level={3}>{__("Select organization by FAUdir Identifier", "rrze-faudir")}</Heading>
      <TextControl
        label={label || __('Via FAUdir-ID or FAUdir-URL', 'rrze-faudir')}
        value={localValue}
        onChange={handleOrgIdChange}
        type="text"
        help={
          errorMessage
            ? errorMessage
            : helpText || __(
            'Please enter either the complete FAUdir-URL ("https://faudir.fau.de/public/org/…"), or the FAUdir-Identifier (the last part of the URL after "org/".',
            'rrze-faudir'
          )
        }
      />
    </>
  );
}
