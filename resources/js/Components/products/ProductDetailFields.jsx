import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { DetailFieldGroup } from '@/Components/products/DetailFieldGroup';
import {
    AGRICULTURAL_TYPE_OPTIONS,
    SHARED_FIELDS,
    SHARED_REGISTRATION_STATUS_OPTIONS,
    SHARED_REPEATERS,
    SHARED_TEXTAREAS,
    AGRI_COMMON_REPEATERS,
    PESTICIDE_SUBTYPES,
    FERTILIZER_SUBTYPES,
    SEED_SUBTYPES,
    PESTICIDE_FIELDS,
    PESTICIDE_REPEATERS,
    PESTICIDE_TEXTAREAS,
    FERTILIZER_FIELDS,
    FERTILIZER_REPEATERS,
    SEED_FIELDS,
    SEED_REPEATERS,
    VETERINARY_FIELDS,
    VETERINARY_REPEATERS,
    VETERINARY_TEXTAREAS,
} from '@/lib/product-detail-schema';

/**
 * Full shared + agricultural + veterinary detail form, ported from
 * components/products/detail-fields.blade.php. Visibility of the
 * agriculture subtype groups (pesticide / fertilizer&soil / seed) follows
 * `agriculturalProductType`, matching the original's data-agri-subtype
 * show/hide behavior.
 */
export function ProductDetailFields({
    categoryType,
    agriculturalProductType,
    onAgriculturalProductTypeChange,
    sharedDetail,
    onSharedChange,
    agriculturalDetail,
    onAgriculturalChange,
    veterinaryDetail,
    onVeterinaryChange,
    errors,
}) {
    const isPesticide = PESTICIDE_SUBTYPES.includes(agriculturalProductType);
    const isFertilizer = FERTILIZER_SUBTYPES.includes(agriculturalProductType);
    const isSeed = SEED_SUBTYPES.includes(agriculturalProductType);

    return (
        <>
            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">Shared parameters</CardTitle>
                    <p className="text-sm text-muted-foreground">These fields apply to every product regardless of type.</p>
                </CardHeader>
                <CardContent className="space-y-6 p-5 sm:p-6">
                    <DetailFieldGroup
                        fields={SHARED_FIELDS}
                        selects={[{ key: 'registration_status', label: 'Registration status', options: SHARED_REGISTRATION_STATUS_OPTIONS }]}
                        values={sharedDetail}
                        onChange={onSharedChange}
                        errors={errors}
                        prefix="shared_detail"
                    />
                    <DetailFieldGroup repeaters={SHARED_REPEATERS} values={sharedDetail} onChange={onSharedChange} errors={errors} prefix="shared_detail" />
                    <DetailFieldGroup textareas={SHARED_TEXTAREAS} values={sharedDetail} onChange={onSharedChange} errors={errors} prefix="shared_detail" />
                </CardContent>
            </Card>

            {categoryType === 'agriculture' && (
                <Card className="border-border/80 shadow-none">
                    <CardHeader className="border-b border-border/80">
                        <CardTitle className="text-base font-bold">Agricultural parameters</CardTitle>
                        <p className="text-sm text-muted-foreground">Select the agricultural product type to reveal only the relevant fields.</p>
                    </CardHeader>
                    <CardContent className="space-y-6 p-5 sm:p-6">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label className="mb-1.5 block text-sm font-medium">Agricultural product type</label>
                                <select
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    value={agriculturalProductType}
                                    onChange={(e) => onAgriculturalProductTypeChange(e.target.value)}
                                >
                                    <option value="">Select agricultural type</option>
                                    {AGRICULTURAL_TYPE_OPTIONS.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            </div>
                            <DetailFieldGroup fields={[{ key: 'formulation', label: 'Formulation / formula' }]} values={agriculturalDetail} onChange={onAgriculturalChange} errors={errors} prefix="agricultural_detail" />
                        </div>

                        <DetailFieldGroup repeaters={AGRI_COMMON_REPEATERS} values={agriculturalDetail} onChange={onAgriculturalChange} errors={errors} prefix="agricultural_detail" />

                        {isPesticide && (
                            <div className="space-y-6 rounded-lg border border-[var(--color-success-200)] bg-[var(--color-success-soft)]/40 p-4">
                                <div>
                                    <h3 className="text-sm font-bold text-foreground">Pesticide fields</h3>
                                    <p className="text-xs text-muted-foreground">Visible when the agricultural product type is pesticide.</p>
                                </div>
                                <DetailFieldGroup fields={PESTICIDE_FIELDS} values={agriculturalDetail} onChange={onAgriculturalChange} errors={errors} prefix="agricultural_detail" />
                                <DetailFieldGroup repeaters={PESTICIDE_REPEATERS} values={agriculturalDetail} onChange={onAgriculturalChange} errors={errors} prefix="agricultural_detail" />
                                <DetailFieldGroup textareas={PESTICIDE_TEXTAREAS} values={agriculturalDetail} onChange={onAgriculturalChange} errors={errors} prefix="agricultural_detail" />
                            </div>
                        )}

                        {isFertilizer && (
                            <div className="space-y-6 rounded-lg border border-[var(--color-info-200)] bg-[var(--color-info-soft)]/40 p-4">
                                <div>
                                    <h3 className="text-sm font-bold text-foreground">Fertilizer and soil fields</h3>
                                    <p className="text-xs text-muted-foreground">Used for fertilizers, soil amendments, and growth regulators.</p>
                                </div>
                                <DetailFieldGroup fields={FERTILIZER_FIELDS} values={agriculturalDetail} onChange={onAgriculturalChange} errors={errors} prefix="agricultural_detail" />
                                <DetailFieldGroup repeaters={FERTILIZER_REPEATERS} values={agriculturalDetail} onChange={onAgriculturalChange} errors={errors} prefix="agricultural_detail" />
                            </div>
                        )}

                        {isSeed && (
                            <div className="space-y-6 rounded-lg border border-[var(--color-warning-200)] bg-[var(--color-warning-soft)]/40 p-4">
                                <div>
                                    <h3 className="text-sm font-bold text-foreground">Seed fields</h3>
                                    <p className="text-xs text-muted-foreground">Visible when the agricultural product type is seeds.</p>
                                </div>
                                <DetailFieldGroup fields={SEED_FIELDS} values={agriculturalDetail} onChange={onAgriculturalChange} errors={errors} prefix="agricultural_detail" />
                                <DetailFieldGroup repeaters={SEED_REPEATERS} values={agriculturalDetail} onChange={onAgriculturalChange} errors={errors} prefix="agricultural_detail" />
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            {categoryType === 'veterinary' && (
                <Card className="border-border/80 shadow-none">
                    <CardHeader className="border-b border-border/80">
                        <CardTitle className="text-base font-bold">Veterinary parameters</CardTitle>
                        <p className="text-sm text-muted-foreground">Organized fields for medicines, vaccines, and veterinary supplies.</p>
                    </CardHeader>
                    <CardContent className="space-y-6 p-5 sm:p-6">
                        <DetailFieldGroup fields={VETERINARY_FIELDS} values={veterinaryDetail} onChange={onVeterinaryChange} errors={errors} prefix="veterinary_detail" />
                        <DetailFieldGroup repeaters={VETERINARY_REPEATERS} values={veterinaryDetail} onChange={onVeterinaryChange} errors={errors} prefix="veterinary_detail" />
                        <DetailFieldGroup textareas={VETERINARY_TEXTAREAS} values={veterinaryDetail} onChange={onVeterinaryChange} errors={errors} prefix="veterinary_detail" />
                    </CardContent>
                </Card>
            )}
        </>
    );
}
