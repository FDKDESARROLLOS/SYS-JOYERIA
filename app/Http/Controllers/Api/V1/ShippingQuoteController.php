<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ShippingQuoteRequest;
use App\Http\Resources\ShippingQuoteResource;
use App\Services\ShippingQuoteService;

class ShippingQuoteController extends Controller
{
    public function __invoke(
        ShippingQuoteRequest $request,
        ShippingQuoteService $shippingQuoteService,
    ): ShippingQuoteResource {
        return new ShippingQuoteResource(
            $shippingQuoteService->quote($request->validated()),
        );
    }
}
