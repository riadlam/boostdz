<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Pages\ManageLandingReviews;
use App\Filament\Resources\Testimonials\TestimonialResource;
use Filament\Resources\Pages\ListRecords;

class ListTestimonials extends ListRecords
{
    protected static string $resource = TestimonialResource::class;

    public function mount(): void
    {
        $this->redirect(ManageLandingReviews::getUrl());
    }
}
