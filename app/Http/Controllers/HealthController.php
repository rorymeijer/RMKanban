<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * /api/health — rapporteert of de app geïnstalleerd is en de status van de
 * afhankelijke services, zodat proxy/monitoring weet wanneer de app klaar is.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $services = [
            'database' => $this->check(function (): bool {
                DB::connection()->getPdo();

                return true;
            }),
            'redis' => $this->check(function (): bool {
                Redis::connection()->ping();

                return true;
            }),
            'meilisearch' => $this->checkMeilisearch(),
            'reverb' => $this->checkReverb(),
        ];

        $installed = $this->safe(fn (): bool => Setting::isInstalled()) ?? false;

        // De database bepaalt of de app kan serveren (readiness). Overige services
        // die down zijn maken de status "degraded" maar houden de app bereikbaar.
        $ready = $services['database'] === 'up';
        $allUp = ! in_array('down', array_values($services), true);

        return response()->json([
            'status' => $allUp ? 'ok' : 'degraded',
            'installed' => $installed,
            'version' => config('board.version'),
            'services' => $services,
        ], $ready ? 200 : 503);
    }

    private function check(callable $probe): string
    {
        return $this->safe($probe) === true ? 'up' : 'down';
    }

    private function checkMeilisearch(): string
    {
        return $this->check(function (): bool {
            $host = (string) config('scout.meilisearch.host');
            if ($host === '') {
                return false;
            }
            $response = Http::timeout(2)->get(rtrim($host, '/').'/health');

            return $response->successful();
        });
    }

    private function checkReverb(): string
    {
        return $this->check(function (): bool {
            $host = (string) config('board.reverb.host');
            $port = (int) config('board.reverb.port');
            $connection = @fsockopen($host, $port, $errno, $errstr, 2);
            if ($connection === false) {
                return false;
            }
            fclose($connection);

            return true;
        });
    }

    /**
     * @template T
     *
     * @param  callable():T  $probe
     * @return T|null
     */
    private function safe(callable $probe): mixed
    {
        try {
            return $probe();
        } catch (Throwable) {
            return null;
        }
    }
}
