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
    </div>
</div>

<div id="contact-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#121212]/95 backdrop-blur-md px-6">
    <div class="w-full max-w-xl rounded-sm border border-[#222222] bg-[#1b1b1b] p-8 shadow-2xl">
        <div class="flex items-center justify-between border-b border-[#222222] pb-4 mb-6">
            <h3 class="text-xl font-black uppercase italic tracking-tight text-white">{{ __('Soporte Manual') }}</h3>
            <button id="contact-close" class="text-slate-500 hover:text-white transition">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <p class="text-sm text-slate-400 font-medium leading-relaxed">
            {{ __('El sistema de contacto automatizado está en mantenimiento. Por favor, copia este reporte y envíalo vía Discord a') }} <span class="font-bold text-[#5b7cff]">betans</span>.
        </p>
        <div class="mt-6 rounded-sm border border-[#222222] bg-[#121212] p-5">
            <pre id="contact-preview" class="whitespace-pre-wrap font-mono text-[11px] text-slate-300"></pre>
        </div>
        <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <button id="contact-copy" type="button" class="flex-1 rounded-sm bg-[#5b7cff] px-6 py-3 text-[11px] font-black uppercase tracking-widest text-white hover:bg-[#7c5cff] transition">
                {{ __('Copiar Reporte') }}
            </button>
            <button id="contact-close-alt" type="button" class="flex-1 rounded-sm border border-[#222222] bg-[#1b1b1b] px-6 py-3 text-[11px] font-black uppercase tracking-widest text-white hover:bg-[#222222] transition">
                {{ __('Volver') }}
            </button>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('contact-modal');
    const preview = document.getElementById('contact-preview');
    const closeButtons = [document.getElementById('contact-close'), document.getElementById('contact-close-alt')];
    const copyButton = document.getElementById('contact-copy');

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

    // Limpiar error en tiempo real al escribir
    fields.forEach(({ id, errorId }) => {
        const input = document.getElementById(id);
        const errorEl = document.getElementById(errorId);
        input.addEventListener('input', () => {
            const val = input.value.trim();
            const invalid = id === 'contact-email' ? !isValidEmail(val) : val === '';
            if (!invalid) clearError(errorEl, input);
        });
    });

    const buildMessage = () => {
        const name = document.getElementById('contact-name').value.trim();
        const email = document.getElementById('contact-email').value.trim();
        const subject = document.getElementById('contact-subject').value.trim();
        const message = document.getElementById('contact-message').value.trim();

        return `--- REPORT AFTERRELOAD ---\n{{ __('Nombre') }}: ${name || 'N/A'}\nEmail: ${email || 'N/A'}\n{{ __('Asunto') }}: ${subject || 'N/A'}\n\n{{ __('Mensaje') }}:\n${message || 'N/A'}\n-------------------------`;
    };

    const openModal = () => {
        if (!validate()) return;
        preview.textContent = buildMessage();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        copyButton.textContent = '{{ __('Copiar Reporte') }}';
    };

    const fallbackCopy = (text) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
            return true;
        } catch (error) {
            return false;
        } finally {
            document.body.removeChild(textarea);
        }
    };

    document.getElementById('contact-submit').addEventListener('click', openModal);

    closeButtons.forEach((btn) => {
        btn.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    copyButton.addEventListener('click', async () => {
        const text = preview.textContent;
        let copied = false;

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                copied = true;
            } else {
                copied = fallbackCopy(text);
            }
        } catch (error) {
            copied = fallbackCopy(text);
        }

        copyButton.textContent = copied ? '{{ __('Copiado con éxito') }}' : '{{ __('Error al copiar') }}';
        setTimeout(() => {
            copyButton.textContent = '{{ __('Copiar Reporte') }}';
        }, 2000);
    });
})();
</script>
@endsection
