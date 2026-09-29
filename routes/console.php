<?php

use App\Support\DailyFaceoffPowerPlay;
use App\Support\DailyFaceoffStartingGoalies;
use App\Support\FantraxRefresh;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('pickup:refresh-fantrax {leagueId}', function (FantraxRefresh $refresh) {
    try{
        $result=$refresh->refresh((string)$this->argument('leagueId'));
        $this->info('Updated '.$result['league_id'].': '.$result['today'].' today, '.$result['tomorrow'].' tomorrow.');
        return 0;
    }catch(\Throwable $e){
        $this->error($e->getMessage());
        return 1;
    }
});

Artisan::command('pickup:refresh-goalies', function (DailyFaceoffStartingGoalies $collector) {
    $abbr=[
        'Anaheim Ducks'=>'ANA','Boston Bruins'=>'BOS','Buffalo Sabres'=>'BUF','Calgary Flames'=>'CGY',
        'Carolina Hurricanes'=>'CAR','Chicago Blackhawks'=>'CHI','Colorado Avalanche'=>'COL','Columbus Blue Jackets'=>'CBJ',
        'Dallas Stars'=>'DAL','Detroit Red Wings'=>'DET','Edmonton Oilers'=>'EDM','Florida Panthers'=>'FLA',
        'Los Angeles Kings'=>'LAK','Minnesota Wild'=>'MIN','Montreal Canadiens'=>'MTL','Nashville Predators'=>'NSH',
        'New Jersey Devils'=>'NJD','New York Islanders'=>'NYI','New York Rangers'=>'NYR','Ottawa Senators'=>'OTT',
        'Philadelphia Flyers'=>'PHI','Pittsburgh Penguins'=>'PIT','San Jose Sharks'=>'SJS','Seattle Kraken'=>'SEA',
        'St. Louis Blues'=>'STL','Tampa Bay Lightning'=>'TBL','Toronto Maple Leafs'=>'TOR','Utah Mammoth'=>'UTA',
        'Vancouver Canucks'=>'VAN','Vegas Golden Knights'=>'VGK','Washington Capitals'=>'WSH','Winnipeg Jets'=>'WPG',
    ];

    $now=CarbonImmutable::now('America/Halifax');
    $failed=false;
    foreach([$now->startOfDay(),$now->addDay()->startOfDay()] as $date){
        try{
            $data=$collector->fetch($date);
            $stamp=CarbonImmutable::now();
            DB::transaction(function()use($data,$date,$stamp,$abbr){
                DB::table('active_starting_goalies')->whereDate('game_date',$date->toDateString())->delete();
                $rows=[];
                foreach($data['rows'] as $row){
                    $team=$abbr[$row['team_name']]??null;
                    $opponent=$abbr[$row['opponent_name']]??null;
                    if(!$team||!$opponent) continue;
                    $rows[]=[
                        'game_date'=>$date->toDateString(),
                        'player_name'=>$row['player_name'],
                        'team'=>$team,
                        'opponent'=>$opponent,
                        'home_away'=>$row['home_away'],
                        'starting_status'=>$row['starting_status'],
                        'source_updated_at'=>$row['source_updated_at'],
                        'source_url'=>$data['url'],
                        'checked_at'=>$stamp,
                        'created_at'=>$stamp,
                        'updated_at'=>$stamp,
                    ];
                }
                if($rows) DB::table('active_starting_goalies')->insert($rows);
            });
            $this->info(count($data['rows']).' goalies for '.$date->toDateString());
        }catch(\Throwable $e){
            $failed=true;
            $this->error($date->toDateString().': '.$e->getMessage());
        }
    }
    return $failed?1:0;
});

Artisan::command('pickup:refresh-lines', function (DailyFaceoffPowerPlay $collector) {
    $failed=false;
    $stamp=CarbonImmutable::now();

    foreach(DailyFaceoffPowerPlay::TEAMS as $team=>$slug){
        try{
            $data=$collector->fetch($team,$slug);
            DB::transaction(function()use($team,$data,$stamp){
                DB::table('active_pp_lines')->where('team',$team)->delete();
                DB::table('active_line_combinations')->where('team',$team)->delete();

                $pp=[];
                foreach($data['players'] as $row){
                    $pp[]=[
                        'team'=>$team,'player_name'=>$row['player_name'],'pp_unit'=>$row['pp_unit'],
                        'unit_position'=>$row['unit_position'],'source_url'=>$data['url'],
                        'last_update'=>$data['lastUpdate'],'checked_at'=>$stamp,'created_at'=>$stamp,'updated_at'=>$stamp,
                    ];
                }
                if($pp) DB::table('active_pp_lines')->insert($pp);

                $lines=[];
                foreach($data['lines'] as $row){
                    $lines[]=[
                        'team'=>$team,'player_name'=>$row['player_name'],'position_group'=>$row['position_group'],
                        'line_number'=>$row['line_number'],'unit_position'=>$row['unit_position'],'source_url'=>$data['url'],
                        'last_update'=>$data['lastUpdate'],'checked_at'=>$stamp,'created_at'=>$stamp,'updated_at'=>$stamp,
                    ];
                }
                if($lines) DB::table('active_line_combinations')->insert($lines);
            });
            $this->line($team.': updated');
        }catch(\Throwable $e){
            $failed=true;
            $this->error($team.': '.$e->getMessage());
        }
    }

    return $failed?1:0;
});

Schedule::command('pickup:refresh-goalies')->everyThirtyMinutes()->withoutOverlapping(25)->runInBackground();
Schedule::command('pickup:refresh-lines')->cron('2 */4 * * *')->withoutOverlapping(240)->runInBackground();
