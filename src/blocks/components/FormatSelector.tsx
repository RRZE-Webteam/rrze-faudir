import { __ } from "@wordpress/i18n";
import { SelectControl } from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";
import { EditProps, SettingsRESTApi } from "../faudir/types";
import { useEffect, useMemo, useState } from "@wordpress/element";

interface FormatSelectorProps {
  attributes: EditProps["attributes"];
  setAttributes: EditProps["setAttributes"];
}

export default function FormatSelector({
  attributes,
  setAttributes,
}: FormatSelectorProps) {
  const { selectedFormat = "", display = "person" } = attributes;

  const [availableFormats, setAvailableFormats] = useState<string[]>([]);
  const [formatTranslation, setFormatTranslation] = useState<
    Record<string, string>
  >({});

  useEffect(() => {
    apiFetch<SettingsRESTApi>({ path: "/wp/v2/settings/rrze_faudir_options" })
      .then((data) => {
        const byFormat = data?.avaible_fields_byformat || {};
        const keys = Object.keys(byFormat);

        const formats =
          display === "org"
            ? keys
                .filter((key) => {
                  return key.startsWith("org-");
                })
                .map((key) => {
                  return key.replace(/^org-/, "");
                })
            : keys.filter((key) => {
                return !key.startsWith("org-");
              });

        const uniqueFormats = Array.from(new Set(formats));

        setAvailableFormats(uniqueFormats);

        if (data?.format_names) {
          setFormatTranslation(data.format_names);
        } else {
          setFormatTranslation({});
        }
      })
      .catch((error) => {
        console.error("Fehler beim Laden der Formate:", error);
      });
  }, [display]);

  const getFieldLabel = (format: string): string => {
    const key = display === "org" ? "org-" + format : format;
    return formatTranslation[key] || formatTranslation[format] || format;
  };

  const formatOptions = useMemo(() => {
    return availableFormats.map((format) => {
      return {
        value: format,
        label: getFieldLabel(format),
      };
    });
  }, [availableFormats, formatTranslation, display]);

  const normalizedSelectedFormat = useMemo(() => {
    if (selectedFormat && availableFormats.includes(selectedFormat)) {
      return selectedFormat;
    }

    if (availableFormats.includes("default")) {
      return "default";
    }

    return availableFormats[0] || "";
  }, [selectedFormat, availableFormats]);

  useEffect(() => {
    if (
      normalizedSelectedFormat !== selectedFormat &&
      normalizedSelectedFormat !== ""
    ) {
      setAttributes({ selectedFormat: normalizedSelectedFormat });
    }
  }, [normalizedSelectedFormat, selectedFormat, setAttributes]);

  const handleFormatChange = (value: string) => {
    setAttributes({ selectedFormat: value });
  };

  return (
    <SelectControl
      label={__("Select Format", "rrze-faudir")}
      value={normalizedSelectedFormat}
      options={formatOptions}
      onChange={handleFormatChange}
    />
  );
}
