<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(
            config('rate.table_names.ratings'),
            function (Blueprint $table): void {
                config('rate.uuids')?$table->uuid('uuid'):   $table->bigIncrements('id');
                $table->unsignedBigInteger(config('rate.column_names.user_foreign_key'))->index()->comment('user_id');
                $table->morphs('ratable');
                $table->timestamps();
                $table->unique([config('rate.column_names.user_foreign_key'), 'ratable_type', 'ratable_id']);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('rate.table_names.ratings'));
    }
}
