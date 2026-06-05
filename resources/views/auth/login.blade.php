@extends('layouts.guest')

@section('title', 'Login - Luntian')

@push('head')
<style>
    .login-spinner {
        display: inline-block;
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: login-spin 0.7s linear infinite;
        vertical-align: middle;
        margin-right: 0.5rem;
    }
    @keyframes login-spin { to { transform: rotate(360deg); } }
</style>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { inter: ['Inter', 'sans-serif'] },
                colors: { luntianOrange: '#fa9b05', luntianDark: '#1e1e2d', tealBlue: '#FA9800' }
            }
        }
    };
</script>
@endpush

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 md:p-6 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-[#1e1e2d] via-[#1e1e2d] to-[#FA9800] -z-10"></div>

    <div class="hidden md:flex bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden flex-row">
        <div class="hidden md:flex md:w-2/5 bg-white items-center justify-center p-8 md:p-12">
            <div class="w-48 h-48 md:w-56 md:h-56 rounded-full border-2 border-slate-200 bg-white flex items-center justify-center shadow-inner">
                <i class="fa fa-users text-7xl md:text-8xl text-[#E9A319]" aria-hidden="true"></i>
            </div>
        </div>
        <div class="flex-1 p-6 md:p-10 flex flex-col justify-center">
            @include('auth.partials.login-form', ['idPrefix' => ''])
        </div>
    </div>

    <div class="md:hidden absolute inset-0 flex flex-col items-center justify-center p-4 bg-gradient-to-br from-[#1e1e2d] via-[#1e1e2d] to-[#FA9800]">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="flex justify-center pt-8 pb-4">
                <div class="w-32 h-32 rounded-full border-2 border-slate-200 bg-white flex items-center justify-center shadow-inner">
                    <i class="fa fa-users text-6xl text-[#E9A319]" aria-hidden="true"></i>
                </div>
            </div>
            <div class="px-6 pb-8">
                @include('auth.partials.login-form', ['idPrefix' => 'Mobile'])
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/manila-time.js') }}"></script>
<script src="{{ asset('function/login/toggle-password.js') }}"></script>
<script src="{{ asset('function/login/login-process.js') }}"></script>
<script>
(function () {
    @if($timeout)
    Toastify({ text: 'You have been logged out due to inactivity.', duration: 5000, gravity: 'top', position: 'right', backgroundColor: '#f59e0b' }).showToast();
    @endif
    @foreach($errors as $message)
    Toastify({ text: @json($message), duration: 5000, gravity: 'top', position: 'right', backgroundColor: '#e3342f' }).showToast();
    @endforeach
    @if(!empty($privacyDeclined))
    Toastify({ text: @json($privacyDeclined), duration: 6000, gravity: 'top', position: 'right', backgroundColor: '#f59e0b' }).showToast();
    @endif
})();
</script>
@endpush
