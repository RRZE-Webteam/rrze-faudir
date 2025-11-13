import {
  useBlockProps,
} from "@wordpress/block-editor";
import {EditProps, OrganizationResponseProps, OfficeHour, MediaMetadata } from "./types";
import {
  PanelBody,
  ToggleControl,
  ToolbarGroup,
  ToolbarItem,
  ToolbarButton,
  SVG,
  Path,
  Modal,
  Notice
} from "@wordpress/components";
import {
  InspectorControls,
  BlockControls,
  RichText,
  MediaReplaceFlow
} from "@wordpress/block-editor";
import {useEffect, useMemo, useState, useCallback} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import {__, sprintf} from "@wordpress/i18n";
import OrganizationIdentifierDetector from "../components/OrganizationIdentifierDetector"
import {DataViews} from "@wordpress/dataviews";
import type {View} from "@wordpress/dataviews/build-types";

type ContactData = {
  phone: string;
  mail: string;
  url: string;
  street: string;
  zip: string;
  city: string;
};

type DataViewRow = {
  id: string;
  label: string;
  value: string;
};

const WEEKDAY_LABELS: Record<number, string> = {
  1: __("Monday", "rrze-faudir"),
  2: __("Tuesday", "rrze-faudir"),
  3: __("Wednesday", "rrze-faudir"),
  4: __("Thursday", "rrze-faudir"),
  5: __("Friday", "rrze-faudir"),
  6: __("Saturday", "rrze-faudir"),
  7: __("Sunday", "rrze-faudir"),
};

const emptyContact: ContactData = {
  phone: "",
  mail: "",
  url: "",
  street: "",
  zip: "",
  city: "",
};

const emptyOfficeHours: OfficeHour[] = [];

const DEFAULT_VISIBLE_FIELDS = [
  "name",
  "street",
  "zip",
  "city",
  "phone",
  "mail",
  "url",
  "officeHours",
];

const formatOfficeHour = (entry: OfficeHour): string => {
  if (!entry) {
    return "";
  }
  const weekdayRaw = entry.weekday;
  const weekdayLabel = typeof weekdayRaw === "number"
    ? WEEKDAY_LABELS[weekdayRaw] ?? `${weekdayRaw}`
    : (weekdayRaw ?? "");
  const from = entry.from ?? "";
  const to = entry.to ?? "";
  let timeLabel = "";
  if (from && to) {
    timeLabel = `${from} – ${to}`;
  } else {
    timeLabel = from || to || "";
  }
  return [weekdayLabel, timeLabel].filter(Boolean).join(": ");
};

