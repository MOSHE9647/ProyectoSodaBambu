<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Comprobante de Nomina</title>
    <style>
        * { margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #111827;
            background-color: #ffffff;
        }

        table { border-collapse: collapse; }

        .page { padding: 32px 40px; }

        .section-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #15803d;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            border-bottom: 1pt solid #dcfce7;
            padding-bottom: 4pt;
            margin-bottom: 10pt;
        }

        .label-sm {
            font-size: 7.5pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            margin-bottom: 2pt;
        }

        .ts-th {
            padding: 8pt 10pt;
            text-align: left;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            color: #374151;
            background-color: #f3f4f6;
            border-bottom: 1pt solid #e5e7eb;
        }

        .ts-td {
            padding: 9pt 10pt;
            font-size: 9.5pt;
            color: #374151;
            border-bottom: 1pt solid #f3f4f6;
            vertical-align: middle;
        }

        .ts-td-right {
            padding: 9pt 10pt;
            font-size: 9.5pt;
            color: #374151;
            border-bottom: 1pt solid #f3f4f6;
            vertical-align: middle;
            text-align: right;
        }

        .ts-td-center {
            padding: 9pt 10pt;
            font-size: 9.5pt;
            color: #374151;
            border-bottom: 1pt solid #f3f4f6;
            vertical-align: middle;
            text-align: center;
        }
    </style>
</head>
<body>

@php
    $timesheets  = collect(data_get($employee, 'timesheets', []));
    $periodStr   = data_get($employee, 'payroll_period', '');

    $periodCarbon = $periodStr
        ? \Carbon\Carbon::createFromFormat('Y-m', $periodStr)->locale('es')
        : \Carbon\Carbon::now('America/Costa_Rica')->locale('es');

    $periodLabel = mb_convert_case(
        $periodCarbon->isoFormat('MMMM YYYY'),
        MB_CASE_TITLE,
        'UTF-8'
    );

    $halfLabel = match(data_get($employee, 'payroll_half')) {
        'first_half'  => 'Primera Quincena (Dias 1-15)',
        'second_half' => 'Segunda Quincena (Dias 16-31)',
        default       => null,
    };

    $startDate   = data_get($employee, 'period_start_date');
    $endDate     = data_get($employee, 'period_end_date');
    $periodRange = '';
    if ($startDate && $endDate) {
        $start       = \Carbon\Carbon::parse($startDate)->locale('es')->isoFormat('DD MMM YYYY');
        $end         = \Carbon\Carbon::parse($endDate)->locale('es')->isoFormat('DD MMM YYYY');
        $periodRange = $start . ' al ' . $end;
    }

    $isActive       = (bool) data_get($employee, 'is_active', true);
    $regularHours   = (float) data_get($employee, 'regular_hours', 0);
    $holidayHours   = (float) data_get($employee, 'holiday_hours', 0);
    $includesFeriado = (bool) data_get($employee, 'includes_holiday_days', false);
@endphp

<div class="page">

    {{-- ══════════════════════════════════════ ENCABEZADO ══════════════════════════════════════ --}}

    <table style="width: 100%; background-color: #15803d; margin-bottom: 22pt;">
        <tr>
            <td style="padding: 18pt 22pt; vertical-align: middle;">
                <div style="font-size: 17pt; font-weight: bold; color: #ffffff; margin-bottom: 2pt;">
                    Soda El Bambu
                </div>
                <div style="font-size: 8.5pt; color: #bbf7d0;">
                    Sistema de Gestion Interna
                </div>
            </td>
            <td style="padding: 18pt 22pt; vertical-align: middle; text-align: right;">
                <div style="font-size: 13pt; font-weight: bold; color: #ffffff; margin-bottom: 3pt;">
                    Comprobante de Nomina
                </div>
                <div style="font-size: 8.5pt; color: #bbf7d0;">
                    {{ $periodLabel }}{{ $halfLabel ? '  |  ' . $halfLabel : '' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ════════════════════════════ DATOS DEL COLABORADOR + PERIODO ═══════════════════════════ --}}

    <table style="width: 100%; margin-bottom: 18pt;">
        <tr>
            {{-- Columna izquierda: colaborador --}}
            <td style="width: 57%; vertical-align: top; padding-right: 14pt;">

                <div class="section-title">Informacion del Colaborador</div>

                <div style="font-size: 13pt; font-weight: bold; color: #111827; margin-bottom: 2pt;">
                    {{ data_get($employee, 'name', 'Colaborador sin nombre') }}
                </div>
                <div style="font-size: 9pt; color: #6b7280; margin-bottom: 10pt;">
                    {{ data_get($employee, 'email', 'Sin correo') }}
                </div>

                <table style="width: 100%; border-top: 1pt solid #f3f4f6;">
                    <tr>
                        <td style="padding-top: 10pt; vertical-align: top; width: 33%;">
                            <div class="label-sm">Estado</div>
                            <div style="font-size: 9pt; font-weight: bold;
                                 color: {{ $isActive ? '#166534' : '#991b1b' }};">
                                {{ data_get($employee, 'status_label', 'Activo') }}
                            </div>
                        </td>
                        <td style="padding-top: 10pt; vertical-align: top; width: 34%;">
                            <div class="label-sm">Tarifa por Hora</div>
                            <div style="font-size: 9pt; font-weight: bold; color: #111827;">
                                {{ data_get($employee, 'hourly_wage_label', 'CRC 0/hr') }}
                            </div>
                        </td>
                        <td style="padding-top: 10pt; vertical-align: top; width: 33%;">
                            <div class="label-sm">Frecuencia de Pago</div>
                            <div style="font-size: 9pt; font-weight: bold; color: #111827;">
                                {{ data_get($employee, 'payment_frequency_label', 'Sin definir') }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>

            {{-- Columna derecha: periodo --}}
            <td style="width: 43%; vertical-align: top;
                 background-color: #f0fdf4;
                 border: 1pt solid #dcfce7;
                 padding: 14pt 16pt;">

                <div class="section-title" style="border-bottom-color: #bbf7d0;">
                    Periodo de Pago
                </div>

                <div style="margin-bottom: 9pt;">
                    <div class="label-sm">Periodo</div>
                    <div style="font-size: 11pt; font-weight: bold; color: #111827;">
                        {{ $periodLabel }}
                    </div>
                </div>

                @if($halfLabel)
                <div style="margin-bottom: 9pt;">
                    <div class="label-sm">Quincena</div>
                    <div style="font-size: 9.5pt; font-weight: bold; color: #15803d;">
                        {{ $halfLabel }}
                    </div>
                </div>
                @endif

                <div>
                    <div class="label-sm">Rango de Fechas</div>
                    <div style="font-size: 9pt; font-weight: bold; color: #374151;">
                        {{ $periodRange }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════ RESUMEN DE HORAS Y SALARIO ══════════════════════════════ --}}

    <table style="width: 100%; border: 1pt solid #e5e7eb; background-color: #f9fafb; margin-bottom: 20pt;">
        <tr>
            <td style="padding: 12pt 14pt; text-align: center; border-right: 1pt solid #e5e7eb;">
                <div class="label-sm" style="margin-bottom: 4pt;">Dias Trabajados</div>
                <div style="font-size: 19pt; font-weight: bold; color: #111827;">
                    {{ data_get($employee, 'worked_days', 0) }}
                </div>
            </td>
            <td style="padding: 12pt 14pt; text-align: center; border-right: 1pt solid #e5e7eb;">
                <div class="label-sm" style="margin-bottom: 4pt;">Horas Regulares</div>
                <div style="font-size: 15pt; font-weight: bold; color: #111827;">
                    @php
                        $regularLabel = rtrim(rtrim(number_format($regularHours, 2, ',', ''), '0'), ',');
                    @endphp
                    {{ $regularLabel }}h
                </div>
            </td>
            <td style="padding: 12pt 14pt; text-align: center; border-right: 1pt solid #e5e7eb;">
                <div class="label-sm" style="margin-bottom: 4pt;">Horas Feriado</div>
                <div style="font-size: 15pt; font-weight: bold;
                     color: {{ $holidayHours > 0 ? '#b45309' : '#111827' }};">
                    @php
                        $holidayLabel = rtrim(rtrim(number_format($holidayHours, 2, ',', ''), '0'), ',');
                    @endphp
                    {{ $holidayLabel }}h
                </div>
            </td>
            <td style="padding: 12pt 14pt; text-align: center;">
                <div class="label-sm" style="margin-bottom: 4pt;">Total Horas</div>
                <div style="font-size: 19pt; font-weight: bold; color: #15803d;">
                    {{ data_get($employee, 'total_worked_hours_label', '0h') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ═══════════════════════════════════ DESGLOSE POR DIA ═══════════════════════════════════ --}}

    <div class="section-title">Desglose por Dia</div>

    @if($timesheets->isEmpty())
        <table style="width: 100%; border: 1pt solid #e5e7eb; margin-bottom: 12pt;">
            <tr>
                <td style="padding: 24pt; text-align: center; color: #9ca3af; font-size: 9.5pt;">
                    No se encontraron registros de asistencia para este periodo.
                </td>
            </tr>
        </table>
    @else
        <table style="width: 100%; border: 1pt solid #e5e7eb; margin-bottom: 10pt;">
            <thead>
                <tr>
                    <th class="ts-th" style="width: 18%;">Fecha</th>
                    <th class="ts-th" style="width: 14%;">Tipo de Dia</th>
                    <th class="ts-th" style="width: 14%;">Entrada</th>
                    <th class="ts-th" style="width: 14%;">Salida</th>
                    <th class="ts-th" style="width: 12%; text-align: center;">Horas</th>
                    <th class="ts-th" style="width: 28%; text-align: right;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timesheets as $index => $ts)
                    @php
                        $isHoliday   = (bool) data_get($ts, 'is_holiday', false);
                        $totalHours  = (float) data_get($ts, 'total_hours', 0);
                        $isIncomplete = $totalHours <= 0;
                        $rowBg = $isHoliday
                            ? '#fefce8'
                            : ($index % 2 === 0 ? '#ffffff' : '#f9fafb');
                    @endphp
                    <tr style="background-color: {{ $rowBg }};">
                        <td class="ts-td" style="font-weight: bold;">
                            {{ data_get($ts, 'work_date_label', 'N/A') }}
                        </td>
                        <td class="ts-td">
                            @if($isHoliday)
                                <span style="font-weight: bold; color: #b45309;">Feriado (x2)</span>
                            @else
                                <span style="color: #6b7280;">Regular</span>
                            @endif
                        </td>
                        <td class="ts-td">
                            {{ data_get($ts, 'start_time_label', 'N/A') }}
                        </td>
                        <td class="ts-td">
                            @if($isIncomplete)
                                <span style="color: #b45309; font-size: 8.5pt;">Sin registrar</span>
                            @else
                                {{ data_get($ts, 'end_time_label', 'N/A') }}
                            @endif
                        </td>
                        <td class="ts-td-center" style="font-weight: bold;
                            color: {{ $isIncomplete ? '#9ca3af' : '#111827' }};">
                            {{ data_get($ts, 'total_hours_label', '0h') }}
                        </td>
                        <td class="ts-td-right" style="font-weight: bold;
                            color: {{ $isHoliday ? '#b45309' : '#15803d' }};">
                            {{ data_get($ts, 'salary_amount_label', 'CRC 0') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- FILA TOTAL --}}
    <table style="width: 100%; background-color: #f0fdf4; border: 1pt solid #bbf7d0; margin-bottom: 30pt;">
        <tr>
            <td style="padding: 11pt 14pt; font-size: 8.5pt; color: #6b7280; vertical-align: middle;">
                {{ data_get($employee, 'worked_days', 0) }} dias trabajados
                &nbsp;&middot;&nbsp;
                {{ data_get($employee, 'total_worked_hours_label', '0h') }} totales
                @if($includesFeriado)
                    &nbsp;&middot;&nbsp; Incluye dias feriados (x2)
                @endif
            </td>
            <td style="padding: 11pt 14pt; text-align: right; white-space: nowrap; vertical-align: middle;">
                <div style="font-size: 8pt; color: #16a34a; margin-bottom: 2pt;">
                    Total a Pagar
                </div>
                <div style="font-size: 16pt; font-weight: bold; color: #15803d;">
                    {{ data_get($employee, 'total_salary_amount_label', 'CRC 0') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ════════════════════════════════════════ FIRMAS ════════════════════════════════════════ --}}

    <table style="width: 100%; margin-bottom: 28pt;">
        <tr>
            <td style="width: 42%; text-align: center; padding-top: 38pt; vertical-align: bottom;">
                <div style="border-top: 1pt solid #9ca3af; padding-top: 6pt;">
                    <div style="font-size: 8.5pt; font-weight: bold; color: #374151;">
                        Firma del Colaborador
                    </div>
                    <div style="font-size: 8pt; color: #9ca3af; margin-top: 2pt;">
                        {{ data_get($employee, 'name', '') }}
                    </div>
                </div>
            </td>
            <td style="width: 16%;"></td>
            <td style="width: 42%; text-align: center; padding-top: 38pt; vertical-align: bottom;">
                <div style="border-top: 1pt solid #9ca3af; padding-top: 6pt;">
                    <div style="font-size: 8.5pt; font-weight: bold; color: #374151;">
                        Firma del Administrador
                    </div>
                    <div style="font-size: 8pt; color: #9ca3af; margin-top: 2pt;">
                        Soda El Bambu
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════= PIE DE PAGINA ══════════════════════════════════════ --}}
    <div style="border-top: 1pt solid #e5e7eb; padding-top: 10pt; text-align: center;">
        <div style="font-size: 7.5pt; color: #9ca3af;">
            Generado el
            {{ $generatedAt->translatedFormat('d \d\e F \d\e Y \a \l\a\s h:i A') }}
            (hora de Costa Rica)
            {{-- &nbsp;&middot;&nbsp; --}}<br>
            Soda El Bambu &mdash; Sistema de Gestion Interna
            &nbsp;&middot;&nbsp;
            Documento confidencial de uso interno.
        </div>
    </div>

</div>
</body>
</html>