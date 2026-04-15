@component('mail::message')
{{ $body }}

@component('mail::button', ['url' => config('app.url')])
View in Social Mavin
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
