<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - Sedang Pemeliharaan | {{ config('app.name', 'OPS_BOSP SR CBD') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #172554 55%, #0f172a 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            padding: 1.5rem;
        }
        .kartu {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            padding: 2.5rem 2rem;
            max-width: 26rem;
            width: 100%;
            text-align: center;
        }
        .kode {
            font-size: 3.5rem;
            font-weight: 800;
            color: #0369a1;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }
        p {
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="kartu">
        <div class="kode">503</div>
        <h1>Sedang Dalam Pemeliharaan</h1>
        <p>Aplikasi sedang dalam pemeliharaan sebentar. Mohon coba lagi dalam beberapa saat.</p>
    </div>
</body>
</html>
