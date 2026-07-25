<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\JsonResponse;

/**
 * Statuscontrole voor Board's online refresh (upgrades/intrekking).
 */
class LicenseStatusController extends Controller
{
    public function show(string $uuid): JsonResponse
    {
        $license = License::query()->where('uuid', $uuid)->first();

        if ($license === null) {
            return response()->json(['status' => 'unknown'], 404);
        }

        if ($license->status === 'revoked') {
            return response()->json(['status' => 'revoked']);
        }

        // Actief: geef de huidige (mogelijk geüpgradede) sleutel terug.
        return response()->json([
            'status' => 'active',
            'key' => $license->key,
        ]);
    }
}
