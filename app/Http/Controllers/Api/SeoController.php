<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public sitemap.xml and robots.txt (spec §39).
 *
 * Product/category URLs stay id-based (matching the existing public routes
 * in routes/api.php) — introducing slugs would be a routing change with no
 * requirement behind it, and the sitemap only needs a URL that resolves.
 */
class SeoController extends Controller
{
    public function sitemap(Request $request): Response
    {
        $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');

        $urls = collect();

        $urls->push(['loc' => $baseUrl.'/', 'lastmod' => now()->toDateString()]);

        Page::query()->where('is_published', true)->get(['slug', 'updated_at'])->each(
            fn (Page $page) => $urls->push([
                'loc' => $baseUrl.'/pages/'.$page->slug,
                'lastmod' => $page->updated_at?->toDateString(),
            ])
        );

        Category::query()->get(['id', 'updated_at'])->each(
            fn (Category $category) => $urls->push([
                'loc' => $baseUrl.'/categories/'.$category->id,
                'lastmod' => $category->updated_at?->toDateString(),
            ])
        );

        Product::query()->where('is_active', true)->where('status', Product::STATUS_APPROVED)
            ->get(['id', 'updated_at'])->each(
                fn (Product $product) => $urls->push([
                    'loc' => $baseUrl.'/products/'.$product->id,
                    'lastmod' => $product->updated_at?->toDateString(),
                ])
            );

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function robots(Request $request): Response
    {
        $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');

        $lines = [
            'User-agent: *',
            'Allow: /',
            // Role-gated dashboards and API namespaces: real crawlers only ever
            // hit a login redirect here, so keep them out of the crawl budget.
            'Disallow: /admin/',
            'Disallow: /vendor/',
            'Disallow: /syndicate/',
            'Disallow: /employee/',
            'Disallow: /api/',
            // Checkout/profile/order pages are per-user and carry no indexable
            // content of their own; they already ship <meta name="robots"
            // content="noindex"> too, this just saves the crawl budget.
            'Disallow: /checkout',
            'Disallow: /profile',
            'Disallow: /orders/',
            'Sitemap: '.$baseUrl.'/sitemap.xml',
        ];

        return response(implode("\n", $lines)."\n", 200)->header('Content-Type', 'text/plain');
    }
}
