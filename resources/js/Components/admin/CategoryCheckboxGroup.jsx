/**
 * Business-type-aware category picker used on the vendor create/edit forms:
 * shows a flat grid for agriculture-only or veterinary-only, and two
 * labelled groups when business_type is "both".
 */
export function CategoryCheckboxGroup({ businessType, categories, selectedIds, onToggle, emptyHint }) {
    if (!businessType) {
        return <p className="rounded-md border border-dashed border-border px-3 py-3 text-sm text-muted-foreground sm:col-span-2">{emptyHint}</p>;
    }

    const renderGroup = (title, rows) => (
        <div className="sm:col-span-2" key={title}>
            {title && <h4 className="mb-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">{title}</h4>}
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {rows.map((category) => (
                    <label
                        key={category.id}
                        className="flex cursor-pointer items-center gap-3 rounded-md border border-border p-3 transition-colors hover:border-primary/50 hover:bg-accent/40"
                    >
                        <input
                            type="checkbox"
                            checked={selectedIds.includes(category.id)}
                            onChange={() => onToggle(category.id)}
                            className="size-4 rounded border-border text-primary focus:ring-primary"
                        />
                        <div className="min-w-0 flex-1">
                            <span className="text-sm font-medium text-foreground">{category.name}</span>
                            <span className={`ms-2 text-xs font-semibold ${category.type === 'veterinary' ? 'text-[var(--color-info-strong)]' : 'text-[var(--color-success-strong)]'}`}>
                                {category.type === 'veterinary' ? 'Veterinary' : 'Agriculture'}
                            </span>
                        </div>
                    </label>
                ))}
            </div>
        </div>
    );

    if (businessType === 'both') {
        return (
            <>
                {renderGroup('Agriculture categories', categories.filter((c) => c.type === 'agriculture'))}
                {renderGroup('Veterinary categories', categories.filter((c) => c.type === 'veterinary'))}
            </>
        );
    }

    return renderGroup(null, categories.filter((c) => c.type === businessType));
}
