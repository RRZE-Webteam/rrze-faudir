import { useState, useEffect } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { EditProps, SettingsRESTApi } from '../types';

interface ShowHideSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
  setHasFormatDisplayName: (hasDisplayName: boolean) => void;
}


// Helper: baut den effektiven Format-Key
const normalizeFormatKey = (fmt: string, display?: string) => {
  if (!fmt) return '';
  if (display === 'org') {
    return fmt.startsWith('org-') ? fmt : `org-${fmt}`;
  }
  // Sicherstellen, dass im "person"-Modus kein org- dran bleibt
  return fmt.replace(/^org-/, '');
};

// flacher Array-Vergleich
const arraysEqual = (a: string[], b: string[]) =>
  a.length === b.length && a.every((v, i) => v === b[i]);

export default function ShowHideSelector({
  attributes,
  setAttributes,
  setHasFormatDisplayName
}: ShowHideSelectorProps) {
  const { selectedFormat, selectedFields = [], display } = attributes;

  const [availableFields, setAvailableFields] = useState<string[]>([]);
  const [shownFields, setShownFields] = useState<string[]>(selectedFields);
  const [translatableFields, setTranslatableFields] = useState<Record<string, string>>({});

  // Felder + Labels laden
  useEffect(() => {
    let mounted = true;
    const formatKey = normalizeFormatKey(selectedFormat || '', attributes.display);
   
    apiFetch({ path: '/wp/v2/settings/rrze_faudir_options' })
      .then((data: SettingsRESTApi) => {
        if (!mounted) return;

        const fieldsForFormat = data?.avaible_fields_byformat?.[formatKey] || [];
        setAvailableFields(fieldsForFormat);
        setHasFormatDisplayName(fieldsForFormat.includes('format_displayname'));

        if (data?.available_fields) {
          setTranslatableFields(data.available_fields);
        }
      })
      .catch((err) => console.error('Fehler beim Laden der Felder:', err));
    return () => { mounted = false; };
  }, [selectedFormat, display, setHasFormatDisplayName]);

  // WICHTIG: Sync shownFields, sobald entweder selectedFields ODER availableFields sich ändern
  useEffect(() => {
    const src = Array.isArray(attributes.selectedFields) ? attributes.selectedFields : [];
    // Wenn availableFields noch leer, nimm src (zeigt initiale Auswahl an);
    // sonst: Intersection, damit nur erlaubte Felder markiert sind.
    const next = availableFields.length ? src.filter(f => availableFields.includes(f)) : src;

    if (!arraysEqual(next, shownFields)) {
      setShownFields(next);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [attributes.selectedFields, availableFields]);

  // shownFields zurück in die Block-Attribute schreiben (eine Quelle der Wahrheit)
  useEffect(() => {
    const current = Array.isArray(attributes.selectedFields) ? attributes.selectedFields : [];
    if (!arraysEqual(shownFields, current)) {
      setAttributes({ selectedFields: shownFields });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [shownFields]);

  const handleToggleField = (field: string) => {
    if (!availableFields.includes(field)) return;
    setShownFields(prev =>
      prev.includes(field) ? prev.filter(f => f !== field) : [...prev, field]
    );
  };

  const isChecked = (field: string) => shownFields.includes(field);
  const getFieldLabel = (field: string) => translatableFields[field] || field;
  const fieldsToDisplay = availableFields.filter(f => f !== 'format_displayname');

  return (
    <div>
      <h4>{__('Select fields', 'rrze-faudir')}</h4>
      {fieldsToDisplay.map((field) => (
        <CheckboxControl
          key={field}
          label={getFieldLabel(field)}
          checked={isChecked(field)}
          onChange={() => handleToggleField(field)}
        />
      ))}
    </div>
  );
}
