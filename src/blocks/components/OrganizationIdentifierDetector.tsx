import {
  __experimentalHeading as Heading,
  TextControl
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "@wordpress/element";
import { EditProps } from "../faudir/types";

interface OrganizationIdDetectorProps {
  attributes: EditProps["attributes"];
  setAttributes: EditProps["setAttributes"];
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

  useEffect(function syncLocalValueFromAttributes() {
    const next = attributes.orgid || "";
    if (next !== localValue) {
      setLocalValue(next);
    }
  }, [attributes.orgid]);

  function handleOrgIdChange(value: string) {
    const trimmedValue = value.trim();

    const match = trimmedValue.match(/^https?:\/\/faudir\.fau\.de\/public\/org\/([^/]+)\/?$/i);
    let extractedId = match ? match[1] : trimmedValue;

    extractedId = extractedId.trim().toLowerCase();
    extractedId = extractedId.replace(/[^a-z0-9]/g, "");

    if (!/^[a-z0-9]{10}$/.test(extractedId)) {
      setAttributes({ orgid: "" });
      setErrorMessage(
        __("Please enter a valid FAUdir URL or a valid 10-character FAUdir identifier.", "rrze-faudir")
      );
    } else {
      setAttributes({ orgid: extractedId });
      setErrorMessage("");
    }

    setLocalValue(value);
  }

  return (
    <>
      <Heading level={3}>{__("Select organization by FAUdir Identifier", "rrze-faudir")}</Heading>
      <TextControl
        label={label || __("Via FAUdir-ID or FAUdir-URL", "rrze-faudir")}
        value={localValue}
        onChange={handleOrgIdChange}
        type="text"
        help={
          errorMessage ||
          helpText ||
          __(
            'Please enter either the complete FAUdir URL ("https://faudir.fau.de/public/org/..."), or the FAUdir identifier (the last part of the URL after "org/").',
            "rrze-faudir"
          )
        }
      />
    </>
  );
}