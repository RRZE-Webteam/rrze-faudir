import { __experimentalHeading as Heading, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState } from "@wordpress/element";
import { EditProps } from "../faudir/types";

interface PersonIdentifierDetectorProps {
  attributes: EditProps["attributes"];
  setAttributes: EditProps["setAttributes"];
  label?: string;
  helpText?: string;
}

export default function PersonIdentifierDetector({
                                                 attributes,
                                                 setAttributes,
                                                 label,
                                                 helpText
                                               }: PersonIdentifierDetectorProps) {
  const [localValue, setLocalValue] = useState<string>(attributes.identifier || "");
  const [errorMessage, setErrorMessage] = useState<string>("");
  const handlePersonIdentifierChange = (value: string) => {
    const trimmedValue = value.trim();

    // RegEx for "https://faudir.fau.de/public/person/<id>"
    const match = trimmedValue.match(/^https?:\/\/faudir\.fau\.de\/public\/person\/([^/]+)\/?$/);
    let extractedId = match ? match[1] : trimmedValue;

    if (!extractedId) {
      setAttributes({ identifier: "" });
      setErrorMessage(__("Please enter a valid FAUdir-URL or the identifier.", "rrze-faudir"));
    } else {
      setAttributes({ identifier: extractedId });
      setErrorMessage("");
    }

    setLocalValue(value);
  };

  return (
    <>
      <Heading level={3}>{__("Direct Select via FAUdir", "rrze-faudir")}</Heading>
      <TextControl
        label={label || __('Via Person Identifier or FAUdir-URL', 'rrze-faudir')}
        value={localValue}
        onChange={handlePersonIdentifierChange}
        type="text"
        help={
          errorMessage
            ? errorMessage
            : helpText || __(
            'Please enter either a FAUdir-URL ("https://faudir.fau.de/public/person/…"), or the Person-Identifier. ' +
            'This will display your contact, even if the contact is not created via Dashboard > Persons.',
            'rrze-faudir'
          )
        }
      />
    </>
  );
}