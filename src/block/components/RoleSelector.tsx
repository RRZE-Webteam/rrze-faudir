import {TextControl} from "@wordpress/components";
import {__} from "@wordpress/i18n";
import {EditProps} from "../types";
interface RoleSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
}

export default function RoleSelector ({attributes, setAttributes}: RoleSelectorProps) {
  return (
    <TextControl
      label={__('Role', 'rrze-faudir')}
      value={attributes.role}
      onChange={(value) => setAttributes({role: value})}
      type="text"
    />
  );
}

