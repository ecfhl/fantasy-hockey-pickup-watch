<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DailyFaceoffPowerPlay
{
    public const TEAMS=[
        'ANA'=>'anaheim-ducks','BOS'=>'boston-bruins','BUF'=>'buffalo-sabres','CGY'=>'calgary-flames',
        'CAR'=>'carolina-hurricanes','CHI'=>'chicago-blackhawks','COL'=>'colorado-avalanche','CBJ'=>'columbus-blue-jackets',
        'DAL'=>'dallas-stars','DET'=>'detroit-red-wings','EDM'=>'edmonton-oilers','FLA'=>'florida-panthers',
        'LAK'=>'los-angeles-kings','MIN'=>'minnesota-wild','MTL'=>'montreal-canadiens','NSH'=>'nashville-predators',
        'NJD'=>'new-jersey-devils','NYI'=>'new-york-islanders','NYR'=>'new-york-rangers','OTT'=>'ottawa-senators',
        'PHI'=>'philadelphia-flyers','PIT'=>'pittsburgh-penguins','SJS'=>'san-jose-sharks','SEA'=>'seattle-kraken',
        'STL'=>'st-louis-blues','TBL'=>'tampa-bay-lightning','TOR'=>'toronto-maple-leafs','UTA'=>'utah-mammoth',
        'VAN'=>'vancouver-canucks','VGK'=>'vegas-golden-knights','WSH'=>'washington-capitals','WPG'=>'winnipeg-jets',
    ];

    public function fetch(string $team,string $slug): array
    {
        $url="https://www.dailyfaceoff.com/teams/{$slug}/line-combinations";
        $response=Http::withHeaders([
            'User-Agent'=>'Mozilla/5.0 (compatible; FantasyHockeyPickupWatch/1.0)',
            'Accept'=>'text/html,application/xhtml+xml',
            'Accept-Language'=>'en-US,en;q=0.9',
        ])->timeout(30)->retry(2,1000)->get($url);
        $response->throw();
        $html=$response->body();
        $lastUpdate=$this->extractLastUpdate($html,$team);
        $lines=[];

        $forwardStart=stripos($html,'Forwards');
        $defenseStart=stripos($html,'Defensive Pairings');
        if($forwardStart===false||$defenseStart===false||$defenseStart<=$forwardStart) throw new RuntimeException("Could not find {$team} even-strength lines");

        $forwardNames=$this->playerNames(substr($html,$forwardStart,$defenseStart-$forwardStart));
        if(count($forwardNames)<9) throw new RuntimeException("Expected at least 9 forwards for {$team}, found ".count($forwardNames));
        foreach(array_slice($forwardNames,0,12) as $position=>$name){
            $lines[]=['player_name'=>$name,'position_group'=>'F','line_number'=>min(4,intdiv($position,3)+1),'unit_position'=>($position%3)+1];
        }

        $defenseEnd=stripos($html,'1st Powerplay Unit',$defenseStart);
        if($defenseEnd===false) throw new RuntimeException("Could not find {$team} defense section end");
        $defenseNames=$this->playerNames(substr($html,$defenseStart,$defenseEnd-$defenseStart));
        if(count($defenseNames)<6) throw new RuntimeException("Expected at least 6 defensemen for {$team}, found ".count($defenseNames));
        foreach(array_slice($defenseNames,0,7) as $position=>$name){
            $lines[]=['player_name'=>$name,'position_group'=>'D','line_number'=>min(4,intdiv($position,2)+1),'unit_position'=>($position%2)+1];
        }

        $goalieAnchor=stripos($html,'2nd Penalty Kill Unit');
        $goalieStart=$goalieAnchor===false?false:stripos($html,'Goalies',$goalieAnchor);
        if($goalieStart===false) throw new RuntimeException("Could not find {$team} goalie depth chart");
        $goalieEnd=stripos($html,'Injuries',$goalieStart);
        if($goalieEnd===false) $goalieEnd=strlen($html);
        $goalieNames=$this->playerNames(substr($html,$goalieStart,$goalieEnd-$goalieStart));
        if(count($goalieNames)<2) throw new RuntimeException("Expected 2 goalies for {$team}, found ".count($goalieNames));
        foreach(array_slice($goalieNames,0,2) as $position=>$name){
            $lines[]=['player_name'=>$name,'position_group'=>'G','line_number'=>$position+1,'unit_position'=>$position+1];
        }

        $players=[];
        foreach([1=>'1st Powerplay Unit',2=>'2nd Powerplay Unit'] as $unit=>$heading){
            $start=stripos($html,$heading);
            if($start===false) throw new RuntimeException("Could not find {$team} PP{$unit}");
            $endMarkers=$unit===1?['2nd Powerplay Unit']:['1st Penalty Kill Unit','Goalies','Injuries'];
            $end=strlen($html);
            foreach($endMarkers as $marker){
                $candidate=stripos($html,$marker,$start+strlen($heading));
                if($candidate!==false) $end=min($end,$candidate);
            }
            $names=array_slice($this->playerNames(substr($html,$start,$end-$start)),0,5);
            if(count($names)!==5) throw new RuntimeException("Expected 5 players on {$team} PP{$unit}, found ".count($names));
            foreach($names as $position=>$name) $players[]=['player_name'=>$name,'pp_unit'=>$unit,'unit_position'=>$position+1];
        }

        return compact('team','url','lastUpdate','players','lines');
    }

    private function playerNames(string $segment): array
    {
        preg_match_all('/<a\b[^>]*href=["\'][^"\']*\/players\/[^"\']+["\'][^>]*>(.*?)<\/a>/is',$segment,$matches);
        $names=[];
        foreach($matches[1]??[] as $label){
            $name=trim(preg_replace('/\s+/',' ',html_entity_decode(strip_tags($label),ENT_QUOTES|ENT_HTML5)));
            if($name!==''&&!in_array($name,$names,true)) $names[]=$name;
        }
        return $names;
    }

    private function extractLastUpdate(string $html,string $team): CarbonImmutable
    {
        $candidates=[
            html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5),
            html_entity_decode($html,ENT_QUOTES|ENT_HTML5),
            str_replace(['\\u003A','\\u002D','\\/'],[':','-','/'],$html),
        ];
        $iso='\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:\\.\\d+)?(?:Z|[+-]\\d{2}:?\\d{2})';
        foreach($candidates as $candidate) if(preg_match('/Last\\s*updated[^0-9]{0,250}('.$iso.')/is',$candidate,$m)) return CarbonImmutable::parse($m[1])->utc();
        foreach($candidates as $candidate) if(preg_match('/('.$iso.')/i',$candidate,$m)) return CarbonImmutable::parse($m[1])->utc();
        throw new RuntimeException("Could not find Daily Faceoff last-updated value for {$team}");
    }
}
