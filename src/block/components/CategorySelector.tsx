import {__} from "@wordpress/i18n";
import {FormTokenField, __experimentalHeading as Heading} from "@wordpress/components";

interface CategorySelectorProps {
  categories: any[];
  selectedCategory: string;
  setAttributes: (newAttrs: object) => void;
}

export default function CategorySelector({
 categories,
 selectedCategory,
 setAttributes,
}: CategorySelectorProps) {
  const selectedTokens =
    selectedCategory.trim().length > 0
      ? selectedCategory.split(",").map((token) => token.trim())
      : [];
  const suggestions = categories.map((category) => category.name);
  console.log(selectedCategory);
  const onChangeTokenList = (newTokens: string[]) => {
    const validatedTokens = newTokens.filter((token) =>
      suggestions.includes(token)
    );

    const newCategoryString = validatedTokens.join(", ");

    setAttributes({
      selectedCategory: newCategoryString,
      selectedPosts: [],
      selectedPersons: [],
    });
  };

  return (
    <>
      <Heading level={4}>{__("Select Categories", "rrze-faudir")}</Heading>
      <FormTokenField
        __next40pxDefaultSize
        label={__("Type to add categories", "rrze-faudir")}
        value={selectedTokens}
        suggestions={suggestions}
        onChange={onChangeTokenList}
      />
    </>
  );
}
