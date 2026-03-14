@extends('layouts.app')

@section('title', 'Contacto')

@section('content')
<div class="max-w-4xl mx-auto p-8">
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h2 class="text-2xl font-black mb-4">Contacto</h2>
        <p class="text-sm text-slate-300">Escribenos para soporte, colaboraciones o dudas sobre la plataforma.</p>

        <form id="contact-form" class="mt-6 grid gap-4">
            <div>
                <label class="text-xs uppercase tracking-widest text-blue-300">Nombre</label>
                <input id="contact-name" type="text" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100" placeholder="Tu nombre">
            </div>
            <div>
                <label class="text-xs uppercase tracking-widest text-blue-300">Email</label>
                <input id="contact-email" type="email" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100" placeholder="tu@email.com">
            </div>
            <div>
                <label class="text-xs uppercase tracking-widest text-blue-300">Asunto / Incidencia</label>
                <input id="contact-subject" type="text" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100" placeholder="Resumen rapido del problema">
            </div>
            <div>
                <label class="text-xs uppercase tracking-widest text-blue-300">Mensaje</label>
                <textarea id="contact-message" rows="4" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100" placeholder="Escribe tu mensaje"></textarea>
            </div>
            <button id="contact-submit" type="button" class="rounded-md bg-blue-600 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-blue-500">
                Enviar
            </button>
        </form>
    </div>
</div>

<div id="contact-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 px-6">
    <div class="w-full max-w-xl rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-black">Mensaje listo para copiar</h3>
            <button id="contact-close" class="text-slate-400 hover:text-slate-200">Cerrar</button>
        </div>
        <p class="mt-3 text-sm text-slate-300">
            El sistema de contacto aun esta en desarrollo. Si es urgente, escribeme en Discord: <span class="font-semibold text-blue-300">betans</span>.
        </p>
        <div class="mt-4 rounded-lg border border-slate-800 bg-slate-950/70 p-4 text-sm text-slate-200">
            <pre id="contact-preview" class="whitespace-pre-wrap font-sans"></pre>
        </div>
        <div class="mt-5 flex flex-wrap gap-3">
            <button id="contact-copy" type="button" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-blue-500">
                Copiar mensaje
            </button>
            <button id="contact-close-alt" type="button" class="rounded-md border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:border-slate-500">
                Cerrar
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

    const buildMessage = () => {
        const name = document.getElementById('contact-name').value.trim();
        const email = document.getElementById('contact-email').value.trim();
        const subject = document.getElementById('contact-subject').value.trim();
        const message = document.getElementById('contact-message').value.trim();

        return `Nombre: ${name || 'N/A'}\nEmail: ${email || 'N/A'}\nAsunto: ${subject || 'N/A'}\nMensaje: ${message || 'N/A'}`;
    };

    const openModal = () => {
        preview.textContent = buildMessage();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        copyButton.textContent = 'Copiar mensaje';
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

        copyButton.textContent = copied ? 'Copiado' : 'No se pudo copiar';
        setTimeout(() => {
            copyButton.textContent = 'Copiar mensaje';
        }, 2000);
    });
})();
</script>
@endsection
