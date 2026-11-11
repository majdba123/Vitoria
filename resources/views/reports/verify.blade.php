@php
    $t = fn (string $key) => trans('reports.verify.'.$key, [], $locale);
    $notAvailable = $t('not_available');
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $t('page_title') }} — Vetora</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif; background:#f4f8f6; color:#172033; margin:0; padding:2.5rem 1.25rem; display:flex; justify-content:center; }
        .card { max-width:30rem; width:100%; box-sizing:border-box; background:#fff; border:1px solid #d3ddd6; border-radius:12px; padding:2rem; box-shadow:0 1px 2px rgba(23,63,53,.06); }
        .badge { display:inline-flex; align-items:center; gap:.4rem; font-weight:700; font-size:.8rem; padding:.35rem .75rem; border-radius:999px; }
        .badge.ok { background:#e7f3ee; color:#173f35; }
        .badge.fail { background:#fbeaea; color:#a93226; }
        h1 { font-size:1.15rem; margin:.9rem 0 .25rem; color:#173f35; }
        dl { margin:1.25rem 0 0; }
        dt { font-size:.75rem; color:#64748b; font-weight:700; margin-top:.75rem; }
        dd { margin:.15rem 0 0; font-size:.95rem; font-weight:600; }
        .figures { margin-top:1.25rem; border-top:1px solid #e3e9ee; padding-top:.25rem; }
        .ref { font-family: ui-monospace, Menlo, Consolas, monospace; letter-spacing:.04em; }
        .notice { margin-top:1.75rem; font-size:.8rem; color:#64748b; line-height:1.6; }
    </style>
</head>
<body>
    <main class="card">
        @if ($verified)
            <span class="badge ok">&#10003; {{ $t('badge') }}</span>
            <h1 dir="auto">{{ $organization }}</h1>
            <dl>
                <dt>{{ $t('scope') }}</dt>
                <dd dir="auto">{{ $scopeLabel }}</dd>

                <dt>{{ $t('period') }}</dt>
                <dd><bdi dir="ltr">{{ $from ?? $notAvailable }} — {{ $to ?? $notAvailable }}</bdi></dd>

                <dt>{{ $t('generated') }}</dt>
                <dd><bdi dir="ltr">{{ $generatedAt?->format('Y-m-d H:i') ?? $notAvailable }}</bdi></dd>

                <dt>{{ $t('reference') }}</dt>
                <dd class="ref"><bdi dir="ltr">{{ $reference ?: $notAvailable }}</bdi></dd>
            </dl>
            @if ($figures !== [])
                <dl class="figures">
                    @foreach ($figures as $label => $value)
                        <dt>{{ $label }}</dt>
                        <dd><bdi>{{ $value }}</bdi></dd>
                    @endforeach
                </dl>
            @endif
            <p class="notice">{{ $t('notice') }}</p>
        @else
            <span class="badge fail">&#10007; {{ $t('failed_badge') }}</span>
            <h1>{{ $t('failed_title') }}</h1>
            <p class="notice">{{ $t('failed_notice') }}</p>
        @endif
    </main>
</body>
</html>
