<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ trans('reports.verify.page_title', [], $isArabic ? 'ar' : 'en') }} — Vetora</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif; background:#f4f8f6; color:#172033; margin:0; padding:2.5rem 1.25rem; display:flex; justify-content:center; }
        .card { max-width:30rem; width:100%; background:#fff; border:1px solid #d3ddd6; border-radius:12px; padding:2rem; box-shadow:0 1px 2px rgba(23,63,53,.06); }
        .badge { display:inline-flex; align-items:center; gap:.4rem; background:#e7f3ee; color:#173f35; font-weight:700; font-size:.8rem; padding:.35rem .75rem; border-radius:999px; }
        h1 { font-size:1.15rem; margin:.9rem 0 .25rem; color:#173f35; }
        dl { margin:1.25rem 0 0; }
        dt { font-size:.75rem; color:#64748b; font-weight:700; margin-top:.75rem; }
        dd { margin:.15rem 0 0; font-size:.95rem; font-weight:600; }
        .ref { font-family: ui-monospace, Menlo, Consolas, monospace; letter-spacing:.04em; }
        .notice { margin-top:1.75rem; font-size:.8rem; color:#64748b; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">&#10003; {{ trans('reports.verify.badge', [], $isArabic ? 'ar' : 'en') }}</span>
        <h1 dir="auto">{{ $organization }}</h1>
        <dl>
            <dt>{{ trans('reports.verify.scope', [], $isArabic ? 'ar' : 'en') }}</dt>
            <dd dir="auto">{{ $scopeLabel }}</dd>

            <dt>{{ trans('reports.verify.period', [], $isArabic ? 'ar' : 'en') }}</dt>
            <dd dir="ltr">{{ $from ?? trans('reports.verify.not_available', [], $isArabic ? 'ar' : 'en') }} — {{ $to ?? trans('reports.verify.not_available', [], $isArabic ? 'ar' : 'en') }}</dd>

            <dt>{{ trans('reports.verify.generated', [], $isArabic ? 'ar' : 'en') }}</dt>
            <dd dir="ltr">{{ $generatedAt?->format('Y-m-d H:i') ?? trans('reports.verify.not_available', [], $isArabic ? 'ar' : 'en') }}</dd>

            <dt>{{ trans('reports.verify.reference', [], $isArabic ? 'ar' : 'en') }}</dt>
            <dd class="ref" dir="ltr">{{ $reference ?: trans('reports.verify.not_available', [], $isArabic ? 'ar' : 'en') }}</dd>
        </dl>
        <p class="notice">{{ trans('reports.verify.notice', [], $isArabic ? 'ar' : 'en') }}</p>
    </div>
</body>
</html>
