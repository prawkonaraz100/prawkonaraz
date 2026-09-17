<?php

namespace App\Support;

final class NewsroomPublicGate
{
    public function enabled(): bool
    {
        return (bool) config('newsroom.public_enabled', false);
    }

    public function disabled(): bool
    {
        return ! $this->enabled();
    }
}
