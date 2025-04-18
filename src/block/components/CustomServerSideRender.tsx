import ServerSideRender from "@wordpress/server-side-render";
import {useEffect, useState} from "@wordpress/element";
import {EditProps} from "../types";
import LoadingPlaceholder from "./LoadingPlaceholder";
import EmptyPlaceholder from "./EmptyPlaceholder";

interface CustomServerSideRenderProps {
  attributes: EditProps['attributes'];
}

export default function CustomServerSideRender({attributes}: CustomServerSideRenderProps) {
  const [componentKey, setComponentKey] = useState(0);

  useEffect(() => {
    setComponentKey((prevKey) => prevKey + 1);
  }, [attributes.orgnr, attributes.selectedCategory, attributes.orgid, attributes.display]);

  return (
    <ServerSideRender
      key={componentKey}
      block="rrze-faudir/block"
      attributes={{
        role: attributes.role,
        orgnr: attributes.orgnr,
        orgid: attributes.orgid,
        selectedFormat: attributes.selectedFormat,
        selectedFields: attributes.selectedFields,
        selectedCategory: attributes.selectedCategory,
        selectedPersonIds: attributes.selectedPersonIds,
        hideFields: attributes.hideFields,
        url: attributes.url,
        sort: attributes.sort,
        format_displayname: attributes.format_displayname,
        display: attributes.display,
        identifier: attributes.identifier,
      }}
      LoadingResponsePlaceholder={LoadingPlaceholder}
      EmptyResponsePlaceholder={EmptyPlaceholder}
    />
  );
}