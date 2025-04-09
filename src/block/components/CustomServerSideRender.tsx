import ServerSideRender from "@wordpress/server-side-render";
import { useEffect, useState } from "@wordpress/element";
import { EditProps } from "../types";

interface CustomServerSideRenderProps {
  attributes: EditProps['attributes'];
}

export default function CustomServerSideRender({attributes}: CustomServerSideRenderProps) {
  const [componentKey, setComponentKey] = useState(0);

  useEffect(() => {
    setComponentKey((prevKey) => prevKey + 1);
  }, [attributes.orgnr, attributes.selectedCategory]);

  return (
    <ServerSideRender
      key={componentKey}
      block="rrze-faudir/block"
      attributes={{
        role: attributes.role,
        orgnr: attributes.orgnr,
        selectedFormat: attributes.selectedFormat,
        selectedFields: attributes.selectedFields,
        selectedCategory: attributes.selectedCategory,
        selectedPersonIds: attributes.selectedPersonIds,
        hideFields: attributes.hideFields,
        url: attributes.url,
        sort: attributes.sort,
        format_displayname: attributes.format_displayname,
      }}
    />
  );
}