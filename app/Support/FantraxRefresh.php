<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class FantraxRefresh
{
    public function __construct(private FantraxAvailablePlayers $fantrax) {}

    public function refresh(string $leagueId): array
    {
        $leagueId=trim($leagueId);
        if(!preg_match('/^[A-Za-z0-9_-]{5,64}$/D',$leagueId)) throw new \InvalidArgumentException('Enter a valid Fantrax league ID.');

        $now=CarbonImmutable::now('America/Halifax');
        $dates=[$now->startOfDay(),$now->addDay()->startOfDay()];
        $prepared=[];

        foreach($dates as $date){
            // Fetch before deleting anything. Existing rows survive a Fantrax failure.
            $all=$this->fantrax->fetch($leagueId,$date,'ALL');
            $goalies=$this->fantrax->fetch($leagueId,$date,'G');

            $merged=[];
            foreach(array_merge($all['rows'],$goalies['rows']) as $row){
                $key=strtoupper($row['team']).'|'.mb_strtolower(trim($row['player_name']));
                $merged[$key]=$row;
            }
            $prepared[$date->toDateString()]=array_values($merged);
        }

        $todayCount=count($prepared[$dates[0]->toDateString()]??[]);
        $tomorrowCount=count($prepared[$dates[1]->toDateString()]??[]);

        if($todayCount===0&&$tomorrowCount===0){
            $diagnosis=$this->fantrax->diagnoseLeague($leagueId);

            if(!$diagnosis['accessible']){
                throw new \RuntimeException('This Fantrax league is private or does not expose its player list publicly.');
            }

            if($diagnosis['has_players']){
                throw new \RuntimeException('This Fantrax league is accessible, but it returned no players for today or tomorrow. It likely belongs to a previous season or has no games on these dates.');
            }

            throw new \RuntimeException('This Fantrax league appears to be from a previous or inactive season, or it has no publicly available player pool.');
        }

        $stamp=CarbonImmutable::now();
        DB::transaction(function()use($leagueId,$prepared,$stamp){
            DB::table('fantrax_leagues')->updateOrInsert(
                ['league_id'=>$leagueId],
                ['last_refresh_at'=>$stamp,'updated_at'=>$stamp,'created_at'=>$stamp]
            );

            foreach($prepared as $date=>$rows){
                DB::table('active_daily_players')->where('league_id',$leagueId)->whereDate('game_date',$date)->delete();

                $insert=[];
                foreach($rows as $row){
                    $insert[]=[
                        'league_id'=>$leagueId,
                        'game_date'=>$date,
                        'player_name'=>$row['player_name'],
                        'team'=>$row['team'],
                        'position'=>$row['position'],
                        'opponent'=>$row['opponent'],
                        'home_away'=>$row['home_away'],
                        'availability'=>$row['availability'],
                        'waiver_day'=>$row['waiver_day'],
                        'injury_status'=>$row['injury_status'],
                        'projected_fpts'=>$row['projected_fpts'],
                        'source_rank'=>$row['source_rank'],
                        'fantrax_url'=>$row['fantrax_url'],
                        'last_update'=>$stamp,
                        'created_at'=>$stamp,
                        'updated_at'=>$stamp,
                    ];
                }
                foreach(array_chunk($insert,250) as $chunk) if($chunk) DB::table('active_daily_players')->insert($chunk);
            }
        });

        return [
            'league_id'=>$leagueId,
            'today'=>count($prepared[$dates[0]->toDateString()]??[]),
            'tomorrow'=>count($prepared[$dates[1]->toDateString()]??[]),
            'refreshed_at'=>$stamp,
        ];
    }
}
