<!DOCTYPE html>
<html lang="bn" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'রক্ষণাবেক্ষণ চলছে - Pirgacha Internet' }}</title>
    <link rel="icon" type="image/png" href="/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Hind Siliguri', 'Outfit', system-ui, -apple-system, sans-serif;
        }
        body {
            background-color: #071322;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }
        .bg-glow-1 {
            position: absolute;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.15) 0%, rgba(249, 115, 22, 0) 70%);
            top: -100px;
            right: -100px;
            border-radius: 50%;
            z-index: 1;
            pointer-events: none;
        }
        .bg-glow-2 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.12) 0%, rgba(14, 165, 233, 0) 70%);
            bottom: -150px;
            left: -150px;
            border-radius: 50%;
            z-index: 1;
            pointer-events: none;
        }
        .card {
            position: relative;
            z-index: 10;
            max-width: 640px;
            width: 100%;
            background: rgba(9, 26, 46, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(30, 58, 95, 0.8);
            border-radius: 2rem;
            padding: 3rem 2.25rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 30px rgba(14, 165, 233, 0.08);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(249, 115, 22, 0.12);
            color: #fb923c;
            border: 1px solid rgba(249, 115, 22, 0.3);
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 1.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #f97316;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
        }
        .icon-box {
            width: 84px;
            height: 84px;
            margin: 0 auto 1.75rem auto;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(249, 115, 22, 0.15));
            border: 1px solid rgba(14, 165, 233, 0.4);
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        h1 {
            font-size: 1.85rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            line-height: 1.3;
        }
        p {
            font-size: 0.98rem;
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 1.75rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            background: rgba(7, 19, 34, 0.6);
            border: 1px solid rgba(30, 58, 95, 0.6);
            border-radius: 1.25rem;
            padding: 1.25rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        @media (min-width: 500px) {
            .info-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .info-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 700;
        }
        .info-val {
            font-size: 0.9rem;
            color: #38bdf8;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.85rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.4rem;
            border-radius: 1rem;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #ffffff;
            box-shadow: 0 10px 20px -5px rgba(249, 115, 22, 0.4);
            border: none;
        }
        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #0b1e36;
            color: #cbd5e1;
            border: 1px solid #1e3a5f;
        }
        .btn-secondary:hover {
            background: #102b4d;
            color: #ffffff;
            border-color: #38bdf8;
        }
        .footer-credit {
            margin-top: 2.25rem;
            font-size: 0.75rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>

    <div class="card">
        <div class="badge">
            <span class="pulse-dot"></span>
            System Scheduled Maintenance
        </div>

        <div class="icon-box">
            🛠️
        </div>

        <h1>{{ $title ?? 'আমরা রক্ষণাবেক্ষণ করছি' }}</h1>

        <p>
            {{ $message ?? 'আমাদের সিস্টেম আপগ্রেড ও নেটওয়ার্ক রক্ষণাবেক্ষণের কাজ চলছে। সাময়িক অসুবিধার জন্য আমরা আন্তরিকভাবে দুঃখিত। খুব শীঘ্রই সাইটটি স্বাভাবিক হবে।' }}
        </p>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">আনুমানিক সময় (Estimated Time)</span>
                <span class="info-val">{{ $estimatedTime ?? 'শীঘ্রই সম্পন্ন হবে' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">জরুরী NOC হটলাইন</span>
                <span class="info-val">{{ $hotline ?? '01711-000000' }}</span>
            </div>
        </div>

        <div class="actions">
            <a href="tel:{{ explode('/', $hotline ?? '01711000000')[0] }}" class="btn btn-primary">
                📞 সরাসরি হটলাইনে কল করুন
            </a>
        </div>

        <div class="footer-credit">
            &copy; {{ date('Y') }} Pirgacha Internet Optical Fiber Broadband. All rights reserved.
        </div>
    </div>
</body>
</html>
