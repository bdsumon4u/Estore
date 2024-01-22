<?php

use App\Models\Shop\Attribute;
use App\Models\Shop\Option;
use App\Models\Shop\Product;
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
        Schema::create('variations', function (Blueprint $table) {
            $table->foreignIdFor(Attribute::class);
            $table->foreignIdFor(Option::class)->nullable();
            $table->foreignIdFor(Product::class);
            $table->string('value')->nullable();

            $table->unique(['attribute_id', 'option_id', 'product_id']);
            $table->unique(['attribute_id', 'product_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variations');
    }
};
