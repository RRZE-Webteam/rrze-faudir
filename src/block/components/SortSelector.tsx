import {SelectControl, __experimentalDivider as Divider} from "@wordpress/components";
import {__} from "@wordpress/i18n";
import {EditProps} from "../types";

interface SortSelectorProps {
  attributes: EditProps['attributes'];
  setAttributes: EditProps['setAttributes'];
}

const sortLabelMap: Record<string, string> = {
  familyName: __('Family name', 'rrze-faudir'),
  givenName: __('Given name', 'rrze-faudir'),
  email: __('Email', 'rrze-faudir'),
  honorificprefix: __('Academic Title', 'rrze-faudir'),
  role: __('Head of Department first', 'rrze-faudir'),
  identifier_order: __('Identifier Order', 'rrze-faudir'),
};

export default function SortSelector({attributes, setAttributes}: SortSelectorProps) {
  const {sort = 'familyName', order = 'asc'} = attributes;

  const handleSortChange = (value: string) => {
    setAttributes({sort: value});
    // Falls vorher pro-Kriterium Orders gesetzt wurden und sich die Anzahl ändert,
    // lassen wir `order` unangetastet. (Optional: hier zurück auf 'asc' setzen)
  };

  // Zerlege Sortkriterien und existierende Orders
  const sortParts = sort.split(/\s*,\s*/).filter(Boolean);
  const orderParts = order.split(/\s*,\s*/).filter(Boolean);
  const isIdentifierOrder = sortParts.includes('identifier_order');

  // Order für Index i ermitteln (fehlende Werte als 'asc')
  const getOrderForIndex = (i: number): 'asc' | 'desc' => {
    const val = (orderParts[i] || orderParts[orderParts.length - 1] || 'asc').toLowerCase();
    return (val === 'desc' ? 'desc' : 'asc');
  };

  const setOrderForIndex = (i: number, dir: 'asc' | 'desc') => {
    const next = orderParts.slice();
    next[i] = dir;
    // Optional: trailing Werte kürzen, wenn sie identisch sind – wir lassen sie stehen.
    setAttributes({order: next.join(', ')});
  };

  const handleGlobalOrderChange = (value: string) => {
    const dir = value === 'desc' ? 'desc' : 'asc';
    // Global: nur ein Wert schreiben
    setAttributes({order: dir});
  };

  return (
    <>
      <SelectControl
        label={__('Sort by', 'rrze-faudir')}
        value={sort}
        options={[
          { value: 'familyName', label: sortLabelMap.familyName },
          { value: 'honorificprefix, familyName', label: __('Academic title, then family name', 'rrze-faudir') },
          { value: 'role', label: sortLabelMap.role },
          { value: 'role, honorificprefix', label: __('Head of Department first, then academic title', 'rrze-faudir') },
          { value: 'honorificprefix', label: sortLabelMap.honorificprefix },
          { value: 'email', label: sortLabelMap.email },
          { value: 'identifier_order', label: sortLabelMap.identifier_order },
        ]}
        onChange={handleSortChange}
      />

      <Divider />

      <SelectControl
        label={__('Order (global)', 'rrze-faudir')}
        value={getOrderForIndex(0)}
        options={[
          { value: 'asc', label: __('Ascending (A→Z)', 'rrze-faudir') },
          { value: 'desc', label: __('Descending (Z→A)', 'rrze-faudir') },
        ]}
        disabled={isIdentifierOrder}
        onChange={(val: string) => handleGlobalOrderChange(val)}
        help={__('Applies to all criteria unless you override per-criterion below.', 'rrze-faudir')}
      />

      {/* Wenn mehrere Kriterien: pro-Kriterium-Order anbieten */}
      {sortParts.length > 1 && !isIdentifierOrder && (
        <>
          <Divider />
          <p style={{marginBottom: 8}}><strong>{__('Per-criterion order (optional)', 'rrze-faudir')}</strong></p>
          {sortParts.map((part, i) => {
            const key = part.trim();
            const label = sortLabelMap[key] || key;
            return (
              <SelectControl
                key={`${key}-${i}`}
                label={label}
                value={getOrderForIndex(i)}
                options={[
                  { value: 'asc', label: __('Ascending (A→Z)', 'rrze-faudir') },
                  { value: 'desc', label: __('Descending (Z→A)', 'rrze-faudir') },
                ]}
                onChange={(val: string) => setOrderForIndex(i, val === 'desc' ? 'desc' : 'asc')}
              />
            );
          })}
        </>
      )}

      {/* Infohinweis, falls identifier_order gewählt ist */}
      {isIdentifierOrder && (
        <p style={{marginTop: 8}}>
          {__('Order is ignored when using “Identifier Order”.', 'rrze-faudir')}
        </p>
      )}
    </>
  );
}
