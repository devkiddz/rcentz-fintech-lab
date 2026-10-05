<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        if (DB::connection()->getDriverName() !== 'mysql') { throw new RuntimeException('This timestamp correction requires MySQL/MariaDB.'); }
        // Explicit DEFAULT prevents the first TIMESTAMP column's implicit ON UPDATE behavior.
        DB::statement('ALTER TABLE trade_positions MODIFY opened_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $extra=DB::table('information_schema.COLUMNS')->whereRaw('TABLE_SCHEMA = DATABASE()')->where('TABLE_NAME','trade_positions')->where('COLUMN_NAME','opened_at')->value('EXTRA');
        if (stripos((string)$extra,'on update')!==false) { throw new RuntimeException('Opening timestamp still updates automatically.'); }
    }
    public function down(): void {
        // Deliberately keep opening times stable rather than restoring the corrupting attribute.
    }
};
