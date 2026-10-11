<?php

return [
    // How many queued emails the scheduler sends each minute (match your email provider's limit).
    'mail_broadcast_per_minute' => (int) env('MAIL_BROADCAST_PER_MINUTE', 60),

    // Shown only as a clickable credit in the website footer; the number and address are never printed on the page.
    'credit' => [
        'name' => 'Addigitaltech',
        'whatsapp' => '2348110581449',
        'website' => 'https://addigitaltech.com.ng',
    ],
];
