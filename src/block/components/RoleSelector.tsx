import {useState, useEffect} from '@wordpress/element';
import {ComboboxControl, FormTokenField} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {EditProps} from '../types';

interface RoleSelectorProps {
  setAttributes: EditProps['setAttributes'];
}

export default function RoleSelector({ setAttributes }: RoleSelectorProps) {
  const [personRoles, setPersonRoles] = useState<Record<string, string>>({});
  const [tempRoles, setTempRoles] = useState<string[]>([]);

  useEffect(() => {
    apiFetch({path: '/wp/v2/settings/rrze_faudir_options'})
      .then((data: any) => {
        if (data?.person_roles) {
          setPersonRoles(data.person_roles);
        }
      })
      .catch((error) => {
        console.error('Fehler beim Laden der person_roles:', error);
      });
  }, []);

  const onPersonRoleChange = (value: string) => {
    setTempRoles([...tempRoles, value]);
    //setAttributes({ role: value });
  };

  const options = Object.entries(personRoles).map(([roleKey, roleLabel]) => ({
    value: roleKey,
    label: roleLabel,
  }));

  const onChangeTemporaryTokens = (newTokens: string[]) => {
    setTempRoles(newTokens);
  }

  useEffect(() => {
    setAttributes({role: tempRoles.join(',')});
  }, [tempRoles]);

  return (
    <>
      <ComboboxControl
        options={options}
        onChange={onPersonRoleChange}
        label={__('Filter by Role', 'rrze-faudir')}
        help={__('Select a category to filter the person entries by.', 'rrze-faudir')}
        allowReset={false}
        value={""}
      />
      {tempRoles.length > 0 &&
          <FormTokenField
              value={tempRoles}
              label={__('Currently selected role filters.', 'rrze-faudir')}
              onChange={onChangeTemporaryTokens}
          />
      }
    </>
  );
}