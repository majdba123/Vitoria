import { useRef, useState } from 'react';
import { Upload, Download, Loader2 } from 'lucide-react';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Re-implements components/csv-import.blade.php as a React dialog: download
 * a starter template, upload a filled-in CSV, and refresh the list on
 * success. Same two API endpoints (`templateUrl` / `importUrl`) as before.
 */
export function CsvImportButton({ label, templateUrl, importUrl, onImported }) {
    const { common } = useI18n();
    const [open, setOpen] = useState(false);
    const [file, setFile] = useState(null);
    const [status, setStatus] = useState('idle'); // idle | uploading | error
    const [message, setMessage] = useState(null);
    const inputRef = useRef(null);

    const reset = () => {
        setFile(null);
        setStatus('idle');
        setMessage(null);
        if (inputRef.current) inputRef.current.value = '';
    };

    const upload = () => {
        if (!file) return;
        setStatus('uploading');
        setMessage(null);
        const formData = new FormData();
        formData.append('file', file);

        window.axios
            .post(importUrl, formData, { headers: { 'Content-Type': 'multipart/form-data' }, silent: true })
            .then((res) => {
                setStatus('idle');
                setOpen(false);
                reset();
                onImported?.(res.data);
            })
            .catch((error) => {
                setStatus('error');
                setMessage(error.response?.data?.message ?? common.import_failed);
            });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                if (!next) reset();
            }}
        >
            <DialogTrigger asChild>
                <Button type="button" variant="outline" size="sm">
                    <Upload className="size-4" />
                    {common.import ?? `Import ${label}`}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{common.import_title ?? `Import ${label}`}</DialogTitle>
                    <DialogDescription>{common.import_copy}</DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <Button asChild variant="secondary" size="sm">
                        <a href={templateUrl} download>
                            <Download className="size-4" />
                            {common.download_template}
                        </a>
                    </Button>

                    <input
                        ref={inputRef}
                        type="file"
                        accept=".csv"
                        onChange={(event) => setFile(event.target.files?.[0] ?? null)}
                        className="block w-full text-sm text-muted-foreground file:me-3 file:rounded-md file:border-0 file:bg-accent file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-accent-foreground"
                    />

                    {message && <p className="text-sm font-medium text-[var(--color-danger-strong)]">{message}</p>}
                </div>

                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="outline">
                            {common.cancel}
                        </Button>
                    </DialogClose>
                    <Button type="button" onClick={upload} disabled={!file || status === 'uploading'}>
                        {status === 'uploading' && <Loader2 className="size-4 animate-spin" />}
                        {common.upload}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
