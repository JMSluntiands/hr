@extends('layouts.guest')

@section('title', 'Privacy Policy - Luntian HR')

@push('head')
<style>
    .privacy-scroll::-webkit-scrollbar { width: 8px; }
    .privacy-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
</style>
@endpush

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 md:p-8 bg-gradient-to-br from-slate-100 via-slate-50 to-orange-50/40">
    <div class="w-full max-w-3xl">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50 overflow-hidden">
            <div class="bg-gradient-to-r from-[#FA9800] to-orange-600 px-6 py-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-widest text-white/80">Required after login</p>
                <h1 class="mt-1 text-2xl font-bold">Privacy Policy</h1>
                <p class="mt-2 text-sm text-white/90">
                    Hello, <strong>{{ $userName }}</strong>. Please read and accept before continuing to Luntian HR.
                </p>
            </div>

            @if(session('error'))
            <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
            @endif

            <div class="px-6 py-5">
                <p class="text-xs text-slate-500 mb-3">Last updated: {{ $lastUpdated }} · Version {{ $policyVersion }}</p>
                <div class="privacy-scroll max-h-[min(50vh,420px)] overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/80 p-5 text-sm leading-relaxed text-slate-700 space-y-4">
                    @include('auth.partials.privacy-policy-text')
                </div>

                <div class="mt-6 space-y-4">
                    <form method="post" action="{{ route('privacy-policy.accept') }}" id="privacyAcceptForm">
                        @csrf
                        <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-3 hover:border-amber-300 transition-colors">
                            <input type="checkbox" name="agree" value="1" id="privacyAgree" class="mt-1 rounded border-slate-300 text-[#FA9800] focus:ring-amber-500" required>
                            <span class="text-sm text-slate-700">
                                I have read, understood, and agree to the <strong>Privacy Policy</strong> and the processing of my personal information as described above.
                            </span>
                        </label>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between mt-4">
                            <button type="submit" id="privacyAcceptBtn" disabled class="w-full sm:w-auto sm:order-2 px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-[#FA9800] shadow-md shadow-amber-600/20 hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-[#FA9800]">
                                I Agree — Continue
                            </button>
                        </div>
                    </form>

                    <form method="post" action="{{ route('privacy-policy.decline') }}">
                        @csrf
                        <button type="submit" class="w-full px-5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                            I Do Not Agree (Logout)
                        </button>
                    </form>
                </div>

                <p class="mt-4 text-xs text-slate-500 text-center">
                    If you do not agree, you will be logged out automatically and cannot access the system.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var agree = document.getElementById('privacyAgree');
    var btn = document.getElementById('privacyAcceptBtn');
    if (!agree || !btn) return;
    function sync() { btn.disabled = !agree.checked; }
    agree.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
