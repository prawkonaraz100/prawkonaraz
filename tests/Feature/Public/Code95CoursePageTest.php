<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the code 95 page explains qualification paths instead of an empty placeholder', function () {
    $this->get(route('public.code95'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Public/Code95'));
});
