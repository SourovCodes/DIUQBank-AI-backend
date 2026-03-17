<?php

$parseList = static fn (string $value): array => array_values(array_filter(array_map(
    static fn (string $item): string => trim($item),
    explode(',', $value),
)));

return [
    'emails' => $parseList((string) env('FILAMENT_ADMIN_EMAILS', '')),
    'usernames' => $parseList((string) env('FILAMENT_ADMIN_USERNAMES', '')),
];
