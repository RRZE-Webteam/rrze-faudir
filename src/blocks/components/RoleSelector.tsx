import { __ } from "@wordpress/i18n";
import { TextControl } from "@wordpress/components";
import { EditProps } from "../faudir/types";

interface RoleSelectorProps {
    setAttributes: EditProps["setAttributes"];
    attributes: EditProps["attributes"];
}

export default function RoleSelector({ attributes, setAttributes }: RoleSelectorProps) {
    const { role } = attributes;

    function handlePersonRoleChange(value: string) {
        setAttributes({
            role: value,
        });
    }
    function handleBlur() {
	setAttributes({
	    role: attributes.role?.trim() ?? "",
	});
    }

    return (
        <TextControl
            label={__("Filter by role", "rrze-faudir")}
            help={__(
                "Filter contacts by FAUdir role or job title (for example: Head, Deputy, Professor, Employee).",
                "rrze-faudir"
            )}
            type="text"
            value={role}
            onChange={handlePersonRoleChange}
	    onBlur={handleBlur}
        />
    );
}