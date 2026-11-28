@extends('layouts.app')

@section('content')
<main style="max-width: 420px; margin: 60px auto 40px; padding: 0 16px;">
  <div class="card-panel" style="padding: 28px 24px;">
    <div style="text-align:center; margin-bottom: 22px;">
      <div style="font-size: 38px; margin-bottom: 8px;">🔐</div>
      <h2 style="font-size: 1.45rem;">Admin Sign In</h2>
      <p style="font-size: 0.82rem; color: var(--muted); margin-top: 4px;">Sign in to manage Staff Hub configuration.</p>
    </div>

    @if($errors->any())
      <div style="background:var(--danger-soft);border:1px solid var(--danger);color:var(--danger);padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:0.82rem;">
        <ul style="margin:0;padding-left:16px">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}" style="display:flex; flex-direction:column; gap:14px;">
      @csrf

      <div class="field">
        <label for="email">Username or Email</label>
        <input id="email" name="email" type="text" value="{{ old('email', 'admin') }}" required autofocus autocomplete="username" placeholder="admin or admin@nordvik.local">
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••">
      </div>

      <div class="check-row" style="margin-top:2px;">
        <input type="checkbox" name="remember" id="remember" checked>
        <label for="remember" style="font-size:0.82rem; font-weight:500; cursor:pointer;">Remember me on this device</label>
      </div>

      <button type="submit" class="btn" style="width:100%; margin-top:8px;">Sign In</button>

      <div style="text-align:center; margin-top:10px;">
        <a href="{{ route('portal') }}" class="btn-quiet" style="font-size:0.78rem; text-decoration:none;">← Return to Staff Portal</a>
      </div>
    </form>
  </div>
</main>
@endsection
