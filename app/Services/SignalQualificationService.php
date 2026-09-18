<?php

namespace App\Services;

class SignalQualificationService
{
    public function qualify(array $analysis): array
    {
        $direction = (string) ($analysis['direction'] ?? 'neutral');
        $confluence = (float) ($analysis['confluence_score'] ?? 0);
        $quality = (float) ($analysis['data_quality'] ?? 0);

        if ($direction === 'neutral' || $quality < 30 || $confluence < 40) {
            return $this->result('reject', 'rejected', false, false, $this->reason($direction, $confluence, $quality));
        }

        if ($confluence < 52) {
            return $this->result('watch', 'watch', false, false, 'Directional evidence exists, but confluence is below Signal construction threshold.');
        }

        if ($confluence < 64) {
            return $this->result('qualified', 'moderate', true, false, 'Setup qualifies for a Signal candidate but requires stronger confirmation for automatic generation.');
        }

        if ($confluence < 78) {
            return $this->result('qualified', 'strong', true, true, 'Strong multi-factor confluence qualifies for automatic Signal generation.');
        }

        return $this->result('qualified', 'very_strong', true, true, 'Very strong multi-factor confluence qualifies for automatic Signal generation.');
    }

    private function result(string $result, string $strength, bool $eligible, bool $autoEligible, string $reason): array
    {
        return [
            'result' => $result,
            'strength' => $strength,
            'eligible_for_signal' => $eligible,
            'eligible_for_auto_generation' => $autoEligible,
            'reason' => $reason,
        ];
    }

    private function reason(string $direction, float $confluence, float $quality): string
    {
        if ($direction === 'neutral') {
            return 'Directional evidence is balanced; no trade direction is justified.';
        }
        if ($quality < 30) {
            return 'Market data quality is insufficient for a reliable Signal.';
        }
        return 'Confluence score is below the minimum Signal threshold.';
    }
}
