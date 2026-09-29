<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class PickupWatch
{
    public static function groups(string $leagueId,string $date): array
    {
        $groups=['G'=>[],'F'=>[],'D'=>[]];
        if($leagueId==='') return $groups;

        $daily=DB::table('active_daily_players')
            ->where('league_id',$leagueId)
            ->whereDate('game_date',$date)
            ->orderByRaw('projected_fpts IS NULL')
            ->orderByDesc('projected_fpts')
            ->orderBy('source_rank')
            ->get();

        $normalize=static fn($v)=>preg_replace('/[^\pL\pN]+/u','',mb_strtolower(trim((string)$v)))??'';

        $dfo=DB::table('active_starting_goalies')->whereDate('game_date',$date)->get();
        $dfoByPlayer=[];$confirmedByTeam=[];
        foreach($dfo as $g){
            $team=strtoupper(trim($g->team));
            $key=$team.'|'.$normalize($g->player_name);
            $dfoByPlayer[$key]=$g;
            if(strtolower(trim((string)$g->starting_status))==='confirmed') $confirmedByTeam[$team]=$normalize($g->player_name);
        }

        foreach($daily as $row){
            $position=strtoupper(trim((string)$row->position));
            if(!isset($groups[$position])) continue;

            $status=strtoupper(trim((string)$row->availability));
            if($status==='W') $status='W'.($row->waiver_day?' ('.$row->waiver_day.')':'');
            if(!preg_match('/^(FA|W(?:\s*\([^)]+\))?)$/',$status)) continue;

            $opponent=trim((string)$row->opponent);
            if($opponent==='') continue;
            $opponent=strtoupper((string)$row->home_away)==='AWAY'?'@'.$opponent:$opponent;

            $player=[
                'name'=>$row->player_name,
                'team'=>$row->team,
                'position'=>$position,
                'opponent'=>$opponent,
                'status'=>$status,
                'injury_status'=>$row->injury_status,
                'projected_points'=>$row->projected_fpts===null?null:(float)$row->projected_fpts,
                'source_rank'=>(int)($row->source_rank??PHP_INT_MAX),
                'fantrax_url'=>$row->fantrax_url,
                'starting_status'=>null,
                'not_starting'=>false,
            ];

            if($position==='G'){
                $team=strtoupper(trim($row->team));
                $name=$normalize($row->player_name);
                $match=$dfoByPlayer[$team.'|'.$name]??null;
                if($match){
                    $player['starting_status']=$match->starting_status;
                    $player['opponent']=($match->home_away==='AWAY'?'@':'').$match->opponent;
                }
                $player['not_starting']=isset($confirmedByTeam[$team])&&$confirmedByTeam[$team]!==$name;
                if($player['not_starting']) $player['starting_status']='Not starting';
            }

            $groups[$position][]=$player;
        }

        foreach(['F','D'] as $position){
            usort($groups[$position],fn($a,$b)=>(($b['projected_points']??-PHP_FLOAT_MAX)<=>($a['projected_points']??-PHP_FLOAT_MAX))?:($a['source_rank']<=>$b['source_rank'])?:strcasecmp($a['name'],$b['name']));
        }

        $priority=static function($p):int{
            if(!empty($p['not_starting'])) return 4;
            return match(strtolower(trim((string)($p['starting_status']??'')))){
                'confirmed'=>0,'probable'=>1,'unconfirmed'=>2,default=>3,
            };
        };
        usort($groups['G'],fn($a,$b)=>($priority($a)<=>$priority($b))?:(($b['projected_points']??-PHP_FLOAT_MAX)<=>($a['projected_points']??-PHP_FLOAT_MAX))?:strcasecmp($a['name'],$b['name']));

        return $groups;
    }
}
