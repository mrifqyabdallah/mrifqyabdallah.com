<?php

namespace App\Models;

use App\Enums\BlogStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static Builder<Blog> published()
 * @method static Builder<Blog> archived()
 * @method static Builder<Blog> whereTagged(string $tag)
 * @method static Builder<Blog> search(string $term)
 */
class Blog extends Model
{
    /** @use HasFactory<\Database\Factories\BlogFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'status' => BlogStatus::class,
        'published_at' => 'date',
    ];

    /** @return HasMany<BlogView, $this> */
    public function views(): HasMany
    {
        return $this->hasMany(BlogView::class);
    }

    /** @param Builder<Blog> $query */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', BlogStatus::Published);
    }

    /** @param Builder<Blog> $query */
    #[Scope]
    protected function archived(Builder $query): void
    {
        $query->where('status', BlogStatus::Archived);
    }

    /** @param Builder<Blog> $query */
    #[Scope]
    protected function whereTagged(Builder $query, string $tag): void
    {
        $query->whereJsonContains('tags', $tag);
    }

    /** @param Builder<Blog> $query */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(function (Builder $q) use ($term) {
            $q->whereRaw(
                "to_tsvector('english', coalesce(title,'') || ' ' || coalesce(excerpt,'') || ' ' || coalesce(content,'')) @@ plainto_tsquery('english', ?)",
                [$term]
            )->orWhereJsonContains('tags', $term);
        });
    }

    public function getViewCountAttribute(): int
    {
        return $this->views()->count();
    }

    public function getViewsThisMonthAttribute(): int
    {
        return $this->views()
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    public function getViewsThisYearAttribute(): int
    {
        return $this->views()
            ->whereBetween('date', [now()->startOfYear(), now()->endOfYear()])
            ->count();
    }
}
