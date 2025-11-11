import {
  useBlockProps,
} from "@wordpress/block-editor";
import { EditProps } from "./types";
import {
  InspectorControls
} from "@wordpress/block-editor";
import OrganizationIdentifierDetector from "../components/OrganizationIdentifierDetector"

export default function Edit({attributes, setAttributes}: EditProps) {
  const props = useBlockProps();

  return (
    <>
      <InspectorControls>
        <OrganizationIdentifierDetector
          attributes={attributes}
          setAttributes={setAttributes}
        />
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

        <header className="rrze-elements-blocks_service__meta_headline">
          <h2 id="service-title" className="meta-headline">Studienberatung</h2>
          <p className="lede">Wir helfen bei der Wahl des Studiums gerne weiter.</p>
        </header>

        <section className="rrze-elements-blocks_service__information" aria-labelledby="addr-h">
          <h3 id="addr-h">Adresse</h3>
          <address>
            Findelgasse 7/9<br/>
            90402 Nürnberg
          </address>
        </section>

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

        <section aria-labelledby="contact-h">
          <h3 id="contact-h">Kontakt</h3>
          <address>
            <p><a href="tel:+49151151151">+49&nbsp;151&nbsp;151&nbsp;151</a></p>
            <p><a href="mailto:info@fau.de">info@fau.de</a></p>
            <p><a href="https://www.fau.de">www.fau.de</a></p>
            <p><a href="https://www.fau.de">Messenger / Matrix</a></p>
          </address>
        </section>
      </article>
    </>
  );
}
