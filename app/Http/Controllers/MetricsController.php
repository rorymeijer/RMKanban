<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Prometheus-metrics. Afschermbaar via een token (METRICS_TOKEN).
 */
class MetricsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = (string) config('board.metrics_token');
        if ($token !== '') {
            $provided = $request->bearerToken() ?? (string) $request->query('token');
            abort_unless(hash_equals($token, $provided), 403);
        }

        $metrics = [
            $this->gauge('board_users_total', 'Aantal gebruikers', User::count()),
            $this->gauge('board_workspaces_total', 'Aantal workspaces', Workspace::count()),
            $this->gauge('board_boards_total', 'Aantal boards', Board::count()),
            $this->gauge('board_cards_total', 'Aantal actieve kaarten', Card::whereNull('archived_at')->count()),
        ];

        return response(implode("\n", $metrics)."\n", 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    private function gauge(string $name, string $help, int $value): string
    {
        return "# HELP {$name} {$help}\n# TYPE {$name} gauge\n{$name} {$value}";
    }
}
