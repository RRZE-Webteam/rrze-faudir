import { __ } from "@wordpress/i18n";
import { SelectControl } from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";
import { EditProps, SettingsRESTApi } from "../faudir/types";
import { useEffect, useMemo, useState } from "@wordpress/element";

interface FormatSelectorProps {
  attributes: EditProps["attributes"];
  setAttributes: EditProps["setAttributes"];
}

export default function FormatSelector({ attributes, setAttributes }: FormatSelectorProps) {
  const { selectedFormat = "", display = "person" } = attributes;

  const [availableFormats, setAvailableFormats] = useState<string[]>([]);
  const [formatTranslation, setFormatTranslation] = useState<Record<string, string>>({});

  useEffect(function() {
    apiFetch({ path: "/wp/v2/settings/rrze_faudir_options" })
      .then(function(data: SettingsRESTApi) {
        const byFormat = data?.avaible_fields_byformat || {};
        const keys = Object.keys(byFormat);

        let formats: string[] = [];

        if (display === "org") {
          formats = keys
            .filter(function(key) {
              return key.startsWith("org-");
            })
            .map(function(key) {
              return key.replace(/^org-/, "");
            });
        } else {
          formats = keys.filter(function(key) {
            return !key.startsWith("org-");
          });
        }

        formats = Array.from(new Set(formats));

        setAvailableFormats(formats);

        if (data?.format_names) {
          setFormatTranslation(data.format_names);
        } else {
          setFormatTranslation({});
        }
      })
      .catch(function(error) {
        console.error("Fehler beim Laden der Formate:", error);
      });
  }, [display]);

  const getFieldLabel = function(format: string): string {
    const key = display === "org" ? "org-" + format : format;
    return formatTranslation[key] || formatTranslation[format] || format;
  };

  const formatOptions = useMemo(function() {
    return availableFormats.map(function(format) {
      return {
        value: format,
        label: getFieldLabel(format),
      };
    });
  }, [availableFormats, formatTranslation, display]);

  const normalizedSelectedFormat = useMemo(function() {
    if (selectedFormat && availableFormats.includes(selectedFormat)) {
      return selectedFormat;
    }

    if (availableFormats.includes("default")) {
      return "default";
    }

    return availableFormats[0] || "";
  }, [selectedFormat, availableFormats]);

  useEffect(function() {
    if (normalizedSelectedFormat !== selectedFormat && normalizedSelectedFormat !== "") {
      setAttributes({ selectedFormat: normalizedSelectedFormat });
    }
  }, [normalizedSelectedFormat, selectedFormat, setAttributes]);

  const handleFormatChange = function(value: string) {
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