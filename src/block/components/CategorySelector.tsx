import {__} from "@wordpress/i18n";
import {CheckboxControl} from "@wordpress/components";

interface CategorySelectorProps {
  categories: any[];
  selectedCategory: string;
  selectedPosts: number[];
  selectedPersonIds: number[];
  setAttributes: (newAttrs: object) => void;
}
export default function CategorySelector({categories, selectedCategory, selectedPosts, selectedPersonIds, setAttributes}: CategorySelectorProps) {
  return (
    <>
      <h4>{__('Select Category', 'rrze-faudir')}</h4>
      {categories.map((category) => (
        <CheckboxControl
          key={category.id}
          label={category.name}
          checked={
            selectedCategory === category.name
          }
          onChange={() => {
            // If the category is already selected, unselect it by setting to empty string
            const newCategory =
              selectedCategory === category.name ? '' : category.name;
            setAttributes({
              selectedCategory: newCategory,
              // Clear selected posts when unchecking category
              selectedPosts:
                newCategory === '' ? [] : selectedPosts,
              selectedPersonIds:
                newCategory === '' ? [] : selectedPersonIds,
            });
          }}
        />
      ))}
    </>
  );
}