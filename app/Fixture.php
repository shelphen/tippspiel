<?php

namespace Todo;

use Exception;
use Log;
use Cache;
use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    const HOME_TEAM_KEY = 'homeTeam';

    const AWAY_TEAM_KEY = 'awayTeam';

    public static function getEm()
    {
        $fixturesFromCache = Cache::get('fixtures');

        if (Fixture::isValidCache($fixturesFromCache)) {
            Log::info('Fixtures from Cache');
            return $fixturesFromCache;
        }

        $fixturesFromWebService = Fixture::rest();

        if (!$fixturesFromWebService) {
            if (!$fixturesFromCache) {
                Log::alert('Neither REST service nor cache provide any data');
                return null;
            }
            Log::info('Fixtures from Cache');
            return $fixturesFromCache;
        }

        $fixturesFromWebService['_timestamp'] = gmdate('Y-m-d H:i:s');
        Cache::forever('fixtures', $fixturesFromWebService);

        Log::info('Fixtures from REST');
        return $fixturesFromWebService;
    }

    public static function rest()
    {
       $uri = 'http://api.football-data.org/v1/soccerseasons/424/fixtures';
//         $uri = 'http://0.0.0.0:8080/fixtures.json';
        $reqPrefs['http']['method'] = 'GET';
        $reqPrefs['http']['header'] = 'X-Auth-Token: ' . env('FOOTBALL_DATA_ORG_KEY');
        $stream_context = stream_context_create($reqPrefs);

        $response = @file_get_contents($uri, false, $stream_context);

        if (!$response) {
            Log::critical('REST request failed', $http_response_header);
            return null;
        }

        return json_decode($response, true);
    }

    public static function isValidCache($assocArrayWithUnderscoreTimestamp)
    {
        if (!$assocArrayWithUnderscoreTimestamp) {
            return false;
        }

        $cachedTime  = strtotime($assocArrayWithUnderscoreTimestamp['_timestamp']);
        $now = strtotime(gmdate('Y-m-d H:i:s'));
        $differenceInSeconds = $now - $cachedTime;

        if ($differenceInSeconds > 2.5) {
            return false;
        }

        return true;
    }

    public static function addUserBetsToFixtures($bets, $fixtures)
    {
        if (empty($bets)) {
            return $fixtures;
        }

        $fixturesCount = count($fixtures['fixtures']);

        for ($i = 0; $i < $fixturesCount; $i++) {
            $fixtureId = Fixture::extractFixtureId($fixtures['fixtures'][$i]);
            $indexOfBet = array_search($fixtureId, array_column($bets, 'fixture_id'));
            if ($indexOfBet !== false) {
                $fixtures['fixtures'][$i]['_bet'] = $bets[$indexOfBet];
                $fixtures['fixtures'][$i]['_bet']['valuation'] =
                    Fixture::calcValuation($bets[$indexOfBet], $fixtures['fixtures'][$i]);
            }
        }

        return $fixtures;
    }

    public static function calcValuation($bet, $fixture)
    {
        $bet['home_goals'] = (int)$bet['home_goals'];
        $bet['away_goals'] = (int)$bet['away_goals'];

        // Exact result
        if ($bet['home_goals'] === $fixture['result']['goalsHomeTeam']
            && $bet['away_goals'] === $fixture['result']['goalsAwayTeam']) {
            return 5;
        }

        // Goal difference
        if (($fixture['result']['goalsHomeTeam'] - $fixture['result']['goalsAwayTeam']) === ($bet['home_goals'] - $bet['away_goals'])) {
            return 3;
        }

        // Goal difference (draw)
        if ($fixture['result']['goalsHomeTeam'] === $fixture['result']['goalsAwayTeam'] && $bet['home_goals'] === $bet['away_goals']) {
            return 3;
        }

        //  Right winner
        if (($fixture['result']['goalsHomeTeam'] > $fixture['result']['goalsAwayTeam'] && $bet['home_goals'] > $bet['away_goals'])
            || ($fixture['result']['goalsHomeTeam'] < $fixture['result']['goalsAwayTeam'] && $bet['home_goals'] < $bet['away_goals'])) {
            return 1;
        }

        return 0;
    }

    public static function isFinalMatchday($betAndFixture)
    {
        try {
            return (int)$betAndFixture['matchday'] === (int)env('FINALE_MATCHDAY');
        } catch (Exception $e) {
            return false;
        }
    }

    public static function extractFinalMatch($fixtures)
    {
        $finaleMatchday = env('FINALE_MATCHDAY');

        foreach ($fixtures as $key => $fixture) {
            if ((int)$fixture['matchday'] === (int)$finaleMatchday) {
                return $fixture;
            }
        }

        return null;
    }

    public static function determineFixtureWinner($fixture)
    {
        $goalsHomeTeam = (int)$fixture['result']['goalsHomeTeam'];
        $goalsAwayTeam = (int)$fixture['result']['goalsAwayTeam'];

        if ($goalsHomeTeam === $goalsAwayTeam) {
            return null;
        }

        if ($goalsHomeTeam > $goalsAwayTeam) {
            return self::HOME_TEAM_KEY;
        } else {
            return self::AWAY_TEAM_KEY;
        }
    }

    public static function extractFixtureId($fixture)
    {
        return self::extractIdAtLastIndex($fixture, 'self');
    }

    /**
     * @param $fixture
     * @param $teamKey {@link Fixture::HOME_TEAM_KEY} or {@link Fixture::AWAY_TEAM_KEY}
     * @return mixed
     */
    public static function extractTeamId($fixture, $teamKey)
    {
        return self::extractIdAtLastIndex($fixture, $teamKey);
    }

    /**
     * @param $fixture
     * @return mixed
     */
    public static function extractIdAtLastIndex($fixture, $key)
    {
        $splitSelfLink = explode('/', $fixture['_links'][$key]['href']);
        return $splitSelfLink[count($splitSelfLink) - 1];
    }

    public static function isOver($fixture)
    {
        return $fixture['status'] === 'CANCELED' || $fixture['status'] === 'FINISHED';
    }

    public static function isFuture($fixture)
    {
        return $fixture['status'] === 'SCHEDULED' || $fixture['status'] === 'TIMED' || $fixture['status'] === 'POSTPONED';
    }
}
