<?php

namespace App\Services;

use InvalidArgumentException;

class FinancialTerminologyService
{
    public function __construct(private LocalizationService $localization)
    {
    }

    public function term(string $key, array $replace = [], ?string $locale = null): string
    {
        $term = data_get(config('financial-terminology.terms', []), $key);

        if (! is_array($term) || empty($term['identity']) || ! array_key_exists('fallback', $term)) {
            throw new InvalidArgumentException("Unknown protected financial term: {$key}");
        }

        return $this->localization->text(
            (string) $term['identity'],
            (string) $term['fallback'],
            $replace,
            $locale
        );
    }

    public function identities(): array
    {
        return collect(config('financial-terminology.terms', []))
            ->pluck('identity')
            ->filter()
            ->values()
            ->all();
    }

    public function count(): int
    {
        return count(config('financial-terminology.terms', []));
    }
}
