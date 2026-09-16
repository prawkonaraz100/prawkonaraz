<?php

namespace App\Support;

use App\Models\ContentHomePlacement;
use JsonException;

final class ContentHomePlacementEditToken
{
    /**
     * Deterministic optimistic-lock token for one placement row.
     *
     * @throws JsonException
     */
    public function make(ContentHomePlacement $placement): string
    {
        $state = $placement->getRawOriginal();
        ksort($state);

        return hash('sha256', json_encode(
            $state,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }
}
