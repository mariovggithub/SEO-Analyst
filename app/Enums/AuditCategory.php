<?php

namespace App\Enums;

enum AuditCategory: string
{
    case Performance = 'performance';
    case TechStack = 'tech_stack';
    case OnPage = 'on_page';

    public function label(): string
    {
        return match ($this) {
            self::Performance => 'Performance',
            self::TechStack => 'Tech Stack',
            self::OnPage => 'On-Page SEO',
        };
    }
}

