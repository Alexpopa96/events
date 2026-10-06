<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rewrite existing phone numbers to Romanian E.164 (+40XXXXXXXXX).
     * Values that are not valid Romanian numbers are left untouched.
     */
    public function up(): void
    {
        $columns = [
            'users' => ['phone'],
            'provider_profiles' => ['phone', 'whatsapp'],
            'quote_requests' => ['phone'],
        ];

        foreach ($columns as $table => $fields) {
            foreach ($fields as $field) {
                DB::table($table)->whereNotNull($field)->select('id', $field)->orderBy('id')->each(function ($row) use ($table, $field) {
                    $normalized = User::normalizePhone((string) $row->{$field});

                    if ($normalized === null || $normalized === $row->{$field}) {
                        return;
                    }

                    // users.phone is unique: keep the original when the
                    // normalized number already belongs to another account.
                    if ($table === 'users' && DB::table('users')->where('phone', $normalized)->where('id', '!=', $row->id)->exists()) {
                        return;
                    }

                    DB::table($table)->where('id', $row->id)->update([$field => $normalized]);
                });
            }
        }
    }

    public function down(): void
    {
        // Original formatting is not recoverable.
    }
};
