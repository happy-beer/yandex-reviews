<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('place_id')->constrained()->cascadeOnDelete();
                $table->string('external_id');
                $table->string('author_name')->nullable();
                $table->text('text')->nullable();
                $table->unsignedTinyInteger('rating');
                $table->timestamp('published_at')->nullable();
                $table->boolean('has_owner_reply')->default(false);
                $table->text('owner_reply_text')->nullable();
                $table->timestamp('owner_replied_at')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->index('place_id');
                $table->index('published_at');
                $table->index('rating');
                $table->unique(['place_id', 'external_id']);
            });

            return;
        }

        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'place_id')) {
                $table->foreignId('place_id')->nullable()->after('id')->constrained('places')->cascadeOnDelete();
            }

            if (!Schema::hasColumn('reviews', 'external_id')) {
                $table->string('external_id')->nullable()->after('place_id');
            }

            if (!Schema::hasColumn('reviews', 'author_name')) {
                $table->string('author_name')->nullable()->after('external_id');
            }

            if (!Schema::hasColumn('reviews', 'text')) {
                $table->text('text')->nullable()->after('author_name');
            }

            if (!Schema::hasColumn('reviews', 'rating')) {
                $table->unsignedTinyInteger('rating')->default(0)->after('text');
            }

            if (!Schema::hasColumn('reviews', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('rating');
            }

            if (!Schema::hasColumn('reviews', 'has_owner_reply')) {
                $table->boolean('has_owner_reply')->default(false)->after('published_at');
            }

            if (!Schema::hasColumn('reviews', 'owner_reply_text')) {
                $table->text('owner_reply_text')->nullable()->after('has_owner_reply');
            }

            if (!Schema::hasColumn('reviews', 'owner_replied_at')) {
                $table->timestamp('owner_replied_at')->nullable()->after('owner_reply_text');
            }

            if (!Schema::hasColumn('reviews', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('owner_replied_at');
            }

            if (!Schema::hasColumn('reviews', 'created_at') && !Schema::hasColumn('reviews', 'updated_at')) {
                $table->timestamps();
            }
        });

        $this->backfillPlaceIds();
        $this->backfillExternalIds();
        $this->ensureIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }

    private function backfillPlaceIds(): void
    {
        if (!Schema::hasColumn('reviews', 'place_id') || !Schema::hasColumn('reviews', 'user_id')) {
            return;
        }

        $rowsWithoutPlace = DB::table('reviews')
            ->select('user_id')
            ->whereNull('place_id')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($rowsWithoutPlace as $userId) {
            $setting = Schema::hasTable('settings')
                ? DB::table('settings')
                    ->where('user_id', $userId)
                    ->where('key', 'yandex_url')
                    ->first()
                : null;

            $placeId = DB::table('places')->insertGetId([
                'user_id' => $userId,
                'name' => 'Imported place',
                'source_url' => $setting->value ?? 'https://yandex.ru/maps/',
                'external_id' => null,
                'rating' => null,
                'reviews_count' => 0,
                'last_synced_at' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('reviews')
                ->where('user_id', $userId)
                ->whereNull('place_id')
                ->update(['place_id' => $placeId]);
        }
    }

    private function backfillExternalIds(): void
    {
        if (!Schema::hasColumn('reviews', 'external_id')) {
            return;
        }

        $reviews = DB::table('reviews')
            ->select('id')
            ->whereNull('external_id')
            ->get();

        foreach ($reviews as $review) {
            DB::table('reviews')
                ->where('id', $review->id)
                ->update(['external_id' => 'legacy-' . $review->id]);
        }
    }

    private function ensureIndexes(): void
    {
        foreach (['place_id', 'published_at', 'rating'] as $column) {
            try {
                Schema::table('reviews', function (Blueprint $table) use ($column) {
                    $table->index($column);
                });
            } catch (Throwable) {
                // The index can already exist in upgraded environments.
            }
        }

        try {
            Schema::table('reviews', function (Blueprint $table) {
                $table->unique(['place_id', 'external_id']);
            });
        } catch (Throwable) {
            // The index can already exist in upgraded environments.
        }
    }
};
