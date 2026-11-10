
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_duplicados', function (Blueprint $table) {
            $table->id();

            // Reclamo que se toma como referencia.
            $table->unsignedInteger('reporte_principal_id');

            // Reclamo confirmado como duplicado.
            $table->unsignedInteger('reporte_duplicado_id');

            // Operador o supervisor que confirmó la relación.
            $table->unsignedInteger('confirmado_por');

            $table->string('observacion', 500)->nullable();

            $table->timestamp('fecha_confirmacion')
                ->useCurrent();

            // Un reclamo no puede registrarse como duplicado
            // de varios reclamos principales.
            $table->unique('reporte_duplicado_id');

            $table->index('reporte_principal_id');

            $table->foreign('reporte_principal_id')
                ->references('id')
                ->on('reportes')
                ->cascadeOnDelete();

            $table->foreign('reporte_duplicado_id')
                ->references('id')
                ->on('reportes')
                ->cascadeOnDelete();

            $table->foreign('confirmado_por')
                ->references('id')
                ->on('usuarios')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_duplicados');
    }
};
