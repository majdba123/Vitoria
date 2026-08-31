import { Eye, Star, Trash2 } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { useState } from 'react';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Existing-photo manager for the product edit page: mark a photo for
 * removal, mark one as primary, and tweak type/sort order per photo before
 * the batched "Save photo changes" submit. Ported from the
 * data-existing-photo-* grid in admin/products/edit.blade.php.
 */
export function ExistingPhotoGrid({ photos, removedIds, onToggleRemove, primaryId, onTogglePrimary, edits, onEditChange }) {
    const { products } = useI18n();
    const [lightbox, setLightbox] = useState(null);

    if (photos.length === 0) {
        return <p className="text-sm text-muted-foreground">{products.form.no_photos_yet}</p>;
    }

    return (
        <>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                {photos.map((photo) => {
                    const isRemoved = removedIds.includes(photo.id);
                    const isPrimary = primaryId === photo.id;
                    const edit = edits[photo.id] ?? { image_type: photo.image_type || 'front', sort_order: photo.sort_order || 1 };

                    return (
                        <div key={photo.id} className="space-y-2">
                            <div className={`relative aspect-square overflow-hidden rounded-lg border-2 ${isRemoved ? 'border-[var(--color-danger-500)] ring-4 ring-[var(--color-danger-200)]' : isPrimary ? 'border-[var(--color-success-500)] ring-4 ring-[var(--color-success-200)]' : 'border-border'}`}>
                                <img src={photo.url} alt="" className={`size-full object-cover ${isRemoved || isPrimary ? 'opacity-60' : ''}`} />
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <Select value={edit.image_type} onValueChange={(value) => onEditChange(photo.id, { ...edit, image_type: value })}>
                                    <SelectTrigger size="sm" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="primary">{products.form.image_type_primary}</SelectItem>
                                        <SelectItem value="front">{products.form.image_type_front}</SelectItem>
                                        <SelectItem value="back">{products.form.image_type_back}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Input type="number" min="1" value={edit.sort_order} onChange={(e) => onEditChange(photo.id, { ...edit, sort_order: Number(e.target.value) || 1 })} />
                            </div>
                            <div className="flex items-center justify-center gap-1.5">
                                <button type="button" onClick={() => onToggleRemove(photo.id)} className={`flex size-9 items-center justify-center rounded-md border ${isRemoved ? 'border-[var(--color-danger-400)] bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]' : 'border-border text-muted-foreground hover:bg-accent'}`} aria-label={products.form.remove_aria_label}>
                                    <Trash2 className="size-4" />
                                </button>
                                <button type="button" onClick={() => setLightbox(photo.url)} className="flex size-9 items-center justify-center rounded-md border border-border text-muted-foreground hover:bg-accent" aria-label={products.form.view_aria_label}>
                                    <Eye className="size-4" />
                                </button>
                                <button type="button" onClick={() => onTogglePrimary(photo.id)} className={`flex size-9 items-center justify-center rounded-md border ${isPrimary ? 'border-[var(--color-success-400)] bg-[var(--color-success-soft)] text-[var(--color-success-strong)]' : 'border-border text-muted-foreground hover:bg-accent'}`} aria-label={products.form.mark_primary_aria_label}>
                                    <Star className="size-4" />
                                </button>
                            </div>
                        </div>
                    );
                })}
            </div>

            {lightbox && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" onClick={() => setLightbox(null)}>
                    <img src={lightbox} alt="" className="max-h-[90vh] max-w-[90vw] rounded-lg object-contain" />
                </div>
            )}
        </>
    );
}
