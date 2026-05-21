@extends('layouts.app')

@section('title', __('Contacto'))

@section('content')
<div class="max-w-4xl mx-auto p-4 md:p-8">
    <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-8 shadow-2xl relative overflow-hidden">
        <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white mb-8 border-b border-[#222222] pb-6">{{ __('Contacto') }}</h2>
        
        <p class="text-base text-slate-400 font-medium leading-relaxed mb-10">
            {{ __('Utiliza este formulario para enviarnos cualquier consulta, reporte de bug o solicitud de soporte.') }}
        </p>

        <form id="contact-form" class="grid gap-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="text-[10px] uppercase font-black tracking-[0.2em] text-[#5b7cff] mb-2 block">{{ __('Nombre') }}</label>
                    <input id="contact-name" type="text" class="w-full rounded-sm border border-[#222222] bg-[#121212] p-4 text-sm text-white placeholder-slate-700 focus:border-[#5b7cff] outline-none transition" placeholder="{{ __('Introduce tu nombre') }}">
                    <span id="error-name" class="hidden mt-1 block text-[11px] font-bold text-red-500"></span>
                </div>
                <div>
                    <label class="text-[10px] uppercase font-black tracking-[0.2em] text-[#5b7cff] mb-2 block">{{ __('Email') }}</label>
                    <input id="contact-email" type="email" class="w-full rounded-sm border border-[#222222] bg-[#121212] p-4 text-sm text-white placeholder-slate-700 focus:border-[#5b7cff] outline-none transition" placeholder="tu@email.com">
                    <span id="error-email" class="hidden mt-1 block text-[11px] font-bold text-red-500"></span>
                </div>
            </div>
            <div>
                <label class="text-[10px] uppercase font-black tracking-[0.2em] text-[#5b7cff] mb-2 block">{{ __('Asunto / Incidencia') }}</label>
                <input id="contact-subject" type="text" class="w-full rounded-sm border border-[#222222] bg-[#121212] p-4 text-sm text-white placeholder-slate-700 focus:border-[#5b7cff] outline-none transition" placeholder="{{ __('Resumen de la incidencia') }}">
                <span id="error-subject" class="hidden mt-1 block text-[11px] font-bold text-red-500"></span>
            </div>
            <div>
                <label class="text-[10px] uppercase font-black tracking-[0.2em] text-[#5b7cff] mb-2 block">{{ __('Mensaje') }}</label>
                <textarea id="contact-message" rows="5" class="w-full rounded-sm border border-[#222222] bg-[#121212] p-4 text-sm text-white placeholder-slate-700 focus:border-[#5b7cff] outline-none transition" placeholder="{{ __('Escribe tu mensaje aquí...') }}"></textarea>
                <span id="error-message" class="hidden mt-1 block text-[11px] font-bold text-red-500"></span>
            </div>
            <button id="contact-submit" type="button" class="w-full rounded-sm bg-[#5b7cff] py-4 text-sm font-black uppercase tracking-widest text-white hover:bg-[#7c5cff] shadow-lg shadow-black/40 transition">
                {{ __('Enviar Mensaje') }}
            </button>
        </form>

        <div id="contact-success" class="hidden mt-6 rounded-sm border border-green-800 bg-green-900/20 p-5 text-center">
            <svg class="mx-auto mb-3 h-10 w-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <p class="text-sm font-bold text-green-400">{{ __('Mensaje enviado correctamente.') }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ __('Te responderemos lo antes posible.') }}</p>
        </div>
    </div>
</div>

<script>
(() => {
    const fields = [
        { id: 'contact-name',    errorId: 'error-name',    msg: '{{ __('El nombre es obligatorio') }}' },
        { id: 'contact-email',   errorId: 'error-email',   msg: '{{ __('Introduce un email válido') }}' },
        { id: 'contact-subject', errorId: 'error-subject', msg: '{{ __('El asunto es obligatorio') }}' },
        { id: 'contact-message', errorId: 'error-message', msg: '{{ __('El mensaje es obligatorio') }}' },
    ];

    const setError = (errorEl, input, msg) => {
        errorEl.textContent = msg;
        errorEl.classList.remove('hidden');
        input.classList.add('border-red-500');
        input.classList.remove('border-[#222222]');
    };

    const clearError = (errorEl, input) => {
        errorEl.classList.add('hidden');
        input.classList.remove('border-red-500');
        input.classList.add('border-[#222222]');
    };

    const isValidEmail = (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);

    const validate = () => {
        let ok = true;
        fields.forEach(({ id, errorId, msg }) => {
            const input = document.getElementById(id);
            const errorEl = document.getElementById(errorId);
            const val = input.value.trim();
            const invalid = id === 'contact-email' ? !isValidEmail(val) : val === '';
            if (invalid) { setError(errorEl, input, msg); ok = false; }
            else { clearError(errorEl, input); }
        });
        return ok;
    };

    fields.forEach(({ id, errorId }) => {
        const input = document.getElementById(id);
        const errorEl = document.getElementById(errorId);
        input.addEventListener('input', () => {
            const val = input.value.trim();
            const invalid = id === 'contact-email' ? !isValidEmail(val) : val === '';
            if (!invalid) clearError(errorEl, input);
        });
    });

    const submitBtn = document.getElementById('contact-submit');

    submitBtn.addEventListener('click', async () => {
        if (!validate()) return;

        submitBtn.disabled = true;
        submitBtn.textContent = '{{ __('Enviando...') }}';

        try {
            await fetch('{{ route('contact.send') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({
                    name:    document.getElementById('contact-name').value.trim(),
                    email:   document.getElementById('contact-email').value.trim(),
                    subject: document.getElementById('contact-subject').value.trim(),
                    message: document.getElementById('contact-message').value.trim(),
                }),
            });
        } catch (_) {}

        document.getElementById('contact-form').classList.add('hidden');
        document.getElementById('contact-success').classList.remove('hidden');
    });
})();
</script>
@endsection
