<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::table('platform_impersonation_tokens',function(Blueprint $t){$t->unsignedBigInteger('admin_id')->nullable()->change();$t->unsignedBigInteger('parent_admin_id')->nullable()->after('admin_id')->index();});}public function down():void{Schema::table('platform_impersonation_tokens',function(Blueprint $t){$t->dropColumn('parent_admin_id');});}};
