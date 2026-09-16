<?php

namespace App\Support;

final class NewsroomTaxonomyContract
{
    public const CATEGORY_VERSION = 'v1';

    /**
     * @return list<array{
     *     slug: string,
     *     name: string,
     *     position: int,
     *     is_active: bool,
     *     description: null,
     *     seo_title: null,
     *     seo_description: null
     * }>
     */
    public static function categories(): array
    {
        return [
            [
                'slug' => 'prawo-jazdy',
                'name' => 'Prawo jazdy',
                'position' => 10,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'egzaminy',
                'name' => 'Egzaminy',
                'position' => 20,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'przepisy',
                'name' => 'Przepisy',
                'position' => 30,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'word',
                'name' => 'WORD',
                'position' => 40,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'kierowcy',
                'name' => 'Kierowcy',
                'position' => 50,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
            [
                'slug' => 'osk',
                'name' => 'OSK',
                'position' => 60,
                'is_active' => true,
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
            ],
        ];
    }
}
