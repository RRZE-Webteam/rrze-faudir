import {useState, useEffect} from '@wordpress/element';
import {CheckboxControl} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {EditProps} from '../types';
import {SettingsRESTApi} from "../types";

interface ShowHideSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
  setHasFormatDisplayName: (hasDisplayName: boolean) => void;
}

export default function ShowHideSelector({
                                           attributes,
                                           setAttributes,
                                           setHasFormatDisplayName
                                         }: ShowHideSelectorProps) {
  const {selectedFormat, hideFields, selectedFields} = attributes;

  const [defaultFields, setDefaultFields] = useState<string[]>([]);
  const [availableFields, setAvailableFields] = useState<string[]>([]);
  const [hiddenFields, setHiddenFields] = useState<string[]>(hideFields || []);
  const [shownFields, setShownFields] = useState<string[]>(selectedFields || []);
  const [translatableFields, setTranslatableFields] = useState<Record<string, string>>({});

  useEffect(() => {
    apiFetch({path: '/wp/v2/settings/rrze_faudir_options'})
      .then((data: SettingsRESTApi) => {
        const fieldsForFormat = data.avaible_fields_byformat[selectedFormat] || [];
        setHasFormatDisplayName(fieldsForFormat.includes("format_displayname"));

        // Pull the default option fields from wp-options
        if (data?.default_output_fields) {
          setDefaultFields(data.default_output_fields);
        }
        // Pull the available Fields per Format
        if (data?.avaible_fields_byformat && selectedFormat) {
          const fieldsForFormat = data.avaible_fields_byformat[selectedFormat] || [];
          setAvailableFields(fieldsForFormat);
        }
        // Pull the translation for the labels
        if (data?.available_fields) {
          setTranslatableFields(data.available_fields);
        }
      })
      .catch((error) => {
        console.error('Fehler beim Laden der Felder:', error);
      });
  }, [selectedFormat, attributes.display]);

  useEffect(() => {
    setAttributes({
      hideFields: hiddenFields,
      selectedFields: shownFields,
    });
  }, [hiddenFields, shownFields, selectedFormat, attributes.display]);

  const handleToggleField = (field: string) => {
    if (defaultFields.includes(field)) {
      if (hiddenFields.includes(field)) {
        setHiddenFields(hiddenFields.filter((f) => f !== field));
      } else {
        setHiddenFields([...hiddenFields, field]);
      }
    } else {
      if (shownFields.includes(field)) {
        setShownFields(shownFields.filter((f) => f !== field));
      } else {
        setShownFields([...shownFields, field]);
      }
    }
  };

  const isChecked = (field: string) => {
    if (defaultFields.includes(field)) {
      return !hiddenFields.includes(field);
    }
    return shownFields.includes(field);
  };

  const getFieldLabel = (field: string) => {
    return translatableFields[field] || field;
  };

  const fieldsToDisplay = availableFields.filter((field) => field !== 'format_displayname');

  return (
    <div>
      <h4>{__('Felder auswählen', 'rrze-faudir')}</h4>
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