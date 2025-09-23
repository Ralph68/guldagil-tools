<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion | Guldagil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        html,body{height:100%;margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;background:#0f172a;color:#e2e8f0}
        .wrap{min-height:100%;display:grid;place-items:center;padding:24px}
        .card{width:100%;max-width:420px;background:#111827;border:1px solid #374151;border-radius:16px;padding:24px;box-shadow:0 6px 20px rgba(0,0,0,.45)}
        h1{margin:0 0 16px;font-size:22px}
        label{display:block;font-size:14px;margin:12px 0 6px}
        input{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #374151;background:#0b1220;color:#e5e7eb}
        .row{display:flex;align-items:center;justify-content:space-between;margin-top:12px}
        button{background:#22c55e;border:none;color:#052e1a;padding:10px 14px;border-radius:10px;font-weight:700;cursor:pointer}
        .error{margin-top:10px;color:#f87171;font-size:14px}
        a{color:#93c5fd;text-decoration:none}
    </style>
</head>
<body>
<div class="wrap">
    <form class="card" method="post" action="{{ route('login.post') }}">
        @csrf
        <h1>Connexion</h1>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" required>

        <div class="row">
            <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                <input type="checkbox" name="remember" value="1"> Se souvenir de moi
            </label>
            <button type="submit">Se connecter</button>
        </div>

        @error('email')
        <div class="error">{{ $message }}</div>
        @enderror
    </form>
</div>
</body>
</html>
