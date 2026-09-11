<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserReviewRequest;
use App\Models\UserReview;
use App\Support\AuditLogService;
use App\Support\UserReviewPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserReviewController extends Controller
{
    public function store(
        StoreUserReviewRequest $request,
        AuditLogService $auditLog,
        UserReviewPhotoService $photos,
    ): RedirectResponse {
        $user = $request->user();
        $review = UserReview::query()->firstOrNew(['user_id' => $user->getKey()]);
        $wasRecentlyCreated = ! $review->exists;
        $oldPhotoPath = filled($review->photo_path) ? (string) $review->photo_path : null;
        $oldSocialLinks = $review->social_links ?? [];
        $newPhotoPath = null;
        $photoPath = $oldPhotoPath;

        try {
            if ($request->hasFile('photo')) {
                $newPhotoPath = $photos->store($request->file('photo'));
                $photoPath = $newPhotoPath;
            } elseif ($request->boolean('remove_photo')) {
                $photoPath = null;
            }

            DB::transaction(function () use ($request, $review, $photoPath, $newPhotoPath): void {
                $socialLinks = collect($request->validated('social_links', []))
                    ->filter(fn (mixed $url): bool => filled($url))
                    ->all();

                $review->fill([
                    'rating' => (int) $request->validated('rating'),
                    'content' => $request->validated('content'),
                    'photo_path' => $photoPath,
                    'photo_uploaded_at' => $newPhotoPath !== null
                        ? now()
                        : ($photoPath === null ? null : $review->photo_uploaded_at),
                    'photo_privacy_confirmed_at' => $newPhotoPath !== null
                        ? now()
                        : ($photoPath === null ? null : $review->photo_privacy_confirmed_at),
                    'social_links' => $socialLinks !== [] ? $socialLinks : null,
                    'status' => UserReview::STATUS_PENDING,
                    'published_at' => null,
                    'moderated_by_user_id' => null,
                    'moderated_at' => null,
                ])->save();
            });
        } catch (Throwable $exception) {
            if ($newPhotoPath !== null) {
                $photos->delete($newPhotoPath);
            }

            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            report($exception);

            throw ValidationException::withMessages([
                'photo' => 'Nie udało się zapisać zdjęcia. Spróbuj ponownie albo wyślij opinię bez zdjęcia.',
            ])->errorBag('review');
        }

        if ($oldPhotoPath !== null && $oldPhotoPath !== $photoPath) {
            $photos->delete($oldPhotoPath);
        }

        $auditLog->record(
            $wasRecentlyCreated ? 'user_review.created' : 'user_review.updated',
            'user_review',
            $review->getKey(),
            $user,
            [
                'rating' => $review->rating,
                'photo_changed' => $oldPhotoPath !== $photoPath,
                'social_links_changed' => $oldSocialLinks !== ($review->social_links ?? []),
            ],
            $request,
        );

        $redirectUrl = $request->validated('return_to') === 'reviews'
            ? route('reviews.index', absolute: false).'#dodaj-opinie'
            : route('home', absolute: false).'#opinie';

        return redirect($redirectUrl)
            ->with('review_success', $wasRecentlyCreated
                ? 'Dziękujemy. Twoja opinia trafiła do zatwierdzenia.'
                : 'Opinia została zaktualizowana i ponownie trafiła do zatwierdzenia.');
    }
}
