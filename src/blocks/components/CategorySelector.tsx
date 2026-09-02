import { __ } from "@wordpress/i18n";
import { decodeEntities } from "@wordpress/html-entities";
import {
  FormTokenField,
  __experimentalHeading as Heading,
  Notice,
} from "@wordpress/components";
import { WPCategory, EditProps } from "../faudir/types";

interface CategorySelectorProps {
  categories: WPCategory[];
  selectedCategory: string;
  setAttributes: EditProps["setAttributes"];
}

type CategoryToken = string | { value: string };

export default function CategorySelector({
  categories,
  selectedCategory,
  setAttributes,
}: CategorySelectorProps) {
  const categoryOptions = categories.map((category) => {
    return {
      label: decodeEntities(category.name),
      slug: category.slug,
    };
  });

  const selectedSlugs =
    selectedCategory.trim().length > 0
      ? selectedCategory
          .split(",")
          .map((token) => {
            return token.trim();
          })
          .filter((token) => {
            return token !== "";
          })
      : [];

  const selectedTokens = selectedSlugs.map((slug) => {
    const match = categoryOptions.find((option) => {
      return option.slug === slug;
    });

    return match ? match.label : slug;
  });

  const suggestions = categoryOptions.map((option) => {
    return option.label;
  });

  const onChangeTokenList = (newTokens: CategoryToken[]) => {
    const validatedSlugs = newTokens
      .map((token) => {
        const tokenValue = typeof token === "string" ? token : token.value;
        const match = categoryOptions.find((option) => {
          return option.label === tokenValue;
        });

        return match ? match.slug : "";
      })
      .filter((slug) => {
        return slug !== "";
      });

    const newCategoryString = validatedSlugs.join(", ");

    setAttributes({
      selectedCategory: newCategoryString,
      selectedPosts: [],
      selectedPersonIds: [],
    });
  };

  return (
    <div>
      <Heading level={3}>{__("Select Categories", "rrze-faudir")}</Heading>

      <FormTokenField
        __next40pxDefaultSize
        label={__("Type to add categories", "rrze-faudir")}
        value={selectedTokens}
        disabled={suggestions.length === 0}
        suggestions={suggestions}
        onChange={onChangeTokenList}
      />

      {suggestions.length === 0 && (
        <Notice isDismissible={false} status="info">
          {__(
            "There are currently no categories available. Start adding your first FAUdir categories via the WordPress Dashboard > Persons > Categories.",
            "rrze-faudir",
          )}
        </Notice>
      )}
    </div>
  );
}
