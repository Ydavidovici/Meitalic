<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'brand',
        'category',
        'description',
        'weight',
        'length',
        'width',
        'height',
        'price',
        'image',
        'inventory',
        'sku',
        'options',
        'slug',
        'is_featured',
    ];

    protected $casts = [
        'options'      => 'array',
        'is_featured'  => 'boolean',
    ];

    /**
     * Slug configuration (Spatie).
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * OrderItems relationship.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Fully qualified image URL.
     */
    public function getImageUrlAttribute(): string
    {
        $img = $this->image;

        // 1) Absolute URLs
        if (Str::startsWith($img, ['http://', 'https://'])) {
            return $img;
        }

        // 2) public/images placeholders
        if (Str::startsWith($img, 'images/')) {
            return asset($img);
        }

        // 3) storage/app/public uploads
        return asset('storage/' . $img);
    }

    /**
     * Free‑text search across name, brand, category.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name',     'like', "%{$term}%")
                ->orWhere('brand',    'like', "%{$term}%")
                ->orWhere('category', 'like', "%{$term}%");
        });
    }

    /**
     * Only featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
