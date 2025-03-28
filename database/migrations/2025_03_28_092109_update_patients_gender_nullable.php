<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePatientsGenderNullable extends Migration
{
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable(false)->change();
        });
    }
}