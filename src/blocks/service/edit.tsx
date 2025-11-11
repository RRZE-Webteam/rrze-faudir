import {
  useBlockProps,
} from "@wordpress/block-editor";
import {EditProps, OrganizationResponseProps} from "./types";
import {
  PanelBody,
  ToggleControl,
  Modal
} from "@wordpress/components";
import {
  InspectorControls
} from "@wordpress/block-editor";
import {useEffect, useMemo, useState, useCallback} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import {__, sprintf} from "@wordpress/i18n";
import OrganizationIdentifierDetector from "../components/OrganizationIdentifierDetector"
import { DataViews } from "@wordpress/dataviews";
import type { View } from "@wordpress/dataviews/build-types";

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
];

export default function Edit({attributes, setAttributes}: EditProps) {
  const props = useBlockProps();
  const {
    orgid,
    contact: contactAttr = emptyContact,
    name = "",
    visibleFields: visibleFieldsAttr,
  } = attributes;
  const visibleFields = (visibleFieldsAttr && visibleFieldsAttr.length > 0)
    ? visibleFieldsAttr
    : DEFAULT_VISIBLE_FIELDS;
  const [dataView, setDataView] = useState<View>({
    type: "table",
    fields: ["label", "value", "visibility"],
    sort: {
      field: "label",
      direction: "asc",
    },
    perPage: 10,
    page: 1,
  });
  const [modalDataView, setModalDataView] = useState(false);

  useEffect(() => {
    if (!orgid) {
      setAttributes({contact: {...emptyContact}, name: ""});
      return;
    }

    const controller = new AbortController();

    apiFetch<OrganizationResponseProps>({
      path: `/rrze-faudir/v1/organization?orgid=${encodeURIComponent(orgid)}`,
      signal: controller.signal,
    })
      .then((response: OrganizationResponseProps ) => {
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

        setAttributes({contact: nextContact, name: name});
      })
      .catch((error) => {
        if (error?.name !== "AbortError") {
          console.error("FAUdir organization request failed", error);
          setAttributes({contact: {...emptyContact}, name: ""});
        }
      });

    return () => controller.abort();
  }, [orgid]);

  const contact = useMemo(() => ({
    ...emptyContact,
    ...contactAttr,
  }), [contactAttr]);

  const {phone, mail, url, street, zip, city} = contact;

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
  ]), [name, street, zip, city, phone, mail, url]);

  const dataViewFields = useMemo(() => ([
    {
      id: "label",
      label: __("Field", "rrze-faudir"),
      enableHiding: false,
      getValue: ({item}: {item: DataViewRow}) => item.label,
    },
    {
      id: "value",
      label: __("API value", "rrze-faudir"),
      enableSorting: false,
      render: ({item}: {item: DataViewRow}) => item.value ? item.value : <span className="rrze-faudir__dataviews-empty">{__("No data", "rrze-faudir")}</span>,
    },
    {
      id: "visibility",
      label: __("Display", "rrze-faudir"),
      enableSorting: false,
      render: ({item}: {item: DataViewRow}) => (
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

  return (
    <>
      <InspectorControls>
        <PanelBody title={__("Organization", "rrze-faudir")} initialOpen={true}>
          <OrganizationIdentifierDetector
            attributes={attributes}
            setAttributes={setAttributes}
          />
        </PanelBody>
        <PanelBody title={__("Available data", "rrze-faudir")} initialOpen={true}>
          <DataViews
            data={dataviewData}
            fields={dataViewFields}
            view={dataView}
            onChangeView={setDataView}
            paginationInfo={dataviewPagination}
            defaultLayouts={{table: {showMedia: false}}}
            getItemId={(item: DataViewRow) => item.id}
            empty={<p>{__("No API data fetched yet.", "rrze-faudir")}</p>}
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
               src="./" alt="" width="640" height="360"/>
        </figure>

        {name && isFieldVisible("name") && (
            <header className="rrze-elements-blocks_service__meta_headline">
                <h2 id="service-title" className="meta-headline">{name}</h2>
                <p className="lede">Wir helfen bei der Wahl des Studiums gerne weiter.</p>
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

        <section aria-labelledby="hours-h">
          <h3 id="hours-h">Sprechzeiten</h3>
          <ul>
            <li>
              Mo., Di. und Do.,
              <time dateTime="10:00">10.00</time>–
              <time dateTime="16:00">16.00&nbsp;Uhr</time>
            </li>
          </ul>
        </section>
        {hasAnyContact && (
          <>
            <section aria-labelledby="contact-h">
              <h3 id="contact-h">Kontakt</h3>
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
