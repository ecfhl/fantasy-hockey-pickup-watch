<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Collector Status · Fantasy Hockey Pickup Watch</title>
<style>
:root{--bg:#f6f7fb;--surface:#fff;--text:#172033;--muted:#667085;--line:#dfe3ea;--good:#166534;--goodbg:#dcfce7;--bad:#b91c1c;--badbg:#fee2e2}
*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:var(--bg);color:var(--text)}
.shell{width:min(1000px,calc(100% - 32px));margin:auto}.hero{background:#0b1739;color:#fff;padding:24px 0}.hero h1{margin:0;font-size:28px}.hero p{margin:6px 0 0;color:#c8d2ee}
main{padding:24px 0 50px}.back{display:inline-block;margin-bottom:16px;color:#2563eb;text-decoration:none;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.card{background:var(--surface);border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(15,23,42,.04)}
.top{display:flex;justify-content:space-between;gap:12px;align-items:start}.card h2{margin:0;font-size:20px}.pill{border-radius:999px;padding:5px 9px;font-size:11px;font-weight:800}.ok{background:var(--goodbg);color:var(--good)}.bad{background:var(--badbg);color:var(--bad)}
dl{display:grid;grid-template-columns:150px 1fr;gap:10px 14px;margin:18px 0 0}dt{color:var(--muted);font-size:13px}dd{margin:0;font-weight:700;font-size:13px}.note{color:var(--muted);font-size:12px;margin-top:18px}
@media(max-width:700px){.grid{grid-template-columns:1fr}.shell{width:min(100% - 20px,1000px)}dl{grid-template-columns:1fr;gap:4px}dd{margin-bottom:8px}}
</style>
</head>
<body>
<header class="hero"><div class="shell"><h1>Collector Status</h1><p>Laravel scheduler status for Daily Faceoff collectors.</p></div></header>
<main><div class="shell">
<a class="back" href="/">← Back to Pickup Watch</a>
<div class="grid">
@foreach($collectors as $collector)
<section class="card">
<div class="top">
<h2>{{ $collector['name'] }}</h2>
<span class="pill {{ $collector['healthy']?'ok':'bad' }}">{{ $collector['healthy']?'Healthy':'Stale / Not run' }}</span>
</div>
<dl>
<dt>Schedule</dt><dd>{{ $collector['schedule'] }}</dd>
<dt>Artisan command</dt><dd><code>{{ $collector['command'] }}</code></dd>
<dt>Last data update</dt><dd>@if($collector['last_run']){{ $collector['last_run']->setTimezone('America/Halifax')->format('M j, Y g:i A T') }}@else Never @endif</dd>
<dt>Next scheduled run</dt><dd>{{ $collector['next_run']->setTimezone('America/Halifax')->format('M j, Y g:i A T') }}</dd>
<dt>Stored records</dt><dd>{{ number_format($collector['records']) }}</dd>
</dl>
</section>
@endforeach
</div>
<p class="note">Current Atlantic time: {{ $now->format('M j, Y g:i:s A T') }}. Health is based on the latest collector timestamps stored in MySQL.</p>
</div></main>
</body>
</html>
