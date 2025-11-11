import {__} from "@wordpress/i18n";
import {SelectControl} from "@wordpress/components";
import apiFetch from '@wordpress/api-fetch';
import {EditProps, SettingsRESTApi} from "../faudir/types";
import {useEffect, useState} from "@wordpress/element";

interface FormatSelectorProps {
  attributes: EditProps["attributes"];
  setAttributes: EditProps["setAttributes"];
}

export default function FormatSelector({attributes, setAttributes}: FormatSelectorProps) {
  const {selectedFormat} = attributes;
  const [types, setTypes] = useState<Record<string, string[]>>({});
  const [typeBasedOnDisplayValue, setTypeBasedOnDisplayValue] = useState<string[]>([]);
  const [formatTranslation, setFormatTranslation] = useState<Record<string, string>>({});

  useEffect(() => {
    apiFetch({path: '/wp/v2/settings/rrze_faudir_options'})
      .then((data: SettingsRESTApi) => {
        // Pull the default option fields from wp-options
        if (data?.available_formats_by_display) {
          setTypes(data.available_formats_by_display);
        }
        if (data?.format_names) {
          setFormatTranslation(data.format_names);
        }
      })
      .catch((error) => {
        console.error('Fehler beim Laden der Felder:', error);
      });
  }, [selectedFormat]);

  useEffect(() => {
    const isOrg = attributes.display === 'org';
    const relevantTypeList = (isOrg ? types.org : types.person) ?? [];
    setTypeBasedOnDisplayValue(relevantTypeList);
  }, [attributes.display, types, selectedFormat, setAttributes]);


  const handleFormatChange = (value: string) => {
    setAttributes({selectedFormat: value});
  };

  const getFieldLabel = (field: string) => {
    return formatTranslation[field] || field;
  };

  const defaultFormat = selectedFormat || 'list';
  const formatOptions = (typeBasedOnDisplayValue ?? []).map((field) => ({
    value: field,
    label: getFieldLabel(field),
  }));

  return (
    <>
      <SelectControl
        label={__('Select Format', 'rrze-faudir')}
        value={defaultFormat}
        options={formatOptions}
        onChange={handleFormatChange}
      />
    </>
  );
}