# Fantasy Hockey Pickup Watch

Standalone fantasy-hockey pickup assistant for public Fantrax leagues.

## How it works

1. Enter a Fantrax League ID.
2. Click **Refresh Fantrax Players**.
3. The app stores that league's available players for today and tomorrow.
4. Switch between leagues with the `league` query parameter. Data from other leagues is never deleted when a different league is refreshed.
5. Shared Daily Faceoff data supplies starting-goalie status, G1/G2 depth, even-strength lines, and PP1/PP2 assignments.

## League isolation

Fantrax rows are keyed by `league_id`. A refresh only replaces rows for the selected league and game date. Other league IDs remain untouched.

## Railway

The app is Docker-ready. Attach a MySQL service and provide the standard Laravel/MySQL variables from `.env.example`. Migrations run automatically at startup. Daily Faceoff goalie and line data are refreshed on startup and then by the Laravel scheduler.

## Commands

```sh
php artisan pickup:refresh-fantrax <leagueId>
php artisan pickup:refresh-goalies
php artisan pickup:refresh-lines
```