export default function Edit({attributes, setAttributes}: EditProps) {
  const props = useBlockProps();
  const {
    orgid,
    contact: contactAttr = emptyContact,
    name = "",
    visibleFields: visibleFieldsAttr,
    officeHours: officeHoursAttr = emptyOfficeHours,
  } = attributes;
  const visibleFields = (visibleFieldsAttr && visibleFieldsAttr.length > 0)
    ? visibleFieldsAttr
    : DEFAULT_VISIBLE_FIELDS;
  const [dataView, setDataView] = useState<View>({
    type: "table",
    fields: ["label", "value", "visibility"],
    perPage: 10,
    filters: [],
    page: 1,
    layout: {
      enableMoving: false
    },
  });
  const [modalDataView, setModalDataView] = useState(false);

  useEffect(() => {
    if (!orgid) {
      setAttributes({contact: {...emptyContact}, name: "", officeHours: []});
      return;
    }

    const controller = new AbortController();

    apiFetch<OrganizationResponseProps>({
      path: `/rrze-faudir/v1/organization?orgid=${encodeURIComponent(orgid)}`,
      signal: controller.signal,
    })
      .then((response: OrganizationResponseProps) => {
        console.log(response);
        const address = response?.data?.address ?? {};
        const nextContact: ContactData = {
          phone: address?.phone ?? "",
          mail: address?.mail ?? "",
          url: address?.url ?? "",
          street: address?.street ?? "",
          zip: address?.zip ?? "",
          city: address?.city ?? "",
        };
        const name = response?.data?.name ?? "";
        const nextOfficeHours: OfficeHour[] = Array.isArray(response?.data?.officeHours)
          ? response.data.officeHours.map((entry) => ({
              weekday: entry?.weekday ?? "",
              from: entry?.from ?? "",
              to: entry?.to ?? "",
            }))
          : [];

        setAttributes({contact: nextContact, name: name, officeHours: nextOfficeHours});
      })
      .catch((error) => {
        if (error?.name !== "AbortError") {
          console.error("FAUdir organization request failed", error);
          setAttributes({contact: {...emptyContact}, name: "", officeHours: []});
        }
      });

    return () => controller.abort();
  }, [orgid]);

  const contact = useMemo(() => ({
    ...emptyContact,
    ...contactAttr,
  }), [contactAttr]);

  const {phone, mail, url, street, zip, city} = contact;

  const officeHours = useMemo(() => {
    if (!Array.isArray(officeHoursAttr)) {
      return [] as OfficeHour[];
    }
    return officeHoursAttr.filter((item) => item && (item.weekday || item.from || item.to));
  }, [officeHoursAttr]);

  const formattedOfficeHours = useMemo(() => (
    officeHours.map((entry) => formatOfficeHour(entry)).filter(Boolean)
  ), [officeHours]);

  const toggleFieldVisibility = useCallback((fieldId: string) => {
    const updated = visibleFields.includes(fieldId)
      ? visibleFields.filter((id) => id !== fieldId)
      : [...visibleFields, fieldId];

    setAttributes({visibleFields: updated});
  }, [visibleFields, setAttributes]);

  const isFieldVisible = useCallback((fieldId: string) => {
    return visibleFields.includes(fieldId);
  }, [visibleFields]);

  const dataviewData: DataViewRow[] = useMemo(() => ([
    {id: "name", label: __("Name", "rrze-faudir"), value: name || ""},
    {id: "street", label: __("Street", "rrze-faudir"), value: street || ""},
    {id: "zip", label: __("ZIP", "rrze-faudir"), value: zip || ""},
    {id: "city", label: __("City", "rrze-faudir"), value: city || ""},
    {id: "phone", label: __("Phone", "rrze-faudir"), value: phone || ""},
    {id: "mail", label: __("Email", "rrze-faudir"), value: mail || ""},
    {id: "url", label: __("Website", "rrze-faudir"), value: url || ""},
    {
      id: "officeHours",
      label: __("Office hours", "rrze-faudir"),
      value: formattedOfficeHours.length ? formattedOfficeHours.join("\n") : "",
    },
  ]), [name, street, zip, city, phone, mail, url, formattedOfficeHours]);

  const dataViewFields = useMemo(() => ([
    {
      id: "label",
      label: __("Field", "rrze-faudir"),
      enableHiding: false,
      enableSorting: false,
      getValue: ({item}: { item: DataViewRow }) => item.label,
    },
    {
      id: "value",
      label: __("API value", "rrze-faudir"),
      enableSorting: false,
      enableHiding: false,
      render: ({item}: { item: DataViewRow }) => item.value ? item.value :
        <span className="rrze-faudir__dataviews-empty">{__("No data", "rrze-faudir")}</span>,
    },
    {
      id: "visibility",
      label: __("Display", "rrze-faudir"),
      enableSorting: false,
      enableHiding: false,
      render: ({item}: { item: DataViewRow }) => (
        <ToggleControl
          label={item.label}
          aria-label={sprintf(__("Toggle %s", "rrze-faudir"), item.label)}
          checked={isFieldVisible(item.id)}
          onChange={() => toggleFieldVisibility(item.id)}
        />
      ),
    },
  ]), [isFieldVisible, toggleFieldVisibility]);

  const dataviewPagination = useMemo(() => ({
    totalItems: dataviewData.length,
    totalPages: 1,
  }), [dataviewData.length]);

  const hasAnyContact = ["phone", "mail", "url"].some((fieldId) => isFieldVisible(fieldId) && contact[fieldId as keyof ContactData]);
  const hasAddress = ["street", "zip", "city"].some((fieldId) => isFieldVisible(fieldId) && contact[fieldId as keyof ContactData]);
  const showOfficeHours = isFieldVisible("officeHours");
  const dataIcon = <SVG xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="evenodd"><Path d="M440-240q116 0 198-81.5T720-520q0-116-82-198t-198-82q-117 0-198.5 82T160-520q0 117 81.5 198.5T440-240Zm0-280Zm0 160q-83 0-147.5-44.5T200-520q28-70 92.5-115T440-680q82 0 146.5 45T680-520q-29 71-93.5 115.5T440-360Zm0-60q55 0 101-26.5t72-73.5q-26-46-72-73t-101-27q-56 0-102 27t-72 73q26 47 72 73.5T440-420Zm0-40q25 0 42.5-17t17.5-43q0-25-17.5-42.5T440-580q-26 0-43 17.5T380-520q0 26 17 43t43 17Zm0 300q-75 0-140.5-28.5t-114-77q-48.5-48.5-77-114T80-520q0-74 28.5-139.5t77-114.5q48.5-49 114-77.5T440-880q74 0 139.5 28.5T694-774q49 49 77.5 114.5T800-520q0 64-21 121t-58 104l159 159-57 56-159-158q-47 37-104 57.5T440-160Z"/></SVG>;

  const onSelectMedia = async ( newMedia: MediaMetadata ) => {
    console.log(newMedia);
    const mediaAttributes = attributesFromMedia( newMedia );

    setAttributes({
      imageURL: mediaAttributes.url,
      imageId: mediaAttributes.id ?? null,
      imageWidth: mediaAttributes.width,
      imageHeight: mediaAttributes.height,
    })
  }

  const attributesFromMedia = ( media: MediaMetadata ) => {
    if (!media || ( ! media.url ) ){
      return {
        url: ""
      }
    }

    return {
      url: media.url,
      id: media.id,
      alt: media?.alt,
      height: media.height,
      width: media.width,
    }
  }

  return (
    <>
      <BlockControls>
        <MediaReplaceFlow
          mediaId={ attributes.imageId }
          mediaURL={ attributes.imageURL }
          allowedTypes={ ["image"] }
          accept="image/*"
          onSelect={ onSelectMedia }
          onToggleFeaturedImage={ () => {} }
          name={ ! url ? __( 'Add media' ) : __( 'Replace' ) }
          onReset={ () => {} }
          onError={ () => {} }
        />
        <ToolbarGroup>
          <ToolbarGroup>
            <ToolbarItem>
              {() => (
                <ToolbarButton
                 label={__("Manage Data Visibility", "rrze-faudir")}
                 icon={ dataIcon }
                 onClick={ () => setModalDataView(true) }
                />
              )}
            </ToolbarItem>
          </ToolbarGroup>
        </ToolbarGroup>
      </BlockControls>
      {modalDataView && (
        <Modal size={"large"} onRequestClose={() => setModalDataView(false)}>
          <Notice isDismissible={false} spokenMessage={__("Please be aware, that all data displayed within the service block is in sync with the Portal FAUdir. You cannot change contact details from within your web page.", "rrze-faudir")} status="info">
            {__('The data displayed below is in sync with FAUdir and cannot be changed from within your website. Contact data can only be edited within the FAUdir Portal.',"rrze-faudir")}
          </Notice>
          <DataViews
            data={dataviewData}
            fields={dataViewFields}
            view={dataView}
            onChangeView={() => {
            }}
            paginationInfo={dataviewPagination}
            defaultLayouts={{table: {showMedia: false}}}
            getItemId={(item: DataViewRow) => item.id}
            empty={<p>{__("No API data fetched yet.", "rrze-faudir")}</p>}
            search={false}
          >
            <DataViews.Layout/>
          </DataViews>
        </Modal>
      )}
      <InspectorControls>
        <PanelBody title={__("Organization", "rrze-faudir")} initialOpen={true}>
          <OrganizationIdentifierDetector
            attributes={attributes}
            setAttributes={setAttributes}
          />
        </PanelBody>
      </InspectorControls>
      <article
        {...props}
        className={`rrze-elements-blocks_service_card ${props.className ?? ''}`}
        aria-labelledby="service-title"
      >
        <figure className="rrze-elements-blocks_service__figure">
          <img className="rrze-elements-blocks_service__image"
               src={ attributes.imageURL } width={ attributes.imageWidth } alt="" height={ attributes.imageHeight}/>
        </figure>

        {name && isFieldVisible("name") && (
          <header className="rrze-elements-blocks_service__meta_headline">
            <h2 id="service-title" className="meta-headline">{name}</h2>
            <RichText value={attributes.displayText} tagName={"p"} placeholder={__("Add your service description...", "rrze-faudir")} onChange={(newText) => setAttributes({displayText: newText})}/>
          </header>
        )}

        {hasAddress && (
          <section className="rrze-elements-blocks_service__information" aria-labelledby="addr-h">
            <h3 id="addr-h">Adresse</h3>
            <address>
              {street && isFieldVisible("street") && <span>{street}<br/></span>}
              {(zip || city) && (
                <span>{[
                  isFieldVisible("zip") ? zip : null,
                  isFieldVisible("city") ? city : null,
                ].filter(Boolean).join(' ')}
                </span>
              )}
            </address>
          </section>
        )}

        {(showOfficeHours && formattedOfficeHours.length !== 0 )&& (
          <section aria-labelledby="hours-h">
            <h3 id="hours-h">{__("Office hours", "rrze-faudir")}</h3>
              <ul>
                {formattedOfficeHours.map((entry, index) => (
                  <li key={`office-hour-${index}`}>
                    {entry}
                  </li>
                ))}
              </ul>
          </section>
        )}
        {hasAnyContact && (
          <>
            <section aria-labelledby="contact-h">
              <h3 id="contact-h">{__("Contact", "rrze-faudir")}</h3>
              <address>
                {phone && isFieldVisible("phone") && (
                  <p>
                    <a href={`tel:${phone.replace(/\s+/g, '')}`}>
                      {phone}
                    </a>
                  </p>
                )}
                {mail && isFieldVisible("mail") && (
                  <p>
                    <a href={`mailto:${mail}`}>
                      {mail}
                    </a>
                  </p>
                )}
                {url && isFieldVisible("url") && (
                  <p>
                    <a href={url} target="_blank" rel="noreferrer">
                      {url}
                    </a>
                  </p>
                )}
              </address>
            </section>
          </>
        )}
      </article>
    </>
  );
}
