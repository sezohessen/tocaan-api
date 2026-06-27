<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Catalog
 */
class ProductController extends Controller
{
    /**
     * Browse the catalog
     *
     * Paginated list of active products.
     *
     * @queryParam name string Filter by partial name. Example: keyboard
     * @queryParam sort_by[price] string Sort by a column (asc|desc). Example: asc
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::filter($request->all())
            ->active()
            ->latest()
            ->paginate((int) $request->integer('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        return ProductResource::make($product);
    }
}
