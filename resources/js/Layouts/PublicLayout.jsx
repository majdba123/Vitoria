import { PublicHeader } from '@/Components/public/PublicHeader';
import { PublicFooter } from '@/Components/public/PublicFooter';
import { SeoHead } from '@/Components/public/SeoHead';
import { CartModal } from '@/Components/workspace/CartModal';
import { CartProvider } from '@/hooks/use-cart';
import { FavouritesProvider } from '@/hooks/use-favourites';

export default function PublicLayout({ title, description, canonical, noindex, image, type, jsonLd, children }) {
    return (
        <FavouritesProvider>
            <CartProvider>
                <SeoHead title={title} description={description} canonical={canonical} noindex={noindex} image={image} type={type} jsonLd={jsonLd} />
                <PublicHeader />
                <main id="main-content" className="relative isolate">
                    {children}
                </main>
                <PublicFooter />
                <CartModal />
            </CartProvider>
        </FavouritesProvider>
    );
}
