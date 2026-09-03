<?php

use App\Models\User;
use App\Support\RankedWebSocketTicketService;
use Tests\TestCase;

uses(TestCase::class);

test('issues websocket urls with a ticket that resolves back to the same user', function () {
    config()->set('ranked.websocket_url', 'ws://localhost:8080/ranked');
    config()->set('ranked.websocket_ticket_store', 'array');
    config()->set('ranked.websocket_ticket_ttl_seconds', 180);

    $user = User::factory()->create();
    $service = app(RankedWebSocketTicketService::class);
    $url = $service->issueWebSocketUrlForUser($user);

    expect($url)->not->toBeNull();
    expect($url)->toStartWith('ws://localhost:8080/ranked?ticket=');

    parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);

    $resolvedUser = $service->resolveUserForTicket($query['ticket'] ?? null);

    expect($resolvedUser?->is($user))->toBeTrue();
});

test('returns null when websocket base url is missing', function () {
    config()->set('ranked.websocket_url', null);
    config()->set('ranked.websocket_ticket_store', 'array');

    $user = User::factory()->create();
    $service = app(RankedWebSocketTicketService::class);

    expect($service->issueWebSocketUrlForUser($user))->toBeNull();
});

test('returns null for unknown websocket tickets', function () {
    config()->set('ranked.websocket_ticket_store', 'array');

    $service = app(RankedWebSocketTicketService::class);

    expect($service->resolveUserForTicket('missing-ticket'))->toBeNull();
});
