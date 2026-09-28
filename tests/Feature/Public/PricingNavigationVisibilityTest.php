<?php

use App\Models\User;
use App\Support\PaymentRequirementService;
use App\Support\PublicNavigation;

test('top navigation follows the global payment requirement', function () {
    $paymentRequirement = app(PaymentRequirementService::class);
    $navigation = app(PublicNavigation::class);

    expect($paymentRequirement->requiresPayment())->toBeTrue()
        ->and(array_column($navigation->data(null)['top'], 'label'))->toContain('Cennik');

    expect($paymentRequirement->setRequiresPayment(false))->toBeTrue()
        ->and(array_column($navigation->data(null)['top'], 'label'))->not->toContain('Cennik');

    expect($paymentRequirement->setRequiresPayment(true))->toBeTrue()
        ->and(array_column($navigation->data(null)['top'], 'label'))->toContain('Cennik');
});

test('top navigation offers learning only to signed-in users', function () {
    $navigation = app(PublicNavigation::class);
    $guestLinks = $navigation->data(null)['top'];
    $userLinks = $navigation->data(User::factory()->make())['top'];

    expect(array_column($guestLinks, 'label'))->not->toContain('Nauka')
        ->and(array_column($userLinks, 'label'))->toContain('Nauka')
        ->and(collect($userLinks)->firstWhere('label', 'Nauka')['href'])->toBe('/nauka');
});
