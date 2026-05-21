<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: monospace; background: #121212; color: #e2e8f0; margin: 0; padding: 32px; }
        .card { background: #1b1b1b; border: 1px solid #222; border-radius: 2px; max-width: 600px; margin: 0 auto; padding: 32px; }
        .label { color: #5b7cff; font-size: 11px; text-transform: uppercase; letter-spacing: 0.2em; font-weight: 900; margin-bottom: 4px; }
        .value { color: #e2e8f0; font-size: 14px; margin-bottom: 20px; }
        .divider { border: none; border-top: 1px solid #222; margin: 24px 0; }
        h2 { color: #fff; font-size: 18px; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 24px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Nuevo reporte — AfterReload</h2>
        <hr class="divider">
        <div class="label">Nombre</div>
        <div class="value">{{ $senderName }}</div>
        <div class="label">Email</div>
        <div class="value">{{ $senderEmail }}</div>
        <div class="label">Asunto</div>
        <div class="value">{{ $reportSubject }}</div>
        <hr class="divider">
        <div class="label">Mensaje</div>
        <div class="value" style="white-space: pre-wrap;">{{ $body }}</div>
    </div>
</body>
</html>
