import {SelectControl} from "@wordpress/components";
import {__} from "@wordpress/i18n";
import {EditProps} from "../types";

interface SortSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
}

export default function SortSelector({attributes, setAttributes}: SortSelectorProps) {
  const {sort} = attributes;
  const handleSortChange = (value: string) => {
    setAttributes({sort: value});
  };

  return (
    <SelectControl
      label={__('Sort by', 'rrze-faudir')}
      value={sort}
      options={[
        {
          value: 'familyName',
          label: __('Last Name', 'rrze-faudir'),
        },
        {
          value: 'title_familyName',
          label: __('Title and Last Name', 'rrze-faudir'),
        },
        {
          value: 'head_first',
          label: __('Head of Department First', 'rrze-faudir'),
        },
        {
          value: 'prof_first',
          label: __('Professors First', 'rrze-faudir'),
        },
        {
          value: 'identifier_order',
          label: __('Identifier Order', 'rrze-faudir'),
        },
      ]}
      onChange={handleSortChange}
    />
  );
}