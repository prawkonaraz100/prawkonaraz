<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('friendly verification email keeps the signed link expiry and plaintext fallback', function () {
    $this->freezeTime();
    $user = User::factory()->unverified()->create();
    config(['auth.verification.expire' => 60]);
    $mail = (new VerifyEmail)->toMail($user);
    $url = $mail->actionUrl;

    expect($mail->subject)->toBe('Potwierdź adres e-mail')
        ->and($mail->view)->toBe(['html' => 'emails.auth.verify-email', 'text' => 'emails.auth.verify-email-text'])
        ->and($mail->viewData['verificationUrl'])->toBe($url)
        ->and($mail->viewData['expiryLabel'])->toBe('1 godzinie')
        ->and(URL::hasValidSignature(Request::create($url)))->toBeTrue();

    $html = (string) $mail->render();
    expect($html)->toContain('Potwierdź swój adres e-mail', 'Dziękujemy za rejestrację', 'Masz problem z przyciskiem?', 'Link wygasa po 1 godzinie.', 'data:image/png;base64,')
        ->not->toContain('<script', '<svg', '24 godzinach');
    preg_match_all('/href="([^"]+)"/', $html, $matches);
    $links = array_map(fn ($href) => html_entity_decode($href, ENT_QUOTES), $matches[1]);
    expect(array_count_values($links)[$url])->toBe(2);

    $text = view($mail->view['text'], $mail->viewData)->render();
    expect($text)->toContain($url, 'Link wygasa po 1 godzinie.', 'Jeśli to nie Ty');
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    expect((int) $query['expires'])->toBe(now()->addMinutes(60)->timestamp);

    // Exercise the real notification channel without sending anything externally.
    config(['mail.default' => 'array', 'mail.mailers.array' => ['transport' => 'array']]);
    Notification::sendNow($user, new VerifyEmail);
    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
    expect($email->getHtmlBody())->toContain('cid:', 'Potwierdź swój adres e-mail')
        ->and($email->getTextBody())->toContain($url)
        ->and($email->getAttachments())->toHaveCount(6);
    foreach ($email->getAttachments() as $attachment) {
        expect($attachment->getDisposition())->toBe('inline');
    }

    config(['auth.verification.expire' => 30]);
    expect((new VerifyEmail)->toMail($user)->viewData['expiryLabel'])->toBe('30 minutach');
});
