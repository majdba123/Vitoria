import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Generic replacement for the fetch/loading/empty/error dance every admin
 * index.blade.php hand-rolled against /api/admin/{resource}. `params` is
 * re-fetched whenever its JSON representation changes (search/filter state).
 * Only the most recent request may update state, so a slow response for an
 * older filter can never overwrite the rows of the filter now on screen.
 */
export function useAdminList(url, params = {}) {
    const [status, setStatus] = useState('loading'); // loading | ready | error
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState(null);
    const [errorMessage, setErrorMessage] = useState(null);
    const latestRequest = useRef(0);

    const paramsKey = JSON.stringify(params);

    const load = useCallback(() => {
        const requestId = ++latestRequest.current;
        setStatus('loading');
        window.axios
            .get(url, { params, silent: true })
            .then((res) => {
                if (requestId !== latestRequest.current) return;
                setRows(res.data?.data ?? []);
                setMeta(res.data?.meta ?? null);
                setStatus('ready');
            })
            .catch((error) => {
                if (requestId !== latestRequest.current) return;
                setErrorMessage(error.response?.data?.message ?? null);
                setStatus('error');
            });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [url, paramsKey]);

    useEffect(() => {
        load();

        return () => {
            latestRequest.current += 1;
        };
    }, [load]);

    return { status, rows, meta, errorMessage, reload: load };
}
