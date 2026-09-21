<?php

namespace Database\Seeders;

use App\Models\HomepageVideo;
use Illuminate\Database\Seeder;

class HomepageVideoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            [
                'kind' => HomepageVideo::KIND_VIDEO,
                'title' => 'Kulisy kampanii — przykładowy materiał video',
                'youtube_url' => 'https://www.youtube.com/watch?v=Om5CIgd8huo',
                'duration_seconds' => 106,
                'sort_order' => 10,
            ],
            [
                'kind' => HomepageVideo::KIND_VIDEO,
                'title' => 'Krótki materiał promocyjny — przykład',
                'youtube_url' => 'https://www.youtube.com/watch?v=LtgrB5texpc',
                'duration_seconds' => 15,
                'sort_order' => 20,
            ],
            [
                'kind' => HomepageVideo::KIND_VIDEO,
                'title' => 'Rozmowa i praktyczne wskazówki — przykładowe video',
                'youtube_url' => 'https://www.youtube.com/watch?v=pXyN3ISsTRA',
                'duration_seconds' => 113,
                'sort_order' => 30,
            ],
            [
                'kind' => HomepageVideo::KIND_PODCAST,
                'title' => 'Rozmowa ekspercka — przykładowy podcast',
                'youtube_url' => 'https://www.youtube.com/watch?v=-tNE6wlavVQ',
                'duration_seconds' => 2485,
                'sort_order' => 10,
            ],
            [
                'kind' => HomepageVideo::KIND_PODCAST,
                'title' => 'Doświadczenia i dobre praktyki — podcast',
                'youtube_url' => 'https://www.youtube.com/watch?v=tag83vPA2GQ',
                'duration_seconds' => 2489,
                'sort_order' => 20,
            ],
            [
                'kind' => HomepageVideo::KIND_PODCAST,
                'title' => 'Co naprawdę się zmieniło? — przykładowa rozmowa',
                'youtube_url' => 'https://www.youtube.com/watch?v=I4eeM4nfqtQ',
                'duration_seconds' => 2580,
                'sort_order' => 30,
            ],
        ];

        foreach ($materials as $material) {
            HomepageVideo::query()->updateOrCreate(
                [
                    'kind' => $material['kind'],
                    'youtube_url' => $material['youtube_url'],
                ],
                [
                    'title' => $material['title'],
                    'duration_seconds' => $material['duration_seconds'],
                    'published_on' => '2026-05-25',
                    'sort_order' => $material['sort_order'],
                    'is_published' => true,
                ],
            );
        }
    }
}
