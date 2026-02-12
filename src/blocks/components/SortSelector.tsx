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

const sortLabelMap: Record<SortValue, string> = {
    familyName: __("Family name", "rrze-faudir"),
    "honorificprefix, familyName": __("Academic title, then family name", "rrze-faudir"),
    role: __("Head of Department first", "rrze-faudir"),
    "role, honorificprefix": __("Head of Department first, then academic title", "rrze-faudir"),
    honorificprefix: __("Academic Title", "rrze-faudir"),
    email: __("Email", "rrze-faudir"),
    identifier_order: __("Identifier Order", "rrze-faudir"),
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

function normalizeOrder(raw: unknown): "asc" | "desc" {
    if (typeof raw === "string" && raw.toLowerCase() === "desc") {
        return "desc";
    }
    return "asc";
}

export default function SortSelector({ attributes, setAttributes }: SortSelectorProps) {
    const sort = normalizeSort(attributes.sort);
    const order = typeof attributes.order === "string" ? attributes.order : "asc";

    function handleSortChange(value: string) {
        if (!isSortValue(value)) {
            return;
        }
        setAttributes({ sort: value });
    }

    const sortParts = sort.split(/\s*,\s*/).filter(Boolean);
    const orderParts = order.split(/\s*,\s*/).filter(Boolean);
    const isIdentifierOrder = sortParts.includes("identifier_order");

    function getOrderForIndex(i: number): "asc" | "desc" {
        const val = (orderParts[i] || orderParts[orderParts.length - 1] || "asc").toLowerCase();
        return val === "desc" ? "desc" : "asc";
    }

    function setOrderForIndex(i: number, dir: "asc" | "desc") {
        const next = orderParts.slice();
        next[i] = dir;
        setAttributes({ order: next.join(", ") });
    }

    function handleGlobalOrderChange(value: string) {
        const dir = normalizeOrder(value);
        setAttributes({ order: dir });
    }

    function renderPerCriterionControl(part: string, i: number) {
        const key = part.trim();
        const label = key === "identifier_order"
            ? sortLabelMap.identifier_order
            : key === "familyName"
                ? sortLabelMap.familyName
                : key === "honorificprefix"
                    ? sortLabelMap.honorificprefix
                    : key === "role"
                        ? sortLabelMap.role
                        : key === "email"
                            ? sortLabelMap.email
                            : key;

        function handlePerCriterionChange(val: string) {
            setOrderForIndex(i, val === "desc" ? "desc" : "asc");
        }

        return (
            <SelectControl
                key={`${key}-${i}`}
                label={label}
                value={getOrderForIndex(i)}
                options={[
                    { value: "asc", label: __("Ascending (A→Z)", "rrze-faudir") },
                    { value: "desc", label: __("Descending (Z→A)", "rrze-faudir") },
                ]}
                onChange={handlePerCriterionChange}
            />
        );
    }

    function buildSortOptions() {
        return allowedSortValues.map(function (value) {
            return { value, label: sortLabelMap[value] };
        });
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
                options={[
                    { value: "asc", label: __("Ascending (A→Z)", "rrze-faudir") },
                    { value: "desc", label: __("Descending (Z→A)", "rrze-faudir") },
                ]}
                disabled={isIdentifierOrder}
                onChange={handleGlobalOrderChange}
                help={__("Applies to all criteria unless you override per-criterion below.", "rrze-faudir")}
            />

            {sortParts.length > 1 && !isIdentifierOrder && (
                <>
                    <Divider />
                    <p style={{ marginBottom: 8 }}>
                        <strong>{__("Per-criterion order (optional)", "rrze-faudir")}</strong>
                    </p>
                    {sortParts.map(renderPerCriterionControl)}
                </>
            )}

            {isIdentifierOrder && (
                <p style={{ marginTop: 8 }}>
                    {__("Order is ignored when using “Identifier Order”.", "rrze-faudir")}
                </p>
            )}
        </>
    );
}