<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Pages\ManageLandingReviews;
use App\Filament\Resources\Testimonials\TestimonialResource;
use Filament\Resources\Pages\EditRecord;

class EditTestimonial extends EditRecord
{
    protected static string $resource = TestimonialResource::class;

    protected function getRedirectUrl(): string
    {
        return ManageLandingReviews::getUrl();
    }
}
