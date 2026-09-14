<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if (!Schema::hasColumn('wallets','reserved_balance')) Schema::table('wallets', function(Blueprint $t){$t->decimal('reserved_balance',15,2)->default(0)->after('balance');});
  if (!Schema::hasTable('financial_activities')) Schema::create('financial_activities', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete(); $t->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete(); $t->foreignId('internal_transfer_id')->nullable()->constrained()->nullOnDelete();
   $t->string('reference',120)->nullable()->index(); $t->string('event_type',80)->index(); $t->string('title',180); $t->text('description')->nullable(); $t->string('status',40)->nullable()->index(); $t->enum('direction',['credit','debit'])->nullable(); $t->decimal('amount',15,2)->nullable();
   $t->decimal('balance_before',15,2)->nullable(); $t->decimal('balance_after',15,2)->nullable(); $t->decimal('available_before',15,2)->nullable(); $t->decimal('available_after',15,2)->nullable(); $t->decimal('reserved_before',15,2)->nullable(); $t->decimal('reserved_after',15,2)->nullable();
   $t->string('actor_type',30)->default('system'); $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete(); $t->json('metadata')->nullable(); $t->timestamps(); $t->index(['user_id','created_at']);
  });
  DB::table('wallets')->orderBy('id')->chunkById(100,function($wallets){foreach($wallets as $w){$sum=DB::table('wallet_transactions')->where('wallet_id',$w->id)->where('type','withdrawal')->where('status','pending')->sum('amount'); DB::table('wallets')->where('id',$w->id)->update(['reserved_balance'=>$sum]);}});
  DB::table('wallet_transactions')->whereNull('reference_id')->orderBy('id')->chunkById(200,function($rows){foreach($rows as $r) DB::table('wallet_transactions')->where('id',$r->id)->update(['reference_id'=>'WT-'.$r->id]);});
  if (DB::table('financial_activities')->count()===0) DB::table('wallet_transactions')->join('wallets','wallets.id','=','wallet_transactions.wallet_id')->select('wallet_transactions.*','wallets.user_id')->orderBy('wallet_transactions.id')->chunkById(200,function($rows){$insert=[]; foreach($rows as $r){$dir=$r->direction; if(!$dir)$dir=(in_array($r->type,['deposit','refund','dividend'],true)||($r->type==='investment'&&str_starts_with((string)$r->description,'Sale of'))) ? 'credit':'debit'; $insert[]=['user_id'=>$r->user_id,'wallet_id'=>$r->wallet_id,'wallet_transaction_id'=>$r->id,'internal_transfer_id'=>null,'reference'=>$r->reference_id ?: 'WT-'.$r->id,'event_type'=>$r->type.'.'.$r->status,'title'=>ucfirst($r->type).' '.ucfirst($r->status),'description'=>$r->description,'status'=>$r->status,'direction'=>$dir,'amount'=>$r->amount,'balance_before'=>null,'balance_after'=>null,'available_before'=>null,'available_after'=>null,'reserved_before'=>null,'reserved_after'=>null,'actor_type'=>'system','actor_id'=>null,'metadata'=>json_encode(['backfilled'=>true]),'created_at'=>$r->created_at,'updated_at'=>$r->updated_at];} if($insert)DB::table('financial_activities')->insert($insert);},'wallet_transactions.id','id');
 }
 public function down(): void { Schema::dropIfExists('financial_activities'); if(Schema::hasColumn('wallets','reserved_balance')) Schema::table('wallets',fn(Blueprint $t)=>$t->dropColumn('reserved_balance')); }
};
