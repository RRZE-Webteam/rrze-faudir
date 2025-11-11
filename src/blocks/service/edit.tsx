import {
  useBlockProps,
} from "@wordpress/block-editor";
import {EditProps, OrganizationResponseProps} from "./types";
import {
  PanelBody
} from "@wordpress/components";
import {
  InspectorControls
} from "@wordpress/block-editor";
import {useEffect, useMemo} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import {__} from "@wordpress/i18n";
import OrganizationIdentifierDetector from "../components/OrganizationIdentifierDetector"
import { DataViews } from "@wordpress/dataviews";

type ContactData = {
  phone: string;
  mail: string;
  url: string;
  street: string;
  zip: string;
  city: string;
};

const emptyContact: ContactData = {
  phone: "",
  mail: "",
  url: "",
  street: "",
  zip: "",
  city: "",
};

export default function Edit({attributes, setAttributes}: EditProps) {
  const props = useBlockProps();
  const {orgid, contact = emptyContact, name} = attributes;

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

  const {phone, mail, url, street, zip, city} = useMemo(() => ({
    phone: contact?.phone ?? "",
    mail: contact?.mail ?? "",
    url: contact?.url ?? "",
    street: contact?.street ?? "",
    zip: contact?.zip ?? "",
    city: contact?.city ?? "",
  }), [contact]);

  const hasAnyContact = !!(phone || mail || url);

  return (
    <>
      {/*<DataViews view={} onChangeView={} fields={} data={} paginationInfo={} defaultLayouts={} getItemId={} />*/}
      <InspectorControls>
        <PanelBody>
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
               src="./" alt="" width="640" height="360"/>
        </figure>

        {name && (
            <header className="rrze-elements-blocks_service__meta_headline">
                <h2 id="service-title" className="meta-headline">{name}</h2>
                <p className="lede">Wir helfen bei der Wahl des Studiums gerne weiter.</p>
            </header>
        )}

        {(street || zip || city) && (
          <section className="rrze-elements-blocks_service__information" aria-labelledby="addr-h">
            <h3 id="addr-h">Adresse</h3>
            <address>
              {street && <span>{street}<br/></span>}
              {(zip || city) && (
                <span>{[zip, city].filter(Boolean).join(' ')}</span>
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
                {phone && (
                  <p>
                    <a href={`tel:${phone.replace(/\s+/g, '')}`}>
                      {phone}
                    </a>
                  </p>
                )}
                {mail && (
                  <p>
                    <a href={`mailto:${mail}`}>
                      {mail}
                    </a>
                  </p>
                )}
                {url && (
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
  )
    ;
}
