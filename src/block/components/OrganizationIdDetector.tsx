import { __experimentalHeading as Heading, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState } from "@wordpress/element";
import { EditProps } from "../types";

interface OrganizationIdDetectorProps {
  attributes: EditProps["attributes"];
  setAttributes: EditProps["setAttributes"];
  label?: string;
  helpText?: string;
}

export default function OrganizationIdDetector({
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

    if (!extractedId) {
      setAttributes({ orgid: "" });
      setErrorMessage(__("Please enter a valid FAUdir-URL or the identifier.", "rrze-faudir"));
    } else {
      setAttributes({ orgid: extractedId });
      setErrorMessage("");
    }

    setLocalValue(value);
  };

  return (
    <>
      <Heading level={3}>{__("Display Faudir Folder", "rrze-faudir")}</Heading>
      <TextControl
        label={label || __('Via FAUorg-ID or FAUdir-URL', 'rrze-faudir')}
        value={localValue}
        onChange={handleOrgIdChange}
        type="text"
        help={
          errorMessage
            ? errorMessage
            : helpText || __(
            'Please enter either a FAUdir-URL ("https://faudir.fau.de/public/org/…"), or the Identifier.',
            'rrze-faudir'
          )
        }
      />
    </>
  );
}