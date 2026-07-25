<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Board — Licentiebeheer</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { font: 14px/1.6 system-ui, sans-serif; margin: 0; background: #0f1424; color: #e2e8f0; }
        .wrap { max-width: 1000px; margin: 0 auto; padding: 2rem 1rem; }
        h1 { font-size: 1.4rem; }
        h2 { font-size: 1.05rem; margin-top: 2rem; }
        .card { background: #171e33; border: 1px solid #263049; border-radius: 12px; padding: 1rem 1.25rem; margin: 1rem 0; }
        label { display: block; font-size: .8rem; color: #94a3b8; margin-top: .5rem; }
        input, select, textarea { width: 100%; padding: .5rem; border-radius: 8px; border: 1px solid #263049; background: #0f1424; color: #e2e8f0; }
        button { background: #6366f1; color: #fff; border: 0; border-radius: 8px; padding: .5rem .9rem; cursor: pointer; font-weight: 600; }
        button.ghost { background: transparent; border: 1px solid #263049; color: #cbd5e1; }
        button.danger { background: #ef4444; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        th, td { text-align: left; padding: .5rem; border-top: 1px solid #263049; vertical-align: top; }
        th { color: #94a3b8; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; }
        .badge { display: inline-block; padding: .1rem .5rem; border-radius: 999px; font-size: .7rem; background: #263049; }
        .badge.active { background: #14532d; color: #86efac; }
        .badge.revoked { background: #7f1d1d; color: #fca5a5; }
        .status { background: #14532d; color: #86efac; padding: .5rem 1rem; border-radius: 8px; margin: 1rem 0; }
        .flex { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
        code, textarea.key { font-family: ui-monospace, monospace; font-size: .72rem; word-break: break-all; }
        .muted { color: #94a3b8; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Board — Licentiebeheer</h1>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <div class="card">
        <strong>Publieke sleutel</strong> — zet deze in Board (<code>LICENSE_PUBLIC_KEY</code>):
        <textarea class="key" rows="2" readonly onclick="this.select()">{{ $publicKey ?: 'Nog geen sleutel — draai: php artisan license:keygen' }}</textarea>
    </div>

    <h2>Pakket aanmaken</h2>
    <div class="card">
        <form method="post" action="{{ route('packages.store') }}">
            @csrf
            <label>Naam</label>
            <input name="name" required placeholder="bijv. Pro, Team, Enterprise">
            <div class="grid">
                <div><label>Max. gebruikers</label><input name="users" type="number" min="1" placeholder="∞"></div>
                <div><label>Max. workspaces</label><input name="workspaces" type="number" min="1" placeholder="∞"></div>
                <div><label>Max. boards</label><input name="boards" type="number" min="1" placeholder="∞"></div>
                <div><label>Opslag (GB)</label><input name="storage_gb" type="number" min="1" placeholder="∞"></div>
            </div>
            <label>Features</label>
            <div class="flex">
                @foreach ($features as $feature)
                    <label class="flex" style="margin:0"><input type="checkbox" name="features[]" value="{{ $feature }}" style="width:auto"> {{ $feature }}</label>
                @endforeach
            </div>
            <label>Trial-dagen (0 = geen)</label>
            <input name="trial_days" type="number" min="0" value="0">
            <p><button type="submit">Pakket opslaan</button></p>
        </form>
    </div>

    <h2>Pakketten</h2>
    <div class="card">
        <table>
            <thead><tr><th>Naam</th><th>Limieten</th><th>Features</th><th>Licenties</th><th></th></tr></thead>
            <tbody>
            @forelse ($packages as $package)
                <tr>
                    <td><strong>{{ $package->name }}</strong></td>
                    <td class="muted">
                        @foreach (($package->limits ?? []) as $k => $v)
                            {{ $k }}: {{ $v ?? '∞' }}<br>
                        @endforeach
                    </td>
                    <td class="muted">{{ implode(', ', $package->features ?? []) ?: '—' }}</td>
                    <td>{{ $package->licenses_count }}</td>
                    <td>
                        <form method="post" action="{{ route('packages.destroy', $package) }}" onsubmit="return confirm('Pakket verwijderen?')">
                            @csrf @method('DELETE')
                            <button class="ghost" type="submit">Verwijder</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Nog geen pakketten.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <h2>Licentie uitgeven</h2>
    <div class="card">
        <form method="post" action="{{ route('licenses.store') }}">
            @csrf
            <label>Pakket</label>
            <select name="package_id" required>
                @foreach ($packages as $package)
                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                @endforeach
            </select>
            <div class="grid">
                <div><label>Naam klant</label><input name="holder_name" required></div>
                <div><label>E-mail klant</label><input name="holder_email" type="email" required></div>
                <div><label>Verloopt op (leeg = nooit)</label><input name="expires_at" type="date"></div>
                <div><label>Respijt (dagen)</label><input name="grace_days" type="number" min="0" value="14"></div>
            </div>
            <p><button type="submit">Licentie uitgeven</button></p>
        </form>
    </div>

    <h2>Licenties</h2>
    <div class="card">
        <table>
            <thead><tr><th>Klant</th><th>Pakket</th><th>Status</th><th>Verloopt</th><th>Acties</th></tr></thead>
            <tbody>
            @forelse ($licenses as $license)
                <tr>
                    <td>{{ $license->holder_name }}<br><span class="muted">{{ $license->holder_email }}</span></td>
                    <td>{{ $license->package?->name }}</td>
                    <td><span class="badge {{ $license->status }}">{{ $license->status }}</span></td>
                    <td class="muted">{{ $license->expires_at?->toDateString() ?? '∞' }}</td>
                    <td>
                        <div class="flex">
                            <form method="post" action="{{ route('licenses.reveal', $license) }}">@csrf<button class="ghost" type="submit">Sleutel</button></form>
                            @if ($license->status === 'active')
                                <form method="post" action="{{ route('licenses.revoke', $license) }}">@csrf<button class="danger" type="submit">Intrekken</button></form>
                            @else
                                <form method="post" action="{{ route('licenses.reactivate', $license) }}">@csrf<button type="submit">Heractiveren</button></form>
                            @endif
                        </div>
                        <form method="post" action="{{ route('licenses.upgrade', $license) }}" class="flex" style="margin-top:.5rem">
                            @csrf
                            <select name="package_id">
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}" @selected($package->id === $license->package_id)>{{ $package->name }}</option>
                                @endforeach
                            </select>
                            <button class="ghost" type="submit">Upgrade</button>
                        </form>
                        @if (session('reveal_key') === $license->uuid)
                            <textarea class="key" rows="3" readonly onclick="this.select()">{{ $license->key }}</textarea>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Nog geen licenties.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
