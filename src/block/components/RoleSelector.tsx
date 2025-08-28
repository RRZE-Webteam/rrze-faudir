import {__} from "@wordpress/i18n";
import {TextControl} from "@wordpress/components";
import {EditProps} from "../types";

interface RoleSelectorProps {
  setAttributes: EditProps['setAttributes'];
  attributes: EditProps['attributes'];
}

export default function RoleSelector({ attributes, setAttributes }: RoleSelectorProps) {
  const {role} = attributes;
  const handlePersonRoleChange = (value: string) => {
    setAttributes({role: value});
  };
	


  return (
    <>
      <TextControl
       
        label={__('Filter by Role', 'rrze-faudir')}
        help={__('Filter contact entries by FAUdir job / FAUdir role. (E.g. "Head", "Deputy", "Professor", "Employee" as you see in FAUdir).', 'rrze-faudir')}
        type="text"
	onChange={handlePersonRoleChange}
        value={role}
      />
     
    </>
  );
}