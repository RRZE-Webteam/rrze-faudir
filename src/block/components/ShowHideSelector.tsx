import {availableFields, formatFields} from "../defaults";
import {__} from "@wordpress/i18n";
import {CheckboxControl} from "@wordpress/components";
import {EditProps} from "../edit";

interface ShowHideSelectorProps {
  attributes: EditProps["attributes"];
  setAttributes: EditProps["setAttributes"];
}

export default function ShowHideSelector({attributes, setAttributes}: ShowHideSelectorProps) {
  const {selectedFormat, selectedFields} = attributes;

  const toggleFieldSelection = (field: string) => {
    const isFieldSelected = selectedFields.includes(field);
    let updatedSelectedFields;
    let updatedHideFields = attributes.hideFields || [];

    // Define name-related fields
    const nameFields = [
      'personalTitle',
      'givenName',
      'familyName',
      'honorificSuffix',
      'titleOfNobility',
    ];

    if (field === 'displayName') {
      if (isFieldSelected) {
        // If unchecking displayName, remove it and all name-related fields
        updatedSelectedFields = selectedFields.filter(
          (f) => !nameFields.includes(f) && f !== 'displayName'
        );
        updatedHideFields = [
          ...updatedHideFields,
          'displayName',
          ...nameFields,
        ];
      } else {
        // If checking displayName, add it and all name-related fields
        updatedSelectedFields = [
          ...selectedFields.filter(
            (f) => !nameFields.includes(f)
          ),
          'displayName',
          ...nameFields,
        ];
        updatedHideFields = updatedHideFields.filter(
          (f) => !nameFields.includes(f) && f !== 'displayName'
        );
      }
    } else if (nameFields.includes(field)) {
      if (isFieldSelected) {
        // If unchecking a name field, remove it and displayName
        updatedSelectedFields = selectedFields.filter(
          (f) => f !== field && f !== 'displayName'
        );
        updatedHideFields = [...updatedHideFields, field];
      } else {
        // If checking a name field, add just that field
        updatedSelectedFields = [
          ...selectedFields.filter((f) => f !== 'displayName'),
          field,
        ];
        updatedHideFields = updatedHideFields.filter(
          (f) => f !== field
        );
      }
    } else {
      // Handle non-name fields as before
      if (isFieldSelected) {
        updatedSelectedFields = selectedFields.filter(
          (f) => f !== field
        );
        updatedHideFields = [...updatedHideFields, field];
      } else {
        updatedSelectedFields = [...selectedFields, field];
        updatedHideFields = updatedHideFields.filter(
          (f) => f !== field
        );
      }
    }

    setAttributes({
      selectedFields: updatedSelectedFields,
      hideFields: updatedHideFields,
    });
  };

  return (
    <>
      {Object.keys(formatFields).map((format) => {
        if (selectedFormat === format) {
          return (
            <div key={format}>
              <h4>
                {__('Select Fields', 'rrze-faudir')}
              </h4>
              {formatFields[format].map((field) => (
                <div
                  key={field}
                  style={{marginBottom: '8px'}}
                >
                  <CheckboxControl
                    label={String(availableFields[field])}
                    checked={selectedFields.includes(field)}
                    onChange={() => toggleFieldSelection(field)}
                  />
                </div>
              ))}
            </div>
          );
        }
        return null;
      })
      }
    </>
  );
};