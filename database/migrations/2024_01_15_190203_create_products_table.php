<?php

use App\Models\Shop\Brand;
use App\Models\Branch;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->index();
            $table->foreignIdFor(Brand::class)->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->longText('description')->nullable();

            $table->unsignedBigInteger('security_stock')->default(0);

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_enabled')->default(false);

            $table->decimal('old_price', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();

            $table->enum('type', ['deliverable', 'downloadable'])->nullable();
            $table->boolean('backorder')->default(false);

            $table->string('seo_title', 60)->nullable();
            $table->string('seo_description', 160)->nullable();

            $this->addShippingFields($table);

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'name']);
            $table->unique(['owner_id', 'slug']);
            $table->unique(['owner_id', 'sku']);
            $table->unique(['owner_id', 'barcode']);
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignIdFor(Category::class)->index();
            $table->foreignIdFor(Product::class)->index();

            $table->primary(['category_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
    }

    private function addShippingFields(Blueprint $table): void
    {
        $table->boolean('requires_shipping')->default(false);
        $table->unsignedDecimal('weight_value', 10, 5)->nullable()->default(0.00);
        $table->string('weight_unit')->default('kg');
        $table->unsignedDecimal('height_value', 10, 5)->nullable()->default(0.00);
        $table->string('height_unit')->default('cm');
        $table->unsignedDecimal('width_value', 10, 5)->nullable()->default(0.00);
        $table->string('width_unit')->default('cm');
        $table->unsignedDecimal('depth_value', 10, 5)->nullable()->default(0.00);
        $table->string('depth_unit')->default('cm');
        $table->unsignedDecimal('volume_value', 10, 5)->nullable()->default(0.00);
        $table->string('volume_unit')->default('l');
    }
};
