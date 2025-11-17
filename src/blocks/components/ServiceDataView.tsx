import { useState, useMemo, useCallback } from "@wordpress/element";
import { ToggleControl } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { DataViews } from "@wordpress/dataviews";
import type { View } from "@wordpress/dataviews/build-types";

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
  const [view, setView] = useState<View>({
    type: "table",
    fields: ["label", "value", "visibility"],
    perPage: 10,
    page: 1,
    filters: [],
    layout: {
      enableMoving: false,
    },
  });

  const isFieldVisible = useCallback(
    (fieldId: string) => visibleFields.includes(fieldId),
    [visibleFields]
  );

  const fields = useMemo(
    () => [
      {
        id: "label",
        label: __("Field", "rrze-faudir"),
        enableHiding: false,
        enableSorting: false,
        getValue: ({ item }: { item: ServiceDataRow }) => item.label,
      },
      {
        id: "value",
        label: __("API value", "rrze-faudir"),
        enableSorting: false,
        enableHiding: false,
        render: ({ item }: { item: ServiceDataRow }) =>
          item.value ? (
            item.value
          ) : (
            <span className="rrze-faudir__dataviews-empty">
              {__("No data", "rrze-faudir")}
            </span>
          ),
      },
      {
        id: "visibility",
        label: __("Display", "rrze-faudir"),
        enableSorting: false,
        enableHiding: false,
        render: ({ item }: { item: ServiceDataRow }) => (
          <ToggleControl
            label={item.label}
            aria-label={sprintf(__("Toggle %s", "rrze-faudir"), item.label)}
            checked={isFieldVisible(item.id)}
            onChange={() => onToggleField(item.id)}
          />
        ),
      },
    ],
    [isFieldVisible, onToggleField]
  );

  const paginationInfo = useMemo(
    () => ({
      totalItems: data.length,
      totalPages: 1,
    }),
    [data.length]
  );

  return (
    <DataViews
      data={data}
      fields={fields}
      view={view}
      onChangeView={setView}
      paginationInfo={paginationInfo}
      defaultLayouts={{ table: { showMedia: false } }}
      getItemId={(item: ServiceDataRow) => item.id}
      empty={<p>{emptyMessage ?? __("No API data fetched yet.", "rrze-faudir")}</p>}
      search={search}
    >
      <DataViews.Layout />
    </DataViews>
  );
}
