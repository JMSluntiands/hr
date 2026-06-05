<form class="js-login-form" novalidate>
    <input type="hidden" name="_token" value="{{ csrf_token() }}" class="js-login-csrf">
    <h1 class="text-2xl font-bold text-slate-800 mb-6 {{ $idPrefix ? 'text-center' : '' }}">User Login</h1>

    <div class="mb-4">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <input name="email" id="email{{ $idPrefix }}" type="email" placeholder="Email Id"
                class="w-full pl-10 pr-4 py-3 bg-slate-100 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E9A319]/50 focus:bg-white text-slate-900" autocomplete="off">
        </div>
    </div>

    <div class="mb-5 relative">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <input name="password" id="password{{ $idPrefix }}" type="password" placeholder="Password"
                class="w-full pl-10 pr-12 py-3 bg-slate-100 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E9A319]/50 focus:bg-white text-slate-900">
            <button type="button" id="togglePassword{{ $idPrefix }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                <svg id="eyeOpen{{ $idPrefix }}" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg id="eyeClosed{{ $idPrefix }}" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18"/></svg>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <button type="button" class="js-auth-btn w-full bg-[#E9A319] hover:bg-[#d18a15] text-white font-semibold py-3 rounded-xl transition-colors" data-action="login">Login</button>
        <button type="button" class="js-auth-btn w-full bg-slate-700 hover:bg-slate-800 text-white font-semibold py-3 rounded-xl transition-colors" data-action="timein">Time In</button>
    </div>
</form>
