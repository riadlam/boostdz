<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Content\StorefrontReviewsContent;
use App\Services\Content\StorefrontPlatformCardsContent;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function testimonials(StorefrontReviewsContent $reviews): JsonResponse
    {
        return response()->json($reviews->payload());
    }

    public function platformCards(StorefrontPlatformCardsContent $platformCards): JsonResponse
    {
        return response()->json($platformCards->payload());
    }
}
