<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete();
            $table->string('marketplace', 20)->default('live');
            $table->string('source', 40)->default('auto_analysis');
            $table->string('direction', 20)->nullable();
            $table->string('timeframe', 20)->nullable();
            $table->string('status', 30)->default('candidate');
            $table->string('strength', 30)->nullable();
            $table->decimal('confluence_score', 6, 2)->nullable();
            $table->decimal('entry_min', 24, 8)->nullable();
            $table->decimal('entry_max', 24, 8)->nullable();
            $table->decimal('stop_loss', 24, 8)->nullable();
            $table->decimal('risk_reward', 10, 4)->nullable();
            $table->text('rationale')->nullable();
            $table->json('analysis_snapshot')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['stock_id', 'marketplace', 'status'], 'signals_instrument_market_status_idx');
            $table->index(['status', 'generated_at'], 'signals_status_generated_idx');
        });

        Schema::create('signal_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')->constrained('signals')->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->decimal('price', 24, 8);
            $table->string('status', 20)->default('pending');
            $table->timestamp('hit_at')->nullable();
            $table->timestamps();

            $table->unique(['signal_id', 'sequence'], 'signal_targets_signal_sequence_unique');
        });

        Schema::create('signal_analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')->nullable()->constrained('signals')->nullOnDelete();
            $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete();
            $table->string('marketplace', 20);
            $table->string('trigger', 30)->default('scheduled');
            $table->string('source', 80)->default('stock_analysis');
            $table->string('timeframe', 20)->nullable();
            $table->string('result', 40)->default('observed');
            $table->decimal('confluence_score', 6, 2)->nullable();
            $table->decimal('market_price', 24, 8)->nullable();
            $table->json('context')->nullable();
            $table->json('conclusion')->nullable();
            $table->timestamp('analyzed_at');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['stock_id', 'marketplace', 'analyzed_at'], 'signal_analysis_instrument_market_time_idx');
            $table->index(['signal_id', 'analyzed_at'], 'signal_analysis_signal_time_idx');
        });

        Schema::create('signal_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')->constrained('signals')->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('change_source', 30)->default('automatic');
            $table->json('previous_values')->nullable();
            $table->json('new_values');
            $table->text('reason')->nullable();
            $table->foreignId('analysis_run_id')->nullable()->constrained('signal_analysis_runs')->nullOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['signal_id', 'revision_number'], 'signal_revisions_signal_revision_unique');
        });

        Schema::create('signal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')->constrained('signals')->cascadeOnDelete();
            $table->foreignId('signal_target_id')->nullable()->constrained('signal_targets')->nullOnDelete();
            $table->foreignId('analysis_run_id')->nullable()->constrained('signal_analysis_runs')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['signal_id', 'type', 'occurred_at'], 'signal_events_signal_type_time_idx');
        });

        Schema::create('signal_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')->constrained('signals')->cascadeOnDelete();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 30)->default('automatic');
            $table->boolean('membership_rules_applied')->default(true);
            $table->text('reason')->nullable();
            $table->json('audience_snapshot')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();

            $table->index(['signal_id', 'mode'], 'signal_distributions_signal_mode_idx');
        });

        Schema::create('signal_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->nullable()->constrained('signal_distributions')->nullOnDelete();
            $table->foreignId('signal_id')->constrained('signals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 30)->default('membership');
            $table->timestamp('delivered_at');
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['signal_id', 'user_id'], 'signal_deliveries_signal_user_unique');
            $table->index(['user_id', 'delivered_at'], 'signal_deliveries_user_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_deliveries');
        Schema::dropIfExists('signal_distributions');
        Schema::dropIfExists('signal_events');
        Schema::dropIfExists('signal_revisions');
        Schema::dropIfExists('signal_analysis_runs');
        Schema::dropIfExists('signal_targets');
        Schema::dropIfExists('signals');
    }
};
