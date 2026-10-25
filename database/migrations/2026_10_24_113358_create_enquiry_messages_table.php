<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The conversation on an enquiry: replies, the guest's responses, internal notes and status changes.
     */
    public function up(): void
    {
        Schema::create('enquiry_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Staff member who added it');
            $table->string('type', 20)->comment('reply, guest, note or status');
            $table->string('channel', 20)->nullable()->comment('email, phone, whatsapp or in_person');
            $table->text('body')->nullable();
            $table->string('status', 20)->nullable()->comment('Enquiry status set with this entry');
            $table->timestamps();

            $table->index(['enquiry_id', 'created_at']);
        });

        // Notes saved before the conversation existed become the first internal note.
        foreach (DB::table('enquiries')->whereNotNull('notes')->where('notes', '!=', '')->get() as $enquiry) {
            DB::table('enquiry_messages')->insert([
                'enquiry_id' => $enquiry->id,
                'user_id' => $enquiry->handled_by,
                'type' => 'note',
                'body' => $enquiry->notes,
                'created_at' => $enquiry->updated_at,
                'updated_at' => $enquiry->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiry_messages');
    }
};
