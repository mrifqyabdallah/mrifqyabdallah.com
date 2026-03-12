<?php

namespace App\Enums;

enum BlogStatus: string
{
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }
}
