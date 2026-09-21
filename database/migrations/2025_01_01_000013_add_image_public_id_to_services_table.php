<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Cloudinary's asset identifier — needed to delete/replace an image
            // via their API. `image` now stores the full secure_url directly.
            $table->string('image_public_id')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('image_public_id');
        });
    }
};
