<?php

use App\Support\FantraxRefresh;
use App\Support\PickupWatch;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $now=CarbonImmutable::now('America/Halifax');
    $today=$now->toDateString();
    $tomorrow=$now->addDay()->toDateString();

    $leagueId=trim((string)$request->query('league',''));
    if($leagueId!==''&&!preg_match('/^[A-Za-z0-9_-]{5,64}$/D',$leagueId)) $leagueId='';

    $date=(string)$request->query('date',$today);
    if(!in_array($date,[$today,$tomorrow],true)) $date=$today;
    $selectedDate=CarbonImmutable::createFromFormat('!Y-m-d',$date,'America/Halifax');

    $groups=PickupWatch::groups($leagueId,$date);
    $norm=static fn($v)=>preg_replace('/[^\pL\pN]+/u','',mb_strtolower(trim((string)$v)))??'';

    $pp=[];
    foreach(DB::table('active_pp_lines')->get() as $row){
        $pp[strtoupper(trim($row->team)).'|'.$norm($row->player_name)]=(int)$row->pp_unit;
    }

    $lines=[];
    foreach(DB::table('active_line_combinations')->get() as $row){
        $lines[strtoupper(trim($row->team)).'|'.$norm($row->player_name).'|'.strtoupper(trim($row->position_group))]=(int)$row->line_number;
    }

    foreach($groups as $position=>&$players){
        foreach($players as &$player){
            $base=strtoupper(trim($player['team'])).'|'.$norm($player['name']);
            $player['pp_unit']=$pp[$base]??null;
            $player['line_number']=$lines[$base.'|'.$position]??null;
        }
        unset($player);
    }
    unset($players);

    $league= $leagueId!=='' ? DB::table('fantrax_leagues')->where('league_id',$leagueId)->first() : null;
    $knownLeagues=DB::table('fantrax_leagues')->orderByDesc('last_refresh_at')->pluck('league_id')->all();

    return view('home',compact('leagueId','date','today','tomorrow','selectedDate','groups','league','knownLeagues'));
});

Route::post('/refresh/fantrax', function (Request $request, FantraxRefresh $refresh) {
    $leagueId=trim((string)$request->input('league_id',''));
    if(!preg_match('/^[A-Za-z0-9_-]{5,64}$/D',$leagueId)){
        return redirect('/')->with('error','Enter a valid Fantrax League ID.');
    }

    try{
        $result=$refresh->refresh($leagueId);
        $message='Fantrax refreshed: '.$result['today'].' players today, '.$result['tomorrow'].' tomorrow.';
        return redirect('/?league='.rawurlencode($leagueId))->with('success',$message);
    }catch(\Throwable $e){
        return redirect('/?league='.rawurlencode($leagueId))->with('error','Fantrax refresh failed: '.$e->getMessage());
    }
})->middleware('throttle:10,1')->name('refresh.fantrax');


Route::get('/collector-status', function () {
    $tz='America/Halifax';
    $now=CarbonImmutable::now($tz);

    $goaliesLast=DB::table('active_starting_goalies')->max('checked_at');
    $linesLast=DB::table('active_line_combinations')->max('checked_at');
    $ppLast=DB::table('active_pp_lines')->max('checked_at');

    $goaliesCount=DB::table('active_starting_goalies')->count();
    $linesCount=DB::table('active_line_combinations')->count();
    $ppCount=DB::table('active_pp_lines')->count();

    $lastGoalies=$goaliesLast ? CarbonImmutable::parse($goaliesLast,$tz) : null;
    $lastLinesRaw=$linesLast ?: $ppLast;
    $lastLines=$lastLinesRaw ? CarbonImmutable::parse($lastLinesRaw,$tz) : null;

    $nextGoalies=$now->minute < 30
        ? $now->startOfHour()->addMinutes(30)
        : $now->addHour()->startOfHour();

    $nextLines=$now->startOfDay()->addHours(((int)floor($now->hour/4)+1)*4);

    $collectors=[
        [
            'name'=>'Starting Goalies',
            'command'=>'pickup:refresh-goalies',
            'schedule'=>'Every 30 minutes',
            'last_run'=>$lastGoalies,
            'next_run'=>$nextGoalies,
            'records'=>$goaliesCount,
            'healthy'=>$lastGoalies && $lastGoalies->greaterThanOrEqualTo($now->subMinutes(60)),
        ],
        [
            'name'=>'Lines & Power Play',
            'command'=>'pickup:refresh-lines',
            'schedule'=>'Every 4 hours',
            'last_run'=>$lastLines,
            'next_run'=>$nextLines,
            'records'=>$linesCount+$ppCount,
            'healthy'=>$lastLines && $lastLines->greaterThanOrEqualTo($now->subHours(8)),
        ],
    ];

    return view('collector-status',compact('collectors','now'));
})->name('collector.status');
