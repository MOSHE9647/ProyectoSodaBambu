<?php

namespace App\Actions\Sale;

use Carbon\Carbon;
use App\Models\Sale;
use Illuminate\Support\Collection;

class GetDailySalesDataAction
{
    public function execute(): array
    {
        
            
        $hoy = Carbon::today();

        // 1. Consultar la BD: Agrupar por hora y sumar totales
        // Nota: HOUR() es una función nativa de MySQL. Si usas PostgreSQL o SQLite, la sintaxis cambia ligeramente.
        $isSqlite = \DB::connection()->getDriverName() === 'sqlite';
        $hourExpression = $isSqlite ? "strftime('%H', date)" : 'HOUR(date)';

        $ventasDB = Sale::selectRaw("{$hourExpression} as hora, SUM(total) as total_por_hora")
            ->whereDate('date', $hoy)
            ->groupBy('hora')
            ->pluck('total_por_hora', 'hora');

        $totalDiario = 0;
        $etiquetas = [];
        $valores = [];

        // 2. Definir el horario de atención para la gráfica (Ejemplo: 8:00 AM a 10:00 PM)
        $horaApertura = 8;  // 8 AM
        $horaCierre = 22;   // 10 PM

        // 3. Iterar sobre cada hora del horario definido
        for ($i = $horaApertura; $i <= $horaCierre; $i++) {
            
            // Formatear la etiqueta de la hora para la UI (ej. "8:00 AM", "2:00 PM")
            $horaFormateada = Carbon::createFromTime($i, 0, 0)->format('g:i A');

            // Obtener la venta de esa hora exacta, si no hubo ventas, asignar 0
            $ventaDeLaHora = $ventasDB->get($i, 0);

            // Poblar los arreglos
            $etiquetas[] = $horaFormateada;
            $valores[] = $ventaDeLaHora;
            $totalDiario += $ventaDeLaHora;
        }

        // 4. Retornar el arreglo asociativo
        return [
            'dailyTotal'        => $totalDiario,
            'dailySalesLabels' => $etiquetas,
            'dailySalesValues' => $valores,
        ];
    }
}