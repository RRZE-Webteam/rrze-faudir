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
	// Nur Ziffern und Kommata erlauben, doppelte Kommata reduzieren
	let cleaned = value
	  .replace(/[^\d,]/g, '')  // alles außer Ziffer/Komma raus
	  .replace(/,{2,}/g, ','); // mehrere Kommata -> eins

	// Führendes Komma entfernen (trailing Komma lassen, damit weiter getippt werden kann)
	cleaned = cleaned.replace(/^,/, '');

	const rawParts = cleaned.split(',');      // inkl. evtl. leerem letztem Teil bei trailing Komma
	const parts    = rawParts.filter(p => p.length > 0);

	// Nichts drin
	if (parts.length === 0) {
	  setAttributes({ orgnr: '' });
	  setErrorMessage('');
	  setLocalValue(cleaned);
	  return;
	}

	// EIN Wert (kein echtes Multi)
	if (parts.length === 1) {
	  const p = parts[0];
	  // Für das gespeicherte Attribut ggf. auf max 10 kürzen (Anzeige bleibt wie getippt)
	  const attrVal = p.length > 10 ? p.slice(0, 10) : p;

	  if (attrVal.length >= 6 && attrVal.length <= 10) {
	    setAttributes({ orgnr: attrVal });
	    setErrorMessage('');
	  } else {
	    setAttributes({ orgnr: '' });
	    setErrorMessage(
	      __('Enter 6–10 digits, or a comma-separated list of 10-digit numbers.', 'rrze-faudir')
	    );
	  }

	  setLocalValue(cleaned); // Anzeige unverändert
	  return;
	}

	// MEHRERE Werte: nur exakt 10-stellige übernehmen, alle anderen ignorieren
	const validTokens = parts.filter(p => p.length === 10);
	const invalidCount = parts.length - validTokens.length;

	if (validTokens.length > 0) {
	  setAttributes({ orgnr: validTokens.join(',') });
	  // Info anzeigen, falls etwas ignoriert wurde
	  if (invalidCount > 0) {
	    setErrorMessage(
	      __('Some entries are not 10 digits and were ignored.', 'rrze-faudir')
	    );
	  } else {
	    setErrorMessage('');
	  }
	} else {
	  setAttributes({ orgnr: '' });
	  setErrorMessage(
	    __('When entering multiple numbers, each must be exactly 10 digits.', 'rrze-faudir')
	  );
	}

	setLocalValue(cleaned); // Anzeige unverändert (inkl. evtl. trailing Komma)
  };




  return (
    <>
      <Heading level={3}>{__("Select organization by FAUOrg Number", "rrze-faudir")}</Heading>
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