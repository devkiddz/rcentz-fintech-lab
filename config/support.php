<?php

return [
    // Default support email. Override in .env as SUPPORT_EMAIL if needed.
    'email' => env('SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS')),
];


