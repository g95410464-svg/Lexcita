<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $cita->codigo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Georgia', serif;
            background: #f5f5f0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .ticket {
            background: white;
            width: 100%;
            max-width: 600px;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        /* Cabecera */
        .ticket-header {
            background: #0d0d0d;
            color: white;
            padding: 30px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ticket-header .logo {
            font-size: 28px;
            font-weight: bold;
            color: #c9a84c;
            letter-spacing: 2px;
        }

        .ticket-header .subtitle {
            font-size: 11px;
            color: #888;
            margin-top: 3px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .ticket-header .codigo {
            text-align: right;
        }

        .ticket-header .codigo-label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ticket-header .codigo-value {
            font-size: 20px;
            color: #c9a84c;
            font-weight: bold;
            margin-top: 4px;
            letter-spacing: 1px;
        }

        /* Estado */
        .ticket-estado {
            padding: 12px 35px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: bold;
            text-align: center;
        }

        .estado-confirmada { background: #dcfce7; color: #166534; }
        .estado-pendiente_pago { background: #fef9c3; color: #854d0e; }
        .estado-cancelada { background: #fee2e2; color: #991b1b; }

        /* Separador de puntos */
        .divider {
            border: none;
            border-top: 2px dashed #e5e5e5;
            margin: 0 35px;
        }

        /* Cuerpo */
        .ticket-body {
            padding: 30px 35px;
        }

        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #999;
            margin-bottom: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        .info-item label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 4px;
        }

        .info-item span {
            display: block;
            font-size: 15px;
            color: #1a1a1a;
            font-weight: 500;
        }

        .info-item.full {
            grid-column: 1 / -1;
        }

        .info-item .highlight {
            color: #c9a84c;
            font-size: 18px;
            font-weight: bold;
        }

        /* Descripción */
        .descripcion-box {
            background: #f9f9f7;
            border-left: 3px solid #c9a84c;
            padding: 14px 16px;
            margin-bottom: 28px;
            border-radius: 0 4px 4px 0;
        }

        .descripcion-box label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 6px;
        }

        .descripcion-box p {
            font-size: 13px;
            color: #444;
            line-height: 1.6;
        }

        /* Monto */
        .monto-section {
            background: #0d0d0d;
            margin: 0 -35px -30px;
            padding: 20px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .monto-section .monto-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .monto-section .monto-valor {
            font-size: 26px;
            color: #c9a84c;
            font-weight: bold;
        }

        /* Footer */
        .ticket-footer {
            background: #0d0d0d;
            padding: 16px 35px 24px;
            text-align: center;
        }

        .ticket-footer p {
            font-size: 11px;
            color: #555;
            line-height: 1.7;
        }

        .ticket-footer .aviso {
            color: #c9a84c;
            font-size: 10px;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Botones (solo pantalla, no imprimen) */
        .acciones {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
        }

        .btn-imprimir {
            background: #c9a84c;
            color: #0d0d0d;
            border: none;
            padding: 12px 28px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 2px;
        }

        .btn-volver {
            background: transparent;
            color: #666;
            border: 1px solid #ddd;
            padding: 12px 28px;
            font-size: 13px;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 2px;
            text-decoration: none;
            display: inline-block;
        }

        /* Estilos de impresión */
        @media print {
            body { background: white; padding: 0; }
            .ticket { box-shadow: none; max-width: 100%; }
            .acciones { display: none; }
            .ticket-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .ticket-estado { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .monto-section { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .ticket-footer { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div>
    <div class="ticket">

        {{-- Cabecera --}}
        <div class="ticket-header">
            <div>
                <div class="logo">GC</div>
                <div class="subtitle">Tu Conexión Legal</div>
            </div>
            <div class="codigo">
                <div class="codigo-label">Código de Cita</div>
                <div class="codigo-value">{{ $cita->codigo }}</div>
            </div>
        </div>

        {{-- Estado --}}
        <div class="ticket-estado estado-{{ $cita->estado }}">
            @switch($cita->estado)
                @case('confirmada') ✓ Cita Confirmada @break
                @case('pendiente_pago') ⏳ Pendiente de Pago @break
                @case('cancelada') ✕ Cita Cancelada @break
                @default {{ $cita->estado }}
            @endswitch
        </div>

        <hr class="divider">

        {{-- Cuerpo --}}
        <div class="ticket-body">

            <p class="section-title">Información de la Cita</p>

            <div class="info-grid">
                <div class="info-item">
                    <label>Fecha</label>
                    <span class="highlight">{{ $cita->fecha->locale('es')->isoFormat('dddd D [de] MMMM, YYYY') }}</span>
                </div>
                <div class="info-item">
                    <label>Horario</label>
                    <span class="highlight">{{ \Carbon\Carbon::parse($cita->hora_inicio)->format('h:i A') }} – {{ \Carbon\Carbon::parse($cita->hora_fin)->format('h:i A') }}</span>
                </div>
                <div class="info-item">
                    <label>Tipo de Consulta</label>
                    <span>{{ ucwords(str_replace('_', ' ', $cita->tipo)) }}</span>
                </div>
                <div class="info-item">
                    <label>Modalidad</label>
                    <span>{{ ucfirst($cita->modalidad) }}</span>
                </div>
                <div class="info-item">
                    <label>Cliente</label>
                    <span>{{ $cita->cliente->nombre }}</span>
                </div>
                <div class="info-item">
                    <label>Abogado Asignado</label>
                    <span>{{ $cita->abogado->nombre }}</span>
                </div>
                @if($cita->abogado->especialidad)
                <div class="info-item full">
                    <label>Especialidad</label>
                    <span>{{ $cita->abogado->especialidad }}</span>
                </div>
                @endif
            </div>

            @if($cita->descripcion)
            <div class="descripcion-box">
                <label>Motivo de Consulta</label>
                <p>{{ $cita->descripcion }}</p>
            </div>
            @endif

            <div class="monto-section">
                <div>
                    <div class="monto-label">Total Pagado</div>
                </div>
                <div class="monto-valor">${{ number_format($cita->monto, 2) }}</div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="ticket-footer">
            <p>
                GC Tu Conexión Legal — Servicios Legales Integrales<br>
                servicioslegalesint.com &nbsp;|&nbsp; {{ date('Y') }}
            </p>
            <p class="aviso">Presentar este ticket al momento de la consulta</p>
        </div>

    </div>

    {{-- Botones --}}
    <div class="acciones">
        <button class="btn-imprimir" onclick="window.print()">🖨 Imprimir Ticket</button>
        <a href="{{ route('cliente.mis-citas') }}" class="btn-volver">← Volver a mis citas</a>
    </div>
</div>

</body>
</html>