import { useCallback, useEffect, useState } from 'react';

/**
 * Ports admin/dashboard.blade.php's data layer 1:1 onto React state: same
 * five endpoints, same "one failed request doesn't blank out data another
 * successful one already provided" resilience, but each slice tracks its
 * own idle/loading/ready/error status instead of manual DOM string-building.
 */
function useEndpoint(url, params) {
    const [status, setStatus] = useState('loading');
    const [data, setData] = useState(null);

    const fetchData = useCallback(() => {
        setStatus('loading');
        window.axios
            .get(url, { params, silent: true })
            .then((res) => {
                setData(res.data);
                setStatus('ready');
            })
            .catch(() => setStatus('error'));
    }, [url, JSON.stringify(params)]);

    useEffect(() => {
        fetchData();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return { status, data, refetch: fetchData };
}

export function useAdminDashboard() {
    const overview = useEndpoint('/api/admin/dashboard/overview');
    const categoryStats = useEndpoint('/api/admin/dashboard/vendor-category-stats');
    const users = useEndpoint('/api/admin/users', { page: 1 });
    const vendors = useEndpoint('/api/admin/vendors', { page: 1 });
    const products = useEndpoint('/api/admin/products', { page: 1, per_page: 8 });

    return { overview, categoryStats, users, vendors, products };
}
