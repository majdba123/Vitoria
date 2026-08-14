import { useCallback, useEffect, useState } from 'react';

/**
 * Generic replacement for the fetch/loading/empty/error dance every admin
 * index.blade.php hand-rolled against /api/admin/{resource}. `params` is
 * re-fetched whenever its JSON representation changes (search/filter state).
 */
export function useAdminList(url, params = {}) {
    const [status, setStatus] = useState('loading'); // loading | ready | error
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState(null);
    const [errorMessage, setErrorMessage] = useState(null);

    const paramsKey = JSON.stringify(params);

    const load = useCallback(() => {
        setStatus('loading');
        window.axios
            .get(url, { params, silent: true })
            .then((res) => {
                setRows(res.data?.data ?? []);
                setMeta(res.data?.meta ?? null);
                setStatus('ready');
            })
            .catch((error) => {
                setErrorMessage(error.response?.data?.message ?? null);
                setStatus('error');
            });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [url, paramsKey]);

    useEffect(() => {
        load();
    }, [load]);

    return { status, rows, meta, errorMessage, reload: load };
}
