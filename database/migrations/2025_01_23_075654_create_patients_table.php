<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
          
            $table->string('Auth_name');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->rememberToken();


            
            $table->date('date_of_birth')->nullable();
            $table->string('patient_photo')->nullable();
            // $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->string('address')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('pin_code')->nullable();
            // Relative Information
            $table->string('relative_name')->nullable();
            $table->string('relative_relation')->nullable();
            $table->enum('relative_gender', ['Male', 'Female', 'Other'])->nullable();
            $table->string('relative_state')->nullable();
            $table->string('relative_city')->nullable();
            $table->string('relative_pin_code')->nullable();
            $table->string('relative_email')->nullable();
            $table->string('relative_phone')->nullable();
            $table->string('relative_address')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->boolean('is_service_engineer')->default(false);
            
            $table->timestamps();
            
         
           


        });
    }

    public function down()
    {
        Schema::dropIfExists('patients');
    }
}