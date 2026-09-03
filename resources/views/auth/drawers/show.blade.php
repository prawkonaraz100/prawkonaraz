@if ($drawer === 'login')
    <x-site.login-drawer :open="false" />
@else
    <x-site.register-drawer :open="false" />
@endif
