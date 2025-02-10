<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActiveToImplantsTable extends Migration
{
    public function up()
    {
        Schema::table('implants', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('ipg_model'); // adjust column order if needed
        });
    }

    public function down()
    {
        Schema::table('implants', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
}