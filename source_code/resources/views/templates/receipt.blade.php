<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante - {{ config('app.name') }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            padding: 0;
            background: #f4f4f4; 
            color: #111; 
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace; 
            font-size: 12px; 
            line-height: 1.4; 
        }
        .receipt-shell { 
            width: 80mm; 
            margin: 0 auto; 
            background: #fff; 
            padding: 5mm; 
            min-height: 100vh;
        }
        .receipt-header, .receipt-footer { text-align: center; margin-bottom: 10px; }
        .business-name { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .receipt-line { border-top: 1px dashed #000; margin: 10px 0; }
        .receipt-row { display: flex; justify-content: space-between; }
        .receipt-item { margin-bottom: 8px; }
        .receipt-item-name { font-weight: bold; }
        .receipt-total { font-size: 15px; font-weight: bold; margin-top: 5px; }
        .text-muted { color: #575B5E; font-size: 10px; }
        
        @media print {
            body { background: #fff; }
            .receipt-shell { min-height: auto; width: 100%; padding: 3mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-shell">
        <header class="receipt-header">
            <div class="business-name">{{ config('app.name', 'Soda El Bambu') }}</div>
            @if($receiptData['receipt_number'])
                <div>N°: {{ $receiptData['receipt_number'] }}</div>
            @endif
            <div>{{ \Carbon\Carbon::parse($receiptData['date'])->timezone('America/Costa_Rica')->translatedFormat('d M Y H:i') }}</div>
        </header>

        <div class="receipt-line"></div>

        <div class="receipt-body">
            @foreach($receiptData['items'] as $item)
                <div class="receipt-item">
                    <div class="receipt-item-name">{{ $item['name'] }}</div>
                    <div class="receipt-row">
                        <span>{{ $item['quantity'] }} x {{ format_crc($item['unit_price']) }}</span>
                        <span>{{ format_crc($item['total']) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="receipt-line"></div>

        <div class="receipt-totals">
            <div class="receipt-row"><span>Subtotal</span><span>{{ format_crc($receiptData['subtotal']) }}</span></div>
            <div class="receipt-row">
                <span>Impuestos</span>
                <span>
                    @php
                        // List of model types that are exempt from taxes when tax_total is 0
                        $taxExemptModels = [\App\Models\Purchase::class, \App\Models\Contract::class];
                    @endphp

                    @if(in_array($receiptData['model_type'], $taxExemptModels) && $receiptData['tax_total'] == 0)
                        <span class="text-muted" style="font-size: 11px;">No aplican impuestos</span>
                    @else
                        {{ format_crc($receiptData['tax_total']) }}
                    @endif
                </span>
            </div>
            <div class="receipt-row receipt-total"><span>Total</span><span>{{ format_crc($receiptData['total']) }}</span></div>
        </div>

        <div class="receipt-line"></div>

        <div class="receipt-payments">
            <div class="receipt-item-name">Detalle de Pago</div>
            @foreach($receiptData['payments'] as $payment)
                <div class="receipt-row">
                    <span>{{ $payment['method_label'] }}</span>
                    <span>{{ format_crc($payment['amount']) }}</span>
                </div>
                @if($payment['reference'])
                    <div class="receipt-row text-muted" style="font-size: 9px;">
                        <span>Ref.:</span>
                        <span>{{ $payment['reference'] }}</span>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="receipt-details">
            <div class="receipt-row">
                <span>Recibido:</span>
                <span>{{ format_crc($receiptData['total_tendered']) }}</span>
            </div>
            <div class="receipt-row">
                <span>Vuelto:</span>
                <span>{{ format_crc($receiptData['change_amount']) }}</span>
            </div>
        </div>

        <div class="receipt-line"></div>
        <footer class="receipt-footer">¡Gracias por su visita!</footer>
    </div>

    <script>window.onload = () => { window.print(); };</script>
</body>
</html>