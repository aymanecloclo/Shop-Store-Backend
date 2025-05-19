<?php

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
        Schema::create('carts', function (Blueprint $table) {
            $table->id(); // Ceci crée un 'id' BIGINT UNSIGNED AUTO_INCREMENT
            
            // Relation avec la table users (en supposant qu'elle utilise 'id' comme clé primaire)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                  ->references('id') // Ici on référence explicitement la colonne 'id' de la table users
                  ->on('users')
                  ->onDelete('cascade');
            
            // Pour les utilisateurs non connectés
            $table->string('session_id')->nullable()->unique();
            
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', ['active', 'converted', 'abandoned'])->default('active');
            
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            
            // Relation avec carts
            $table->unsignedBigInteger('cart_id');
            $table->foreign('cart_id')
                  ->references('id')
                  ->on('carts')
                  ->onDelete('cascade');
            
            // Relation avec products
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
            
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->json('options')->nullable();
            
            $table->timestamps();
            
            // Contrainte d'unicité
            $table->unique(['cart_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};