import ServerSideRender from "@wordpress/server-side-render";
import {useEffect, useState} from "@wordpress/element";
import {EditProps} from "../faudir/types";
import LoadingPlaceholder from "./LoadingPlaceholder";
import EmptyPlaceholder from "./EmptyPlaceholder";

interface CustomServerSideRenderProps {
  attributes: EditProps['attributes'];
}

export default function CustomServerSideRender({attributes}: CustomServerSideRenderProps) {
  const [componentKey, setComponentKey] = useState(0);

  useEffect(function () {
    setComponentKey(function (prevKey) {
      return prevKey + 1;
    });
  }, [
    attributes.role,
    attributes.orgnr,
    attributes.orgid,
    attributes.selectedFormat,
    attributes.selectedFields,
    attributes.selectedCategory,
    attributes.selectedPosts,
    attributes.selectedPersonIds,
    attributes.url,
    attributes.sort,
    attributes.order,
    attributes.format_displayname,
    attributes.display,
    attributes.identifier,
  ]);

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
        selectedPosts: attributes.selectedPosts,
        selectedPersonIds: attributes.selectedPersonIds,
        url: attributes.url,
        sort: attributes.sort,
        order: attributes.order,
        format_displayname: attributes.format_displayname,
        display: attributes.display,
        identifier: attributes.identifier,
      }}
      LoadingResponsePlaceholder={LoadingPlaceholder}
      EmptyResponsePlaceholder={EmptyPlaceholder}
    />
  );
}