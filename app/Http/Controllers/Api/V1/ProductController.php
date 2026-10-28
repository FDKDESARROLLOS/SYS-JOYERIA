<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Services\PublicCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(PublicCatalogService $catalog): AnonymousResourceCollection
    {
        return ProductListResource::collection($catalog->paginateProducts());
    }

    public function show(string $slug, PublicCatalogService $catalog): ProductResource
    {
        return new ProductResource($catalog->findProductBySlug($slug));
    }
}
