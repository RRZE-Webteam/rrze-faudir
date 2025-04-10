import {__} from "@wordpress/i18n";
import {SelectControl} from "@wordpress/components";
import {formatFields} from "../defaults";
import apiFetch, {APIFetchOptions} from '@wordpress/api-fetch';
import {EditProps, SettingsRESTApi} from "../types";

interface FormatSelectorProps {
  attributes: EditProps["attributes"];
  setAttributes: EditProps["setAttributes"];
}

export default function FormatSelector({attributes, setAttributes}: FormatSelectorProps) {
  const {selectedFormat} = attributes;
  const handleFormatChange = (value: string) => {
    setAttributes({selectedFormat: value});

    // Only reset fields if explicitly changing format and no fields are selected
    if (
      !attributes.selectedFields ||
      attributes.selectedFields.length === 0
    ) {
      apiFetch({
        path: '/wp/v2/settings/rrze_faudir_options',
      })
        .then((settings: SettingsRESTApi) => {
          if (settings?.default_output_fields) {
            const formatSpecificFields =
              formatFields[value] || [];
            const filteredDefaultFields =
              settings.default_output_fields.filter((field) =>
                formatSpecificFields.includes(field)
              );
            setAttributes({
              selectedFields: filteredDefaultFields,
            });
          }
        })
        .catch((error) => {
          console.error('Error fetching default fields:', error);
        });
    }
  };

  return (
    <>
      {
        attributes.display !== 'org' && (
          <SelectControl
            label={__('Select Format', 'rrze-faudir')}
            value={selectedFormat || 'list'}
            options={[
              {
                value: 'list',
                label: __('List', 'rrze-faudir'),
              },
              {
                value: 'table',
                label: __('Table', 'rrze-faudir'),
              },
              {
                value: 'card',
                label: __('Card', 'rrze-faudir'),
              },
              {
                value: 'compact',
                label: __('Compact', 'rrze-faudir'),
              },
              {
                value: 'page',
                label: __('Page', 'rrze-faudir'),
              },
            ]}
            onChange={handleFormatChange}
          />
        )
      }
    </>
  );
}