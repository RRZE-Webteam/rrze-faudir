import {
  __experimentalHeading as Heading,
  TextControl
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "@wordpress/element";
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

  const [localValue, setLocalValue] = useState<string>(
    attributes.identifier || ""
  );

  const [errorMessage, setErrorMessage] = useState<string>("");


  useEffect(function syncLocalValueFromAttributes() {
    const next = attributes.identifier || "";

    if (next !== localValue) {
      setLocalValue(next);
    }

  }, [attributes.identifier]);


  function handlePersonIdentifierChange(value: string) {

    let workingValue = value.trim();


    /*
     * Schritt 1:
     * Query-Parameter (?) und Fragment (#) entfernen
     */

    const cutPos = Math.min(
      ...[
        workingValue.indexOf("?"),
        workingValue.indexOf("#")
      ].filter(function(pos) {
        return pos !== -1;
      }),
      workingValue.length
    );

    workingValue = workingValue.substring(0, cutPos);



    /*
     * Schritt 2:
     * URL-Prefix erkennen
     */

    const prefixMatch = workingValue.match(
      /^https?:\/\/faudir\.fau\.de\/public\/person\/(.+)$/i
    );

    let extractedId = prefixMatch
      ? prefixMatch[1]
      : workingValue;



    /*
     * Schritt 3:
     * Slash danach abschneiden
     */

    const slashPos = extractedId.indexOf("/");

    if (slashPos !== -1) {
      extractedId = extractedId.substring(0, slashPos);
    }



    /*
     * Schritt 4:
     * final sanitizen
     */

    extractedId = extractedId
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]/g, "");



    /*
     * Schritt 5:
     * validieren
     */

    if (!/^[a-z0-9]{10,11}$/.test(extractedId)) {

      setAttributes({ identifier: "" });

      setErrorMessage(
        __(
          "Please enter a valid FAUdir URL or a valid 10- or 11-character person identifier.",
          "rrze-faudir"
        )
      );

    } else {

      setAttributes({ identifier: extractedId });

      setErrorMessage("");

    }


    setLocalValue(value);
  }


  return (
    <>
      <Heading level={3}>
        {__("Direct Select via FAUdir", "rrze-faudir")}
      </Heading>

      <TextControl
        label={
          label ||
          __("Via Person Identifier or FAUdir-URL", "rrze-faudir")
        }

        value={localValue}

        onChange={handlePersonIdentifierChange}

        type="text"

        help={
          errorMessage ||
          helpText ||
          __(
            'Please enter either a FAUdir URL ("https://faudir.fau.de/public/person/..."), or the person identifier.',
            "rrze-faudir"
          )
        }
      />
    </>
  );
}