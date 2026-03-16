import { SelectControl, __experimentalDivider as Divider } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { EditProps } from "../faudir/types";

interface SortSelectorProps {
    attributes: EditProps["attributes"];
    setAttributes: EditProps["setAttributes"];
}

const allowedSortValues = [
    "familyName",
    "honorificprefix, familyName",
    "role",
    "role, honorificprefix",
    "honorificprefix",
    "email",
    "identifier_order",
] as const;

type SortValue = (typeof allowedSortValues)[number];
type OrderValue = "asc" | "desc";

const sortLabelMap: Record<SortValue, string> = {
    familyName: __("Family name", "rrze-faudir"),
    "honorificprefix, familyName": __("Academic title, then family name", "rrze-faudir"),
    role: __("Head of Department first", "rrze-faudir"),
    "role, honorificprefix": __("Head of Department first, then academic title", "rrze-faudir"),
    honorificprefix: __("Academic title", "rrze-faudir"),
    email: __("Email", "rrze-faudir"),
    identifier_order: __("Identifier order", "rrze-faudir"),
};

const criterionLabelMap: Record<string, string> = {
    familyName: __("Family name", "rrze-faudir"),
    honorificprefix: __("Academic title", "rrze-faudir"),
    role: __("Head of Department first", "rrze-faudir"),
    email: __("Email", "rrze-faudir"),
    identifier_order: __("Identifier order", "rrze-faudir"),
};

function isSortValue(value: string): value is SortValue {
    return (allowedSortValues as readonly string[]).includes(value);
}

function normalizeSort(raw: unknown): SortValue {
    if (typeof raw === "string" && isSortValue(raw)) {
        return raw;
    }

    return "familyName";
}

function normalizeOrder(raw: unknown): OrderValue {
    if (typeof raw === "string" && raw.toLowerCase() === "desc") {
        return "desc";
    }

    return "asc";
}

function getCriterionLabel(part: string): string {
    const key = part.trim();
    return criterionLabelMap[key] || key;
}

function getOrderOptions(): Array<{ value: OrderValue; label: string }> {
    return [
        { value: "asc", label: __("Ascending (A→Z)", "rrze-faudir") },
        { value: "desc", label: __("Descending (Z→A)", "rrze-faudir") },
    ];
}

function buildSortOptions(): Array<{ value: SortValue; label: string }> {
    return allowedSortValues.map(function(value) {
        return {
            value: value,
            label: sortLabelMap[value],
        };
    });
}

export default function SortSelector({ attributes, setAttributes }: SortSelectorProps) {
    const sort = normalizeSort(attributes.sort);
    const rawOrder = typeof attributes.order === "string" ? attributes.order : "asc";

    const sortParts = sort.split(/\s*,\s*/).filter(Boolean);
    const orderParts = rawOrder.split(/\s*,\s*/).filter(Boolean);
    const isIdentifierOrder = sortParts.includes("identifier_order");

    function handleSortChange(value: string) {
        if (!isSortValue(value)) {
            return;
        }

        setAttributes({ sort: value });
    }

    function getOrderForIndex(index: number): OrderValue {
        const current = orderParts[index] || orderParts[orderParts.length - 1] || "asc";
        return normalizeOrder(current);
    }

    function setOrderForIndex(index: number, dir: OrderValue) {
        const next = orderParts.slice();

        while (next.length <= index) {
            next.push(next[next.length - 1] || "asc");
        }

        next[index] = dir;

        setAttributes({
            order: next.join(", "),
        });
    }

    function handleGlobalOrderChange(value: string) {
        const dir = normalizeOrder(value);
        setAttributes({ order: dir });
    }

    function renderPerCriterionControl(part: string, index: number) {
        const label = getCriterionLabel(part);

        function handlePerCriterionChange(value: string) {
            setOrderForIndex(index, normalizeOrder(value));
        }

        return (
            <SelectControl
                key={`${part.trim()}-${index}`}
                label={label}
                value={getOrderForIndex(index)}
                options={getOrderOptions()}
                onChange={handlePerCriterionChange}
            />
        );
    }

    return (
        <>
            <SelectControl
                label={__("Sort by", "rrze-faudir")}
                value={sort}
                options={buildSortOptions()}
                onChange={handleSortChange}
            />

            <Divider />

            <SelectControl
                label={__("Order (global)", "rrze-faudir")}
                value={getOrderForIndex(0)}
                options={getOrderOptions()}
                disabled={isIdentifierOrder}
                onChange={handleGlobalOrderChange}
                help={__("Applies to all criteria unless you override it per criterion below.", "rrze-faudir")}
            />

            {sortParts.length > 1 && !isIdentifierOrder && (
                <>
                    <Divider />
                    <p className="rrze-faudir-sortselector__subheadline">
                        <strong>{__("Per-criterion order", "rrze-faudir")}</strong>
                    </p>
                    {sortParts.map(renderPerCriterionControl)}
                </>
            )}

            {isIdentifierOrder && (
                <p className="rrze-faudir-sortselector__hint">
                    {__("Order is ignored when using “Identifier order”.", "rrze-faudir")}
                </p>
            )}
        </>
    );
}