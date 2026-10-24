<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('v3_document_number_changes', function (Blueprint $t) {
            $t->foreignId('document_id')->primary()->constrained('v3_documents')->restrictOnDelete();
            $t->string('old_number');
            $t->string('new_number');
            $t->json('old_snapshot');
            $t->timestamp('changed_at');
        });
        \App\Services\NormalizeDocumentNumbers::run();
    }
    public function down(): void {
        throw new \RuntimeException('Penomoran yang telah diterbitkan tidak di-rollback otomatis. Gunakan cadangan sebelum pembaruan jika perlu pemulihan.');
    }
};
