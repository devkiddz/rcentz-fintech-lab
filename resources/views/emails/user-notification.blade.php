@component('mail::message')
# Hello {{ $user->name }}!

{!! nl2br(e($content)) !!}

@if($template->type === 'purchase')
@component('mail::panel')
**Purchase Details**
Thank you for choosing {{ site_name() }} for your vehicle purchase. We're committed to providing you with the best electric vehicle experience.
@endcomponent
@endif

@component('mail::button', ['url' => route('dashboard')])
Visit Your Dashboard
@endcomponent

Best regards,<br>
**{{ site_name() }} Team**

---

@component('mail::subcopy')
If you have any questions or need assistance, please don't hesitate to contact our support team. We're here to help!

**{{ site_name() }}**<br>
Email: {{ site_email() }}<br>
@endcomponent
@endcomponent
