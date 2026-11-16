<?php

namespace App\Services;

class ScoreCalculator
{
    private const WEIGHT_PERFORMANCE = 0.40;

    private const WEIGHT_ON_PAGE = 0.35;

    private const WEIGHT_TECH_STACK = 0.25;

    /**
     * Calculate composite score from individual category scores.
     *
     * @param  array<string, int>  $scores  Keyed by category (performance, on_page, tech_stack)
     */
    public function calculate(array $scores): int
    {
        $performanceScore = $scores['performance'] ?? 0;
        $onPageScore = $scores['on_page'] ?? 0;
        $techStackScore = $scores['tech_stack'] ?? 0;

        $weighted = ($performanceScore * self::WEIGHT_PERFORMANCE)
            + ($onPageScore * self::WEIGHT_ON_PAGE)
            + ($techStackScore * self::WEIGHT_TECH_STACK);

        return (int) round(min(100, max(0, $weighted)));
    }
}

