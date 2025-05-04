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
        'price',
        'image',
        'inventory',
        'sku',
        'options',
        'slug',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    // A product can appear in many order items.
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Return a fully-qualified URL for the product’s image,
     * whether it’s a local storage path or an external URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        // if it's already an absolute URL, return it verbatim
        if (Str::startsWith($this->image, ['http://', 'https://'])) {
            return $this->image;
        }

        // otherwise assume it's in storage/app/public
        return asset('storage/'.$this->image);
    }

    /**
     * Scope a query to search across name, brand, and category.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%")
                ->orWhere('category', 'like', "%{$term}%");
        });
    }

}
