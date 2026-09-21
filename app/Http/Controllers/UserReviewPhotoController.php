<?php

namespace App\Http\Controllers;

use App\Models\UserReview;
use App\Support\UserReviewPhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class UserReviewPhotoController extends Controller
{
    public function __invoke(Request $request, UserReview $review): Response
    {
        $user = $request->user();
        $isOwner = $user !== null && $review->user_id === $user->getKey();
        $isAdministrator = $user?->isAdministrator() === true;
        $isPublic = $review->status === UserReview::STATUS_APPROVED
            && $review->published_at !== null
            && $review->published_at->isPast()
            && $review->user()->whereNull('banned_at')->exists();

        abort_unless($isPublic || $isOwner || $isAdministrator, 404);
        abort_if(blank($review->photo_path), 404);

        $disk = Storage::disk(UserReviewPhotoService::DISK);
        $path = (string) $review->photo_path;
        abort_unless($disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="opinia-'.$review->getKey().'.jpg"',
            'Cache-Control' => $isPublic
                ? 'public, max-age=86400, stale-while-revalidate=604800'
                : 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
