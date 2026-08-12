<?php

namespace App\Services\Product;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\App;

/**
 * Product comparison (spec §29). Deliberately stateless: no
 * `product_comparisons` table exists, and none is needed — §29 explicitly
 * says comparison state may live in the client (session/localStorage) and
 * only asks the backend to compare a given set of ids. This service does
 * exactly that, once, per request.
 */
class ProductComparisonService
{
    public const MIN_PRODUCTS = 2;

    public const MAX_PRODUCTS = 4;

    /**
     * @param  list<int>  $productIds
     * @return array<string, mixed>
     *
     * @throws ProductComparisonException
     */
    public function compare(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        if (count($productIds) < self::MIN_PRODUCTS || count($productIds) > self::MAX_PRODUCTS) {
            throw new ProductComparisonException(__('product_comparison.count_invalid', [
                'min' => self::MIN_PRODUCTS,
                'max' => self::MAX_PRODUCTS,
            ]));
        }

        $products = Product::query()
            ->visible()
            ->whereIn('id', $productIds)
            ->with([
                'photos',
                'category',
                'vendor:id,store_name',
                'sharedDetail.agriculturalDetail',
                'sharedDetail.veterinaryDetail',
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($productIds)) {
            throw new ProductComparisonException(__('product_comparison.not_found'));
        }

        // Reordered to match the ids the caller asked for, not query order.
        $ordered = collect($productIds)->map(fn (int $id) => $products->get($id));

        $types = $ordered->map(fn (Product $product) => $product->category?->type)->unique();

        // "Comparison only between sensible product categories" (spec §29) —
        // agriculture and veterinary specs share almost nothing meaningful,
        // so mixing them would be comparing unrelated attributes.
        if ($types->count() > 1) {
            throw new ProductComparisonException(__('product_comparison.type_mismatch'));
        }

        return [
            'type' => $types->first(),
            'products' => $ordered->map(fn (Product $product) => $this->present($product))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Product $product): array
    {
        $detail = $product->sharedDetail;
        $locale = App::getLocale();
        $photo = $product->primaryPhoto();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'image_url' => $photo ? asset('storage/'.$photo->path) : asset('images/product-placeholder.svg'),
            'vendor_name' => $product->vendor?->store_name,
            'category_name' => $product->category?->name,
            'manufacturer' => $locale === 'ar'
                ? ($detail?->manufacturer_name_ar ?: $detail?->manufacturer_name_en)
                : ($detail?->manufacturer_name_en ?: $detail?->manufacturer_name_ar),
            'brand' => $locale === 'ar'
                ? ($detail?->brand_name_ar ?: $detail?->brand_name_en)
                : ($detail?->brand_name_en ?: $detail?->brand_name_ar),
            'price' => (float) $product->price,
            'discounted_price' => $product->getDiscountedPrice(),
            'has_discount' => $product->hasActiveDiscount(),
            'package_size' => $detail?->package_size,
            'package_unit' => $detail?->package_unit,
            'rating_average' => round((float) ($product->reviews_avg_rating ?? 0), 2),
            'rating_count' => (int) $product->reviews_count,
            'available' => $product->quantity > 0,
            'quantity' => $product->quantity,
            'specs' => $this->specsFor($product),
        ];
    }

    /**
     * Only the specs relevant to the product's own type — never a field that
     * means nothing for the category being compared (spec §29).
     *
     * @return array<string, mixed>
     */
    private function specsFor(Product $product): array
    {
        $categoryType = $product->category?->type;

        if ($categoryType === Category::TYPE_VETERINARY) {
            $vet = $product->veterinaryDetail();

            return [
                'active_ingredients' => $vet?->active_ingredients,
                'concentration' => $vet?->concentration,
                'dosage_form' => $vet?->dosage_form,
                'routes_of_administration' => $vet?->routes_of_administration,
                'target_species' => $vet?->target_species,
            ];
        }

        if ($categoryType === Category::TYPE_AGRICULTURE) {
            $agri = $product->agriculturalDetail();

            return [
                'agricultural_product_type' => $agri?->agricultural_product_type,
                'active_ingredients' => $agri?->active_ingredients,
                'formulation' => $agri?->formulation,
                'application_methods' => $agri?->application_methods,
                'target_crops' => $agri?->target_crops,
                'target_pests' => $agri?->target_pests,
            ];
        }

        return [];
    }
}
