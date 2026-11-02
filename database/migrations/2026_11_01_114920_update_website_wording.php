<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Client wording changes for text that admins may already have saved:
 * "Vrindavan Resort Sikkim" as the full resort name (including on invoices), "Guest Experiences"
 * for the reviews text, and "Enquire" instead of "Inquire" on buttons. Only the old wording is replaced.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_contents')
            ->where('value', 'What our Guests think about us.')
            ->update(['value' => 'Guest Experiences']);

        DB::table('site_contents')
            ->where('value', 'Inquire with concierge')
            ->update(['value' => 'Enquire with concierge']);

        DB::table('site_contents')
            ->where('value', 'like', '%Vrindavan%Resort%')
            ->get(['id', 'value'])
            ->each(function (object $row): void {
                $value = preg_replace('/Vrindavan (Luxury )?Resort(?! Sikkim)( Sikkim)?/', 'Vrindavan Resort Sikkim', $row->value);

                if ($value !== $row->value) {
                    DB::table('site_contents')->where('id', $row->id)->update(['value' => $value]);
                }
            });

        DB::table('experiences')
            ->whereRaw('LOWER(link_label) = ?', ['inquire & book'])
            ->update(['link_label' => 'Enquire & Book']);
    }

    public function down(): void
    {
        // Wording only; the old text is not restored.
    }
};
