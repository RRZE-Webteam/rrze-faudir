import { useState, useEffect } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { EditProps, SettingsRESTApi } from "../faudir/types";

interface ShowHideSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
  setHasFormatDisplayName: (hasDisplayName: boolean) => void;
}

const arraysEqual = (a: string[], b: string[]) =>
  a.length === b.length && a.every((v, i) => v === b[i]);

export default function ShowHideSelector({
  attributes,
  setAttributes,
  setHasFormatDisplayName
}: ShowHideSelectorProps) {

  const { selectedFormat = 'default', selectedFields = [], display } = attributes;

  const [availableFields, setAvailableFields] = useState<string[]>([]);
  const [shownFields, setShownFields] = useState<string[]>(selectedFields);
  const [translatableFields, setTranslatableFields] = useState<Record<string, string>>({});

  useEffect(() => {
    let mounted = true;

    apiFetch({ path: '/wp/v2/settings/rrze_faudir_options' })
      .then((data: SettingsRESTApi) => {
        if (!mounted) return;

        var formatKey = selectedFormat || 'default';

        if (display === 'org') {
          formatKey = formatKey.startsWith('org-')
            ? formatKey
            : `org-${formatKey}`;
        } else {
          formatKey = formatKey.replace(/^org-/, '');
        }

        var fieldsForFormat =
          data?.avaible_fields_byformat?.[formatKey] || [];

        setAvailableFields(fieldsForFormat);

        setHasFormatDisplayName(
          fieldsForFormat.includes('format_displayname')
        );

        if (display === 'org') {
          setTranslatableFields(data.available_fields_org || {});
        } else {
          setTranslatableFields(data.available_fields || {});
        }
      })
      .catch((err) =>
        console.error('Fehler beim Laden der Felder:', err)
      );

    return () => { mounted = false; };

  }, [selectedFormat, display, setHasFormatDisplayName]);


  useEffect(() => {
    const src = Array.isArray(attributes.selectedFields)
      ? attributes.selectedFields
      : [];

    const next = availableFields.length
      ? src.filter(f => availableFields.includes(f))
      : src;

    if (!arraysEqual(next, shownFields)) {
      setShownFields(next);
    }

  }, [attributes.selectedFields, availableFields]);


  useEffect(() => {
    const current = Array.isArray(attributes.selectedFields)
      ? attributes.selectedFields
      : [];

    if (!arraysEqual(shownFields, current)) {
      setAttributes({ selectedFields: shownFields });
    }

  }, [shownFields]);


  const handleToggleField = (field: string) => {
    if (!availableFields.includes(field)) return;

    setShownFields(prev =>
      prev.includes(field)
        ? prev.filter(f => f !== field)
        : [...prev, field]
    );
  };


  const isChecked = (field: string) => shownFields.includes(field);

  const getFieldLabel = (field: string) =>
    translatableFields[field] || field;

  const fieldsToDisplay =
    availableFields.filter(f => f !== 'format_displayname');


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