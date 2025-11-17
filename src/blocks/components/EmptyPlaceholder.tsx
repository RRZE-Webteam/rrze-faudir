import {__experimentalText as Text, Placeholder} from "@wordpress/components";
import {__} from "@wordpress/i18n";

export default function EmptyPlaceholder() {
  return (
    <Placeholder
      label={__('FAUdir Preview…', 'rrze-faudir')}
    >
      <div>
        <Text>{__('Your current configuration does not return a contact. Try adjusting your filter settings.', 'rrze-faudir')}</Text>
      </div>
    </Placeholder>
  );
}