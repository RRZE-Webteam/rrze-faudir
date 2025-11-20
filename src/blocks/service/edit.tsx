import {
  useBlockProps,
} from "@wordpress/block-editor";
import {EditProps, OrganizationResponseProps, OfficeHour} from "./types";
import {
  PanelBody,
  ToolbarGroup,
  ToolbarItem,
  ToolbarButton,
  SVG,
  Path,
  Modal,
  Notice,
  Button,
  Placeholder
} from "@wordpress/components";
import {
  InspectorControls,
  BlockControls,
  RichText,
} from "@wordpress/block-editor";
import {useEffect, useMemo, useState, useCallback} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import {__} from "@wordpress/i18n";
import OrganizationIdentifierDetector from "../components/OrganizationIdentifierDetector"
import ImageSelector from "../components/ImageSelector"
import ServiceDataView, {ServiceDataRow} from "../components/ServiceDataView";

type ContactData = {
  phone: string;
  mail: string;
  url: string;
  street: string;
  zip: string;
  city: string;
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
    orgid = "",
    visibleFields: visibleFieldsAttr,
    imageId = undefined,
    imageURL = "",
    imageWidth = 0,
    imageHeight = 0,
    displayText = "",
  } = attributes;
  const visibleFields = (visibleFieldsAttr && visibleFieldsAttr.length > 0)
    ? visibleFieldsAttr
    : DEFAULT_VISIBLE_FIELDS;
  const [modalDataView, setModalDataView] = useState(false);
  const [organizationName, setOrganizationName] = useState<string>("");
  const [contact, setContact] = useState<ContactData>({...emptyContact});
  const [officeHours, setOfficeHours] = useState<OfficeHour[]>([]);
  const resetOrgData = useCallback(() => {
    setOrganizationName("");
    setContact({...emptyContact});
    setOfficeHours([]);
  }, []);

  useEffect(() => {
    resetOrgData();

    if (!orgid) {
      return;
    }

    const controller = new AbortController();

    apiFetch<OrganizationResponseProps>({
      path: `/rrze-faudir/v1/organization?orgid=${encodeURIComponent(orgid)}`,
      signal: controller.signal,
    })
      .then((response: OrganizationResponseProps) => {
        const address = response?.data?.address ?? {};
        const nextContact: ContactData = {
          phone: address?.phone ?? "",
          mail: address?.mail ?? "",
          url: address?.url ?? "",
          street: address?.street ?? "",
          zip: address?.zip ?? "",
          city: address?.city ?? "",
        };
        const resolvedName = response?.data?.name ?? "";
        const nextOfficeHours: OfficeHour[] = Array.isArray(response?.data?.officeHours)
          ? response.data.officeHours.map((entry) => ({
            weekday: entry?.weekday ?? "",
            from: entry?.from ?? "",
            to: entry?.to ?? "",
          }))
          : [];

        setContact(nextContact);
        setOrganizationName(resolvedName);
        setOfficeHours(nextOfficeHours);
      })
      .catch((error) => {
        if (error?.name !== "AbortError") {
          console.error("FAUdir organization request failed", error);
          resetOrgData();
        }
      });

    return () => controller.abort();
  }, [orgid, resetOrgData]);

  const {phone, mail, url, street, zip, city} = contact;

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

  const dataviewData: ServiceDataRow[] = useMemo(() => ([
    {id: "name", label: __("Name", "rrze-faudir"), value: organizationName || ""},
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
  ]), [organizationName, street, zip, city, phone, mail, url, formattedOfficeHours]);

  const hasAnyContact = ["phone", "mail", "url"].some((fieldId) => isFieldVisible(fieldId) && contact[fieldId as keyof ContactData]);
  const hasAddress = ["street", "zip", "city"].some((fieldId) => isFieldVisible(fieldId) && contact[fieldId as keyof ContactData]);
  const showOfficeHours = isFieldVisible("officeHours");
  const dataIcon = <SVG xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="evenodd"><Path
    d="M440-240q116 0 198-81.5T720-520q0-116-82-198t-198-82q-117 0-198.5 82T160-520q0 117 81.5 198.5T440-240Zm0-280Zm0 160q-83 0-147.5-44.5T200-520q28-70 92.5-115T440-680q82 0 146.5 45T680-520q-29 71-93.5 115.5T440-360Zm0-60q55 0 101-26.5t72-73.5q-26-46-72-73t-101-27q-56 0-102 27t-72 73q26 47 72 73.5T440-420Zm0-40q25 0 42.5-17t17.5-43q0-25-17.5-42.5T440-580q-26 0-43 17.5T380-520q0 26 17 43t43 17Zm0 300q-75 0-140.5-28.5t-114-77q-48.5-48.5-77-114T80-520q0-74 28.5-139.5t77-114.5q48.5-49 114-77.5T440-880q74 0 139.5 28.5T694-774q49 49 77.5 114.5T800-520q0 64-21 121t-58 104l159 159-57 56-159-158q-47 37-104 57.5T440-160Z"/></SVG>;

  return (
    <>
      { orgid ? (
        <>
          <BlockControls>
            <ImageSelector mediaId={imageId} mediaURL={imageURL} mediaWidth={imageWidth} mediaHeight={imageHeight}
                           setAttributes={setAttributes}/>
            <ToolbarGroup>
              <ToolbarGroup>
                <ToolbarItem>
                  {() => (
                    <ToolbarButton
                      label={__("Manage Data Visibility", "rrze-faudir")}
                      icon={dataIcon}
                      onClick={() => setModalDataView(true)}
                    />
                  )}
                </ToolbarItem>
              </ToolbarGroup>
            </ToolbarGroup>
          </BlockControls>
          {modalDataView && (
            <Modal size={"large"} onRequestClose={() => setModalDataView(false)}>
              <Notice isDismissible={false}
                      spokenMessage={__("Please be aware, that all data displayed within the service block is in sync with the Portal FAUdir. You cannot change contact details from within your web page.", "rrze-faudir")}
                      status="info">
                {__('The data displayed below is in sync with FAUdir and cannot be changed from within your website. Contact data can only be edited within the FAUdir Portal.', "rrze-faudir")}
              </Notice>
              <ServiceDataView
                data={dataviewData}
                visibleFields={visibleFields}
                onToggleField={toggleFieldVisibility}
                search={false}
              />
            </Modal>
          )}
          <InspectorControls>
            <PanelBody title={__("Organization", "rrze-faudir")} initialOpen={true}>
              <OrganizationIdentifierDetector
                attributes={attributes}
                setAttributes={setAttributes}
              />
            </PanelBody>
            <PanelBody title={__("Available data", "rrze-faudir")} initialOpen={false}>
              <Button
                variant="tertiary"
                onClick={() => setModalDataView(true)}
                disabled={modalDataView}
              >
                {__("Manage Data View", "rrze-faudir")}
              </Button>
            </PanelBody>
          </InspectorControls>
          <article
            {...props}
            className={`rrze-elements-blocks_service_card ${props.className ?? ''}`}
            aria-labelledby="service-title"
          >
            <figure className="rrze-elements-blocks_service__figure">
              <img className="rrze-elements-blocks_service__image"
                   src={imageURL} width={imageWidth} alt="" height={imageHeight}/>
            </figure>

            {organizationName && isFieldVisible("name") && (
              <header className="rrze-elements-blocks_service__meta_headline">
                <h2 id="service-title" className="meta-headline">{organizationName}</h2>
                <RichText value={displayText} tagName={"p"}
                          placeholder={__("Add your service description...", "rrze-faudir")}
                          onChange={(newText) => setAttributes({displayText: newText})}/>
              </header>
            )}

            {hasAddress && (
              <section className="rrze-elements-blocks_service__information" aria-labelledby="addr-h">
                <h3 id="addr-h">{__("Adresse", "rrze-faudir")}</h3>
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

            {(showOfficeHours && formattedOfficeHours.length !== 0) && (
              <section aria-labelledby="hours-h">
                <h3 id="hours-h">{__("Office hours", "rrze-faudir")}</h3>
                <ul className="list-icons">
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
        ):(
      <Placeholder
        label={__("FAUdir Service-Block", "rrze-faudir")}
        icon={<SVG xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                   fill="evenodd"><Path
          d="M440-120v-80h320v-284q0-117-81.5-198.5T480-764q-117 0-198.5 81.5T200-484v244h-40q-33 0-56.5-23.5T80-320v-80q0-21 10.5-39.5T120-469l3-53q8-68 39.5-126t79-101q47.5-43 109-67T480-840q68 0 129 24t109 66.5Q766-707 797-649t40 126l3 52q19 9 29.5 27t10.5 38v92q0 20-10.5 38T840-249v49q0 33-23.5 56.5T760-120H440Zm-80-280q-17 0-28.5-11.5T320-440q0-17 11.5-28.5T360-480q17 0 28.5 11.5T400-440q0 17-11.5 28.5T360-400Zm240 0q-17 0-28.5-11.5T560-440q0-17 11.5-28.5T600-480q17 0 28.5 11.5T640-440q0 17-11.5 28.5T600-400Zm-359-62q-7-106 64-182t177-76q89 0 156.5 56.5T720-519q-91-1-167.5-49T435-698q-16 80-67.5 142.5T241-462Z"/></SVG>}
        instructions={__("Insert your FAUdir Folder/Org Id to display service information.", "rrze-faudir")}
      >
        <OrganizationIdentifierDetector attributes={attributes} setAttributes={setAttributes} />
      </Placeholder>
    )}
    </>
  );
}
