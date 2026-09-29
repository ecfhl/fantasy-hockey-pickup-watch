<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FantraxAvailablePlayers
{
    private const API_VERSION = '186.1.9';

    public function url(string $leagueId, CarbonImmutable $date, string $positionGroup='ALL'): string
    {
        $day=$date->format('Y-m-d');
        return 'https://www.fantrax.com/fantasy/league/'.$leagueId.'/players;maxResultsPerPage=500;pageNumber=1;positionOrGroup='.$positionGroup.';seasonOrProjection=PROJECTION_0_31n_SEASON;timeframeTypeCode=PROJECTED_SEASON;startDate='.$day.';endDate='.$day.';datePlaying='.$day;
    }

    public function fetch(string $leagueId, CarbonImmutable $date, string $positionGroup='ALL'): array
    {
        if(!preg_match('/^[A-Za-z0-9_-]{5,64}$/D',$leagueId)) throw new RuntimeException('Invalid Fantrax league ID.');

        $day=$date->format('Y-m-d');
        $url=$this->url($leagueId,$date,$positionGroup);
        $requestData=[
            'statusOrTeamFilter'=>'ALL_AVAILABLE',
            'maxResultsPerPage'=>500,
            'pageNumber'=>'1',
            'seasonOrProjection'=>'PROJECTION_0_31n_SEASON',
            'timeframeTypeCode'=>'PROJECTED_SEASON',
            'startDate'=>$day,
            'endDate'=>$day,
            'datePlaying'=>$day,
        ];
        if($positionGroup!=='ALL') $requestData['positionOrGroup']=$positionGroup;

        $payload=[
            'msgs'=>[['method'=>'getPlayerStats','data'=>$requestData]],
            'uiv'=>3,'refUrl'=>$url,'dt'=>0,'at'=>0,'av'=>'0.0',
            'tz'=>'America/Halifax','v'=>self::API_VERSION,
        ];

        $response=Http::timeout(60)->retry(2,1500)->withHeaders([
            'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/153 Safari/537.36',
            'Accept'=>'application/json',
            'Content-Type'=>'application/json',
            'Referer'=>$url,
        ])->post('https://www.fantrax.com/fxpa/req?leagueId='.$leagueId,$payload);
        $response->throw();

        $json=$response->json();
        if(!is_array($json)) throw new RuntimeException('Fantrax returned invalid JSON.');
        if(!empty($json['pageError']['code'])) throw new RuntimeException('This Fantrax league is private, inaccessible, or does not expose its player list publicly.');

        $data=$json['responses'][0]['data']??null;
        if(!is_array($data)) throw new RuntimeException('Fantrax returned no player response data.');
        $stats=$data['statsTable']??[];
        if(!$stats){
            $total=$data['paginatedResultSet']['totalNumResults']??$data['paginatedResultSet']['totalResults']??null;
            if((int)$total===0) return ['url'=>$url,'rows'=>[]];
            throw new RuntimeException('Fantrax returned no player rows.');
        }

        $columns=[];
        foreach(($data['tableHeader']['cells']??[]) as $i=>$col){
            foreach(['key','sortType','shortName'] as $field){
                $key=trim((string)($col[$field]??''));
                if($key!==''&&!isset($columns[$key])) $columns[$key]=$i;
            }
        }
        $cell=function(array $entry,array $ids)use($columns):?array{
            foreach($ids as $id) if(isset($columns[$id])) return $entry['cells'][$columns[$id]]??null;
            return null;
        };

        $rows=[];
        foreach($stats as $rank=>$entry){
            $scorer=$entry['scorer']??[];
            $name=trim((string)($scorer['name']??''));
            $team=strtoupper(trim((string)($scorer['teamShortName']??'')));
            if($name===''||$team==='') continue;

            $statusCell=$cell($entry,['status','STATUS','Sta']);
            $statusRaw=trim(html_entity_decode(strip_tags((string)($statusCell['content']??''))));
            $statusUpper=strtoupper($statusRaw);
            if($statusUpper!=='FA'&&!str_starts_with($statusUpper,'W')) continue;

            $oppCell=$cell($entry,['opponent','Opp']);
            $oppRaw=trim(html_entity_decode(strip_tags(str_replace(['<br>','<br/>','<br />'],' ',(string)($oppCell['content']??'')))));
            if($oppRaw!=='') $oppRaw=preg_split('/\s+/',$oppRaw)[0];
            $away=str_starts_with($oppRaw,'@');
            $opponent=strtoupper(ltrim($oppRaw,'@'));

            $fptsCell=$cell($entry,['fpts','SCORE','FPts']);
            $fpts=$this->numeric($fptsCell['content']??null);

            $posValue=$scorer['posShortNames']??$entry['multiPositions']??'';
            $posText=is_array($posValue)?implode(',',$posValue):html_entity_decode(strip_tags((string)$posValue));
            $position=$this->position($posText);
            if($positionGroup==='G'&&$position!=='G') continue;

            $waiverDay=null;
            if(preg_match('/W\s*\(([^)]+)\)/i',$statusRaw,$m)) $waiverDay=trim($m[1]);

            $rows[]=[
                'player_name'=>$name,
                'team'=>$team,
                'position'=>$position,
                'opponent'=>$opponent,
                'home_away'=>$away?'AWAY':'HOME',
                'availability'=>str_starts_with($statusUpper,'W')?'W':'FA',
                'waiver_day'=>$waiverDay,
                'injury_status'=>$this->injury($scorer['icons']??[]),
                'projected_fpts'=>$fpts,
                'source_rank'=>(int)($scorer['rank']??($rank+1)),
                'fantrax_url'=>'https://www.fantrax.com/fantasy/league/'.$leagueId.'/players;searchName='.rawurlencode($name).';positionOrGroup=ALL;',
            ];
        }

        if(!$rows&&$positionGroup!=='G') throw new RuntimeException('Fantrax returned rows but none were parseable as available players.');
        return ['url'=>$url,'rows'=>$rows];
    }

    public function diagnoseLeague(string $leagueId): array
    {
        if(!preg_match('/^[A-Za-z0-9_-]{5,64}$/D',$leagueId)) {
            return ['accessible'=>false,'has_players'=>false];
        }

        $url='https://www.fantrax.com/fantasy/league/'.$leagueId.'/players';
        $payload=[
            'msgs'=>[['method'=>'getPlayerStats','data'=>[
                'statusOrTeamFilter'=>'ALL_AVAILABLE',
                'maxResultsPerPage'=>1,
                'pageNumber'=>'1',
                'seasonOrProjection'=>'PROJECTION_0_31n_SEASON',
                'timeframeTypeCode'=>'PROJECTED_SEASON',
            ]]],
            'uiv'=>3,'refUrl'=>$url,'dt'=>0,'at'=>0,'av'=>'0.0',
            'tz'=>'America/Halifax','v'=>self::API_VERSION,
        ];

        try{
            $response=Http::timeout(30)->retry(1,1000)->withHeaders([
                'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/153 Safari/537.36',
                'Accept'=>'application/json',
                'Content-Type'=>'application/json',
                'Referer'=>$url,
            ])->post('https://www.fantrax.com/fxpa/req?leagueId='.$leagueId,$payload)->throw();

            $json=$response->json();
            if(!is_array($json)||!empty($json['pageError']['code'])) {
                return ['accessible'=>false,'has_players'=>false];
            }

            $data=$json['responses'][0]['data']??null;
            if(!is_array($data)) return ['accessible'=>false,'has_players'=>false];

            $stats=$data['statsTable']??[];
            $total=$data['paginatedResultSet']['totalNumResults']??$data['paginatedResultSet']['totalResults']??null;
            $hasPlayers=!empty($stats)||((int)$total>0);

            return ['accessible'=>true,'has_players'=>$hasPlayers];
        }catch(\Throwable){
            return ['accessible'=>false,'has_players'=>false];
        }
    }

    private function position(string $value): ?string
    {
        $v=strtoupper($value);
        if(preg_match('/(^|[,\/ ])G($|[,\/ ])/',$v)) return 'G';
        if(preg_match('/(^|[,\/ ])D($|[,\/ ])/',$v)) return 'D';
        if(preg_match('/\b(C|LW|RW|F)\b/',$v)) return 'F';
        return $value!==''?$value:null;
    }

    private function injury(array $icons): ?string
    {
        foreach($icons as $icon){
            $type=(string)($icon['typeId']??'');
            $tip=trim((string)($icon['tooltip']??''));
            if(in_array($type,['1','2','30'],true)||preg_match('/injur|IR|day-to-day|out indefinitely/i',$tip)){
                return preg_match('/injured reserve|injured list|\bIR\b/i',$tip)?'IR':($tip!==''?$tip:'INJ');
            }
        }
        return null;
    }

    private function numeric(mixed $value): ?float
    {
        if($value===null||$value==='') return null;
        $value=preg_replace('/[^0-9.\-]/','',html_entity_decode(strip_tags((string)$value)));
        return is_numeric($value)?(float)$value:null;
    }
}
