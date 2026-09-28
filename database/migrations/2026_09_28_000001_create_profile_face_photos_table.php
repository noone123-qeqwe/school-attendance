<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_face_photos', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->longText('image_ciphertext');
            $table->string('mime_type', 32);
            $table->string('source_path', 1024);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_face_photos');
    }
};
