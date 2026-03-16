import { __ } from "@wordpress/i18n";
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

export default function CategorySelector({
    categories,
    selectedCategory,
    setAttributes,
}: CategorySelectorProps) {
    const selectedTokens = selectedCategory.trim().length > 0
        ? selectedCategory.split(",").map(function(token) {
            return token.trim();
        }).filter(function(token) {
            return token !== "";
        })
        : [];

    const suggestions = categories.map(function(category) {
        return category.name;
    });

    const onChangeTokenList = function(newTokens: string[]) {
        const validatedTokens = newTokens.filter(function(token) {
            return suggestions.includes(token);
        });

        const newCategoryString = validatedTokens.join(", ");

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
                    {__("There are currently no categories available. Start adding your first FAUdir categories via the WordPress Dashboard > Persons > Categories.", "rrze-faudir")}
                </Notice>
            )}
        </div>
    );
}