import {__experimentalText as Text, Placeholder} from "@wordpress/components";
import {__} from "@wordpress/i18n";

export default function LoadingPlaceholder() {
  return (
    <Placeholder
      label={__('FAUdir Preview…', 'rrze-faudir')}
    >
      <div>
        <Text>{__('The Preview is loading…', 'rrze-faudir')}</Text>
      </div>
    </Placeholder>
  );
}