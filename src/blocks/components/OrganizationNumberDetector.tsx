import {
  __experimentalHeading as Heading,
  TextControl
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "@wordpress/element";
import { EditProps } from "../faudir/types";

interface OrganizationNumberDetectorProps {
  attributes: EditProps["attributes"];
  setAttributes: EditProps["setAttributes"];
  label?: string;
  helpText?: string;
}

export default function OrganizationNumberDetector({
  attributes,
  setAttributes,
  label,
  helpText
}: OrganizationNumberDetectorProps) {
  const [errorMessage, setErrorMessage] = useState<string>("");
  const [localValue, setLocalValue] = useState<string>(attributes.orgnr ?? "");

  useEffect(function syncLocalValueFromAttributes() {
    const next = attributes.orgnr ?? "";
    if (next !== localValue) {
      setLocalValue(next);
    }
  }, [attributes.orgnr]);

  function handleOrgNrChange(value: string) {
    let cleaned = value
      .replace(/[^\d,]/g, "")
      .replace(/,{2,}/g, ",");

    cleaned = cleaned.replace(/^,/, "");

    const rawParts = cleaned.split(",");
    const parts = rawParts.filter(function (part) {
      return part.length > 0;
    });

    if (parts.length === 0) {
      setAttributes({ orgnr: "" });
      setErrorMessage("");
      setLocalValue(cleaned);
      return;
    }

    if (parts.length === 1) {
      const singleValue = parts[0];
      const attrValue = singleValue.length > 10 ? singleValue.slice(0, 10) : singleValue;

      if (attrValue.length >= 6 && attrValue.length <= 10) {
        setAttributes({ orgnr: attrValue });
        setErrorMessage("");
      } else {
        setAttributes({ orgnr: "" });
        setErrorMessage(
          __("Enter 6–10 digits, or a comma-separated list of 10-digit numbers.", "rrze-faudir")
        );
      }

      setLocalValue(cleaned);
      return;
    }

    const validTokens = parts.filter(function (part) {
      return part.length === 10;
    });
    const invalidCount = parts.length - validTokens.length;

    if (validTokens.length > 0) {
      setAttributes({ orgnr: validTokens.join(",") });

      if (invalidCount > 0) {
        setErrorMessage(
          __("Some entries are not 10 digits and were ignored.", "rrze-faudir")
        );
      } else {
        setErrorMessage("");
      }
    } else {
      setAttributes({ orgnr: "" });
      setErrorMessage(
        __("When entering multiple numbers, each must be exactly 10 digits.", "rrze-faudir")
      );
    }

    setLocalValue(cleaned);
  }

  return (
    <>
      <Heading level={3}>{__("Select organization by FAUOrg Number", "rrze-faudir")}</Heading>
      <TextControl
        label={label || __("FAUOrg Number", "rrze-faudir")}
        value={localValue}
        onChange={handleOrgNrChange}
        type="text"
        help={
          errorMessage ||
          (helpText || __("To display all Persons from within your Organization, insert your FAUOrg Number (Cost center number).", "rrze-faudir"))
        }
      />
    </>
  );
}