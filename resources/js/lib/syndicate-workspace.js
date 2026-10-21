import SyndicateLayout from '@/Layouts/SyndicateLayout';
import MinistryLayout from '@/Layouts/MinistryLayout';

export const MINISTRY_SECTORS = ['combined', 'agriculture', 'veterinary'];

/**
 * The syndicate pages serve two workspaces: a syndicate account (its own
 * domain) and the Ministry (agriculture, veterinary or both, chosen with
 * `?sector=`). This resolves the layout, API base, request params and route
 * names for whichever one is rendering, keeping the sector on every link.
 */
export function syndicateWorkspace(workspace, sector = 'combined') {
    const isMinistry = workspace === 'ministry';
    const key = isMinistry ? 'ministry' : 'syndicate';
    const sectorParam = isMinistry && sector && sector !== 'combined' ? { sector } : {};

    return {
        isMinistry,
        key,
        sector: isMinistry ? sector || 'combined' : null,
        Layout: isMinistry ? MinistryLayout : SyndicateLayout,
        apiBase: `/api/${key}`,
        params: isMinistry ? { sector: sector || 'combined' } : {},
        href: (name, id) => (id === undefined ? route(`${key}.${name}`, sectorParam) : route(`${key}.${name}`, { id, ...sectorParam })),
    };
}
