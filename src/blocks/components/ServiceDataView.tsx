import { useMemo, useState } from "@wordpress/element";
import { TextControl, ToggleControl } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";

export type ServiceDataRow = {
  id: string;
  label: string;
  value: string;
};

type ServiceDataViewProps = {
  data: ServiceDataRow[];
  visibleFields: string[];
  onToggleField: (fieldId: string) => void;
  search?: boolean;
  emptyMessage?: string;
};

export default function ServiceDataView({
  data,
  visibleFields,
  onToggleField,
  search = false,
  emptyMessage,
}: ServiceDataViewProps) {
  const [query, setQuery] = useState("");

  const normalizedQuery = query.trim().toLowerCase();

  const filteredData = useMemo(function() {
    if (!search || normalizedQuery === "") {
      return data;
    }

    return data.filter(function(item) {
      const haystack = [item.label, item.value]
        .join(" ")
        .toLowerCase();

      return haystack.includes(normalizedQuery);
    });
  }, [data, normalizedQuery, search]);

  return (
    <div className="rrze-faudir-service-data-view">
      {search && (
        <TextControl
          __next40pxDefaultSize
          label={__("Search fields", "rrze-faudir")}
          value={query}
          onChange={setQuery}
        />
      )}

      {filteredData.length === 0 && (
        <p>{emptyMessage ?? __("No API data fetched yet.", "rrze-faudir")}</p>
      )}

      {filteredData.length > 0 && (
        <table className="widefat striped">
          <thead>
            <tr>
              <th scope="col">{__("Field", "rrze-faudir")}</th>
              <th scope="col">{__("API value", "rrze-faudir")}</th>
              <th scope="col">{__("Display", "rrze-faudir")}</th>
            </tr>
          </thead>
          <tbody>
            {filteredData.map(function(item) {
              return (
                <tr key={item.id}>
                  <td>{item.label}</td>
                  <td>
                    {item.value ? (
                      item.value
                    ) : (
                      <span className="rrze-faudir__dataviews-empty">
                        {__("No data", "rrze-faudir")}
                      </span>
                    )}
                  </td>
                  <td>
                    <ToggleControl
                      label={item.label}
                      aria-label={sprintf(__("Toggle %s", "rrze-faudir"), item.label)}
                      checked={visibleFields.includes(item.id)}
                      onChange={function() {
                        onToggleField(item.id);
                      }}
                    />
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      )}
    </div>
  );
}
