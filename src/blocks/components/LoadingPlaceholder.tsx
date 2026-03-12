import { Placeholder, ProgressBar, __experimentalSpacer as Spacer } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

export default function LoadingPlaceholder() {
  return (
    <Placeholder label={__("FAUdir Preview…", "rrze-faudir")}>
      <Spacer paddingTop="1rem" paddingBottom="1rem">
        <ProgressBar />
      </Spacer>

      <p>
        {__("The preview is loading…", "rrze-faudir")}
      </p>
    </Placeholder>
  );
}