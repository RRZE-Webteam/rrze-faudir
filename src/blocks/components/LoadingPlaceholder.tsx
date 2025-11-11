// @ts-ignore
import {__experimentalText as Text, Placeholder, __experimentalSpacer as Spacer , ProgressBar} from "@wordpress/components";
import {__} from "@wordpress/i18n";

export default function LoadingPlaceholder() {
  return (
    <Placeholder
      label={__('FAUdir Preview…', 'rrze-faudir')}
    >
      <div>
        <Spacer paddingTop="1rem" paddingBottom="1rem">
        <ProgressBar />
        </Spacer>
        <Text>{__('The Preview is loading…', 'rrze-faudir')}</Text>
      </div>
    </Placeholder>
  );
}