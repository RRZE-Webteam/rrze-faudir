import ServerSideRender from "@wordpress/server-side-render";


export default function CustomServerSideRender({attributes, debouncedRenderKey}: any) {
  return (
    <ServerSideRender
      key={debouncedRenderKey}
      block="rrze-faudir/block"
      attributes={{
        // Case 1: function + orgnr
        ...(attributes.role
            ? {
              role: attributes.role,
              ...(attributes.orgnr && {
                orgnr: attributes.orgnr,
              }),
              selectedFormat:
              attributes.selectedFormat,
              selectedFields:
                attributes.selectedFields
                  .length > 0
                  ? attributes.selectedFields
                  : null, // Only pass if fields are selected
              hideFields: attributes.hideFields, // Add hideFields
              url: attributes.url,
              sort: attributes.sort,
              format_displayname: attributes.format_displayname,
            }
            : // Case 2: category
            attributes.selectedCategory
              ? {
                selectedCategory:
                attributes.selectedCategory,
                selectedPersonIds:
                attributes.selectedPersonIds,
                selectedFormat:
                attributes.selectedFormat,
                selectedFields:
                  attributes.selectedFields
                    .length > 0
                    ? attributes.selectedFields
                    : null, // Only pass if fields are selected
                hideFields: attributes.hideFields, // Add hideFields
                url: attributes.url,
                sort: attributes.sort,
                format_displayname: attributes.format_displayname,
              }
              : // Case 3: selectedPersonIds
              attributes.selectedPersonIds?.length > 0
                ? {
                  selectedPersonIds:
                  attributes.selectedPersonIds,
                  selectedFields:
                    attributes.selectedFields
                      .length > 0
                      ? attributes.selectedFields
                      : null, // Only pass if fields are selected
                  hideFields: attributes.hideFields, // Add hideFields
                  selectedFormat:
                  attributes.selectedFormat,
                  url: attributes.url,
                  sort: attributes.sort,
                  format_displayname: attributes.format_displayname,
                }
                : // Case 4: orgnr als eigenständiger Fall
                attributes.orgnr
                  ? {
                    orgnr: attributes.orgnr,
                    selectedFormat: attributes.selectedFormat,
                    selectedFields: attributes.selectedFields.length > 0 ? attributes.selectedFields : null,
                    hideFields: attributes.hideFields,
                    url: attributes.url,
                    sort: attributes.sort,
                    format_displayname: attributes.format_displayname,
                  }
                  : // Falls keiner der Fälle greift, leere Attribute übergeben
                  {
                    selectedFormat: attributes.selectedFormat,
                    selectedFields: attributes.selectedFields.length > 0 ? attributes.selectedFields : null,
                    hideFields: attributes.hideFields,
                    url: attributes.url,
                    sort: attributes.sort,
                    format_displayname: attributes.format_displayname,
                  }

        ),
      }}
    />
  );
}