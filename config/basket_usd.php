<?php
return [
 'enabled'=>(bool)env('BASKET_USD_FEED_ENABLED',false),
 'key'=>env('COINMARKETCAP_API_KEY',''),
 'currencies'=>['EUR','GBP','JPY'],
 'daily_limit'=>360,'monthly_limit'=>12000,
 'minimum_interval_seconds'=>840,
 'max_age_seconds'=>1800,
 'window_seconds'=>3600,
 'minimum_window_seconds'=>1800,
 'scale_percent'=>0.25,
];
