<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Fantasy Hockey Pickup Watch</title>
<style>
:root{--bg:#f6f7fb;--surface:#fff;--text:#172033;--muted:#667085;--line:#dfe3ea;--accent:#2563eb}
*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:var(--bg);color:var(--text)}
.shell{width:min(1180px,calc(100% - 32px));margin:auto}.hero{background:#0b1739;color:#fff;padding:26px 0}.hero h1{margin:0 0 6px;font-size:30px}.hero p{margin:0;color:#c8d2ee}
main{padding:24px 0 50px}.panel,.table-card{background:var(--surface);border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 8px rgba(15,23,42,.04)}
.panel{padding:16px;margin-bottom:18px}.league-form{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.field{display:flex;flex-direction:column;gap:6px;min-width:min(420px,100%);flex:1}.field label{font-size:12px;font-weight:800;color:var(--muted)}input[type=text],input[type=search]{border:1px solid var(--line);border-radius:9px;padding:10px 12px;background:#fff;color:var(--text);font:inherit}
.button{border:1px solid #1d4ed8;background:#2563eb;color:white;border-radius:9px;padding:10px 14px;font-weight:800;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}.button.secondary{background:#fff;color:#334155;border-color:var(--line)}
.message{padding:11px 13px;border-radius:10px;margin-top:12px;font-weight:700}.message.ok{background:#dcfce7;color:#166534;border:1px solid #86efac}.message.error{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}
.meta{font-size:12px;color:var(--muted);margin-top:8px}.date-tabs{display:flex;gap:8px;margin:18px 0}.date-tabs .button:not(.active){background:#e5e7eb;color:#334155;border-color:#d1d5db}.section{margin:28px 0}.section h2{margin:0}.section-title p{margin:5px 0 0;color:var(--muted);font-size:13px}
.controls{display:flex;flex-direction:column;gap:8px;margin:10px 0 12px}.search{width:min(360px,100%)}.filters{display:flex;gap:8px;flex-wrap:wrap}.filter{border:1px solid var(--line);background:#fff;color:var(--text);border-radius:999px;padding:7px 13px;font-weight:800;font-size:12px;cursor:pointer}
.filter[data-line="1"].active,.filter[data-goalie="1"].active,.filter[data-pp="1"].active{background:#dcfce7;color:#166534;border-color:#86efac}
.filter[data-line="2"].active,.filter[data-goalie="2"].active,.filter[data-pp="2"].active{background:#fef3c7;color:#92400e;border-color:#fcd34d}
.filter[data-line="3"].active{background:#ffedd5;color:#9a3412;border-color:#fdba74}.filter[data-line="4"].active{background:#fee2e2;color:#b91c1c;border-color:#fca5a5}
.table-card{overflow:hidden}.table-scroll{overflow:auto}table{width:100%;border-collapse:collapse}th,td{padding:11px 12px;border-bottom:1px solid var(--line);text-align:left;font-size:13px}th{background:#f8fafc;color:#475569;font-size:12px}.pill{display:inline-flex;align-items:center;border:1px solid transparent;border-radius:999px;padding:4px 7px;font-size:10px;font-weight:800;white-space:nowrap}
.l1,.g1,.pp1{background:#dcfce7;color:#166534;border-color:#86efac}.l2,.g2,.pp2{background:#fef3c7;color:#92400e;border-color:#fcd34d}.l3{background:#ffedd5;color:#9a3412;border-color:#fdba74}.l4{background:#fee2e2;color:#b91c1c;border-color:#fca5a5}.ir{background:#dc2626;color:#fff;padding:2px 6px;font-size:10px}.waiver{background:#fff7d6;color:#713f12;border-color:#eab308}.fa{background:#e5e7eb;color:#374151;border-color:#d1d5db}.confirmed{background:#16a34a;color:#fff}.probable{background:#facc15;color:#422006}.unconfirmed{background:#e5e7eb;color:#374151}
.player{display:flex;align-items:center;gap:6px;font-weight:800}.badges{display:inline-flex;gap:4px}.add{background:#2563eb;color:#fff;text-decoration:none;border-radius:8px;padding:6px 11px;font-weight:800;font-size:12px}.more{display:block;margin:14px auto 0}.empty{padding:18px;text-align:center;color:var(--muted)}.note{font-size:12px;color:var(--muted)}
@media(max-width:650px){.shell{width:min(100% - 20px,1180px)}.hero{padding:20px 0}.hero h1{font-size:24px}.league-form{align-items:stretch}.league-form .button{width:100%}.table-card{background:transparent;border:0;box-shadow:none}.table-scroll{overflow:visible}table,tbody{display:block}thead{display:none}tr{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;background:#fff;border:1px solid var(--line);border-radius:13px;padding:13px;margin-bottom:10px;position:relative;min-height:105px}td{border:0;padding:0;font-size:12px}td[data-label="Player"],td[data-label="Goalie"]{grid-column:1;padding-right:66px}.proj{position:absolute;right:13px;top:13px;font-size:20px;font-weight:800}.opponent{display:block;color:#15803d;font-size:11px;margin-top:4px}.status{position:absolute;right:92px;bottom:13px}.add-cell{position:absolute;right:13px;bottom:10px}.starting{position:absolute;left:13px;bottom:13px}.skater-row{padding-bottom:46px}.skater-row .badges{position:absolute;left:13px;bottom:14px}.desktop-opponent{display:none}}
</style>
</head>
<body>
<header class="hero"><div class="shell"><h1>Fantasy Hockey Pickup Watch</h1><p>Available Fantrax players enhanced with Daily Faceoff lineup information.</p></div></header>
<main><div class="shell">
<section class="panel">
<form method="post" action="{{ route('refresh.fantrax') }}" class="league-form">
@csrf
<div class="field"><label for="league_id">Fantrax League ID</label><input id="league_id" name="league_id" type="text" value="{{ $leagueId }}" list="known-leagues" placeholder="Enter Fantrax league ID" required><datalist id="known-leagues">@foreach($knownLeagues as $known)<option value="{{ $known }}"></option>@endforeach</datalist></div>
<button class="button" type="submit">Refresh Fantrax Players</button>
</form>
@if(session('success'))<div class="message ok">{{ session('success') }}</div>@endif
@if(session('error'))<div class="message error">{{ session('error') }}</div>@endif
@if($leagueId)
<div class="meta">Selected league: <strong>{{ $leagueId }}</strong>@if($league?->last_refresh_at) · Fantrax last refreshed <span data-age="{{ $league->last_refresh_at }}"></span>@endif</div>
@else
<div class="meta">Enter a league ID and refresh it to load that league's available players. Other saved leagues are not changed.</div>
@endif
</section>

@if($leagueId)
<div class="date-tabs"><a class="button {{ $date===$today?'active':'' }}" href="/?league={{ urlencode($leagueId) }}&date={{ $today }}">Today</a><a class="button {{ $date===$tomorrow?'active':'' }}" href="/?league={{ urlencode($leagueId) }}&date={{ $tomorrow }}">Tomorrow</a></div>
<p class="note">Showing {{ $selectedDate->format('F j, Y') }} for league {{ $leagueId }}.</p>

@php
$sections=[
 ['G','goalies','Available Goalies','Goalies playing this date, ordered by Daily Faceoff starting status and projected season fantasy points.'],
 ['F','forwards','Available Forwards','Available forwards ranked by projected season fantasy points.'],
 ['D','defensemen','Available Defensemen','Available defensemen ranked by projected season fantasy points.'],
];
@endphp

@foreach($sections as [$position,$id,$title,$subtitle])
<section class="section" id="{{ $id }}">
<div class="section-title"><h2>{{ $title }}</h2><p>{{ $subtitle }}</p></div>
<div class="controls">
<input class="search" type="search" placeholder="Search {{ strtolower(str_replace('Available ','',$title)) }}..." aria-label="Search {{ $title }}">
<div class="filters" data-filter-zone></div>
</div>
<div class="table-card"><div class="table-scroll"><table><thead><tr><th>Player</th><th>Opponent</th><th>Status</th>@if($position==='G')<th>Starting</th>@endif<th>Proj. FPts</th><th>Fantrax</th></tr></thead><tbody>
@forelse($groups[$position] as $player)
@php($away=str_starts_with($player['opponent'],'@'))
<tr class="result-row {{ $position!=='G'?'skater-row':'' }}">
<td data-label="{{ $position==='G'?'Goalie':'Player' }}"><div class="player">@if(!empty($player['injury_status'])&&preg_match('/IR/i',$player['injury_status']))<span class="pill ir">IR</span>@endif<span>{{ $player['name'] }} ({{ $player['team'] }})</span><span class="badges">@if($player['line_number'])<span class="pill {{ $position==='G'?'g'.$player['line_number']:'l'.$player['line_number'] }}" data-line="{{ $player['line_number'] }}" data-goalie="{{ $position==='G'?$player['line_number']:'' }}">{{ $position==='G'?'G':'L' }}{{ $player['line_number'] }}</span>@endif @if($position!=='G'&&$player['pp_unit'])<span class="pill pp{{ $player['pp_unit'] }}" data-pp="{{ $player['pp_unit'] }}">PP{{ $player['pp_unit'] }}</span>@endif</span></div><span class="opponent">{{ $away?'':'vs ' }}{{ $player['opponent'] }}</span></td>
<td class="desktop-opponent">{{ $player['opponent'] }}</td>
<td class="status">@if(str_starts_with($player['status'],'W'))<span class="pill waiver">{{ $player['status'] }}</span>@else<span class="pill fa">FA</span>@endif</td>
@if($position==='G')<td class="starting">@php($ss=strtolower((string)($player['starting_status']??'')))<span class="pill {{ $ss==='confirmed'?'confirmed':($ss==='probable'?'probable':'unconfirmed') }}">{{ $player['starting_status'] ?: 'NA' }}</span></td>@endif
<td><span class="proj">{{ $player['projected_points']===null?'—':number_format($player['projected_points'],0) }}</span></td>
<td class="add-cell"><a class="add" href="{{ $player['fantrax_url'] }}" target="_blank" rel="noopener">+ Add</a></td>
</tr>
@empty
<tr><td colspan="{{ $position==='G'?6:5 }}" class="empty">No available players for this league/date.</td></tr>
@endforelse
</tbody></table></div></div>
<button class="button more" type="button">See more results</button>
</section>
@endforeach
@endif
</div></main>

<script>
document.querySelectorAll('[data-age]').forEach(el=>{
 const then=new Date(el.dataset.age.replace(' ','T')+'Z');
 const seconds=Math.max(0,Math.floor((Date.now()-then.getTime())/1000));
 let text;
 if(seconds<60) text=seconds+' '+(seconds===1?'second':'seconds')+' ago';
 else if(seconds<3600){const m=Math.floor(seconds/60);text=m+' '+(m===1?'minute':'minutes')+' ago';}
 else if(seconds<86400){const h=Math.floor(seconds/3600),m=Math.floor((seconds%3600)/60);text=h+' '+(h===1?'hour':'hours')+' '+m+' '+(m===1?'minute':'minutes')+' ago';}
 else{const d=Math.floor(seconds/86400),h=Math.floor((seconds%86400)/3600),m=Math.floor((seconds%3600)/60);text=d+' '+(d===1?'day':'days')+' '+h+' '+(h===1?'hour':'hours')+' '+m+' '+(m===1?'minute':'minutes')+' ago';}
 el.textContent=text;
});

document.querySelectorAll('.section').forEach(section=>{
 const rows=[...section.querySelectorAll('.result-row')];
 const search=section.querySelector('.search');
 const zone=section.querySelector('[data-filter-zone]');
 const more=section.querySelector('.more');
 const goalie=section.id==='goalies';
 let visible=5,term='';
 const availableLines=[1,2,3,4].filter(n=>rows.some(r=>r.querySelector('[data-line="'+n+'"]')));
 const selectedLines=new Set(availableLines.map(String));
 const selectedGoalies=new Set(['1','2']);
 const selectedPp=new Set();

 if(goalie){
   [1,2].forEach(n=>{
     const b=document.createElement('button');b.type='button';b.className='filter active';b.dataset.goalie=n;b.textContent='G'+n;b.setAttribute('aria-pressed','true');zone.appendChild(b);
   });
 }else{
   availableLines.forEach(n=>{const b=document.createElement('button');b.type='button';b.className='filter active';b.dataset.line=n;b.textContent='L'+n;b.setAttribute('aria-pressed','true');zone.appendChild(b);});
   [1,2].forEach(n=>{const b=document.createElement('button');b.type='button';b.className='filter';b.dataset.pp=n;b.textContent='PP'+n;b.setAttribute('aria-pressed','false');zone.appendChild(b);});
 }

 const match=r=>{
   if(term&&!r.textContent.toLowerCase().includes(term)) return false;
   if(goalie){
     const hasDepth=rows.some(x=>x.querySelector('[data-goalie]'));
     if(hasDepth&&selectedGoalies.size){
       const p=r.querySelector('[data-goalie]');if(!p||!selectedGoalies.has(p.dataset.goalie)) return false;
     }
   }else{
     if(selectedLines.size){
       const p=r.querySelector('[data-line]');if(!p||!selectedLines.has(p.dataset.line)) return false;
     }
     if(selectedPp.size){
       const p=r.querySelector('[data-pp]');if(!p||!selectedPp.has(p.dataset.pp)) return false;
     }
   }
   return true;
 };

 const render=()=>{
   const eligible=rows.filter(match);
   rows.forEach(r=>r.style.display='none');
   eligible.slice(0,visible).forEach(r=>r.style.display='');
   more.style.display=eligible.length>visible?'block':'none';
 };

 search.addEventListener('input',()=>{term=search.value.trim().toLowerCase();visible=5;render();});
 zone.addEventListener('click',e=>{
   const b=e.target.closest('.filter');if(!b)return;
   const key=b.dataset.goalie||b.dataset.line||b.dataset.pp;
   const set=b.dataset.goalie?selectedGoalies:(b.dataset.line?selectedLines:selectedPp);
   if(set.has(key)){set.delete(key);b.classList.remove('active');b.setAttribute('aria-pressed','false');}
   else{set.add(key);b.classList.add('active');b.setAttribute('aria-pressed','true');}
   visible=5;render();
 });
 more.addEventListener('click',()=>{visible+=10;render();});
 render();
});
</script>
</body>
</html>
