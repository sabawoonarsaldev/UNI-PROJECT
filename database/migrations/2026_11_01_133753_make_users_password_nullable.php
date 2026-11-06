<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // An empty password means: this person has no login account
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Every user needs a password again, so the empty ones get a random one
        DB::table('users')->whereNull('password')->get(['id'])->each(
            fn ($user) => DB::table('users')->where('id', $user->id)->update(['password' => Hash::make(Str::random(40))])
        );

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};