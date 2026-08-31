import { useRef, useState } from 'react';
import { UploadCloud, X, Eye } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Multi-photo picker ported from components/products/photo-upload.blade.php
 * + photo-upload-script.blade.php: drag & drop, up to 10 images, each with
 * an independent type (primary/front/back) and sort order. `onChange`
 * receives the raw [{file, image_type, sort_order}] payload the submit
 * handler appends as photos[]/photo_types[]/photo_sort_orders[].
 */
export function PhotoUpload({ photos, onChange, error }) {
    const { products } = useI18n();
    const inputRef = useRef(null);
    const [isDragging, setIsDragging] = useState(false);
    const [lightbox, setLightbox] = useState(null);

    const addFiles = (files) => {
        const imageFiles = files.filter((f) => f.type.startsWith('image/'));
        if (photos.length + imageFiles.length > 10) {
            window.alert(products.form.photo_max_alert);
            return;
        }

        const nextStart = photos.length + 1;
        const additions = imageFiles.map((file, index) => ({
            file,
            previewUrl: URL.createObjectURL(file),
            image_type: photos.length === 0 && index === 0 ? 'primary' : 'back',
            sort_order: nextStart + index,
        }));

        onChange([...photos, ...additions]);
    };

    const updateAt = (index, patch) => onChange(photos.map((p, i) => (i === index ? { ...p, ...patch } : p)));
    const removeAt = (index) => {
        URL.revokeObjectURL(photos[index].previewUrl);
        onChange(photos.filter((_, i) => i !== index));
    };

    return (
        <Card className="border-border/80 shadow-none">
            <CardHeader className="flex-row items-start justify-between gap-3 border-b border-border/80">
                <div>
                    <CardTitle className="text-base font-bold">{products.form.photo_upload_heading}</CardTitle>
                    <p className="text-sm text-muted-foreground">{products.form.photo_upload_hint}</p>
                </div>
            </CardHeader>
            <CardContent className="space-y-4 p-5 sm:p-6">
                <button
                    type="button"
                    onClick={() => inputRef.current?.click()}
                    onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
                    onDragLeave={() => setIsDragging(false)}
                    onDrop={(e) => {
                        e.preventDefault();
                        setIsDragging(false);
                        addFiles(Array.from(e.dataTransfer.files || []));
                    }}
                    className={`flex w-full flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-10 text-center transition-colors ${isDragging ? 'border-primary bg-accent/40' : 'border-border bg-muted/30 hover:border-primary/50'}`}
                >
                    <span className="mb-3 flex size-12 items-center justify-center rounded-full bg-accent text-accent-foreground">
                        <UploadCloud className="size-6" />
                    </span>
                    <p className="text-sm font-medium text-foreground">
                        {products.form.drag_drop_prefix} <span className="text-primary underline">{products.form.browse_link}</span>
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">{products.form.photo_format_hint}</p>
                    <input
                        ref={inputRef}
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        className="hidden"
                        onChange={(e) => {
                            addFiles(Array.from(e.target.files || []));
                            e.target.value = '';
                        }}
                    />
                </button>

                {error && <p className="text-xs font-medium text-[var(--color-danger-strong)]">{error}</p>}

                {photos.length > 0 && (
                    <>
                        <p className="text-xs font-medium text-muted-foreground">{products.form.photos_selected_label.replace(':count', String(photos.length))}</p>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {photos.map((photo, index) => (
                                <div key={index} className="overflow-hidden rounded-lg border border-border bg-card">
                                    <div className="relative aspect-[4/3] overflow-hidden bg-muted">
                                        <img src={photo.previewUrl} alt="" className="size-full object-cover" />
                                        <span className="absolute start-3 top-3 rounded-full bg-background/95 px-2.5 py-1 text-[11px] font-semibold text-foreground shadow-sm">
                                            {photo.image_type === 'primary' ? products.form.image_type_primary : photo.image_type === 'front' ? products.form.image_type_front : products.form.image_type_back} · #{photo.sort_order}
                                        </span>
                                        <div className="absolute end-2 top-2 flex gap-1.5">
                                            <button type="button" onClick={() => setLightbox(photo.previewUrl)} className="flex size-8 items-center justify-center rounded-md border border-border bg-background/95 text-foreground hover:bg-accent" aria-label={products.form.view_aria_label}>
                                                <Eye className="size-4" />
                                            </button>
                                            <button type="button" onClick={() => removeAt(index)} className="flex size-8 items-center justify-center rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]" aria-label={products.form.remove_aria_label}>
                                                <X className="size-4" />
                                            </button>
                                        </div>
                                    </div>
                                    <div className="space-y-3 p-3">
                                        <p className="truncate text-xs font-medium text-foreground">{photo.file.name}</p>
                                        <div className="grid grid-cols-2 gap-2">
                                            <Select value={photo.image_type} onValueChange={(value) => updateAt(index, { image_type: value })}>
                                                <SelectTrigger size="sm" className="w-full">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="primary">{products.form.image_type_primary}</SelectItem>
                                                    <SelectItem value="front">{products.form.image_type_front}</SelectItem>
                                                    <SelectItem value="back">{products.form.image_type_back}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={photo.sort_order}
                                                onChange={(e) => updateAt(index, { sort_order: Number(e.target.value) || index + 1 })}
                                            />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </>
                )}
            </CardContent>

            {lightbox && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" onClick={() => setLightbox(null)}>
                    <div className="relative max-h-[90vh] max-w-[90vw]">
                        <img src={lightbox} alt="" className="max-h-[90vh] max-w-[90vw] rounded-lg object-contain" />
                        <Button type="button" variant="secondary" size="icon" className="absolute end-2 top-2" onClick={() => setLightbox(null)}>
                            <X className="size-4" />
                        </Button>
                    </div>
                </div>
            )}
        </Card>
    );
}
