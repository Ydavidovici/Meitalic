<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductImage;

class MigrateProductImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:product-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move images from products.image into product_images table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Product::whereNotNull('image')
            ->chunk(100, function ($products) {
                foreach ($products as $product) {
                    // insert into product_images
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path'       => $product->image,
                    ]);

                    // clear old image field
                    $product->update(['image' => null]);

                    $this->info("✔️  Migrated image for product #{$product->id}");
                }
            });

        $this->info('✅ All product images migrated.');
    }
}
