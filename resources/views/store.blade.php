@extends('layouts.app')

@section('title', __('TIENDA'))

@section('content')
<div class="max-w-6xl mx-auto p-8">
    <div class="rounded-sm border border-[#222222] bg-[#1b1b1b] p-8 shadow-2xl">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-[#222222] pb-6 mb-8">
            <div>
                <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white">{{ __('TIENDA') }}</h2>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mt-2">{{ __('Usa los filtros para explorar categorias, o busca cualquier skin con el buscador.') }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-3">
            <div>
                <label class="text-[10px] uppercase font-black tracking-[0.2em] text-[#5b7cff] mb-3 block">{{ __('Tipo de arma') }}</label>
                <select id="type-select" class="w-full rounded-sm border border-[#222222] bg-[#121212] p-4 text-sm font-bold text-white focus:border-[#5b7cff] transition">
                    @foreach($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] uppercase font-black tracking-[0.2em] text-[#5b7cff] mb-3 block">{{ __('Arma') }}</label>
                <select id="weapon-select" class="w-full rounded-sm border border-[#222222] bg-[#121212] p-4 text-sm font-bold text-white focus:border-[#5b7cff] transition"></select>
            </div>
            <div class="flex items-end">
                <button id="load-skins" class="w-full rounded-sm bg-[#5b7cff] py-4 text-xs font-black uppercase tracking-widest text-white hover:bg-[#7c5cff] shadow-lg shadow-black/20 transition">
                    {{ __('Ver skins') }}
                </button>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-end gap-6 border-t border-[#222222] pt-8">
            <div class="flex-1 min-w-[240px]">
                <label class="text-[10px] uppercase font-black tracking-[0.2em] text-slate-500 mb-3 block">{{ __('Buscar skin') }}</label>
                <input id="search-input" type="text" placeholder="{{ __('Ej: Dragon Lore') }}" class="w-full rounded-sm border border-[#222222] bg-[#121212] p-4 text-sm font-bold text-white focus:border-[#5b7cff] transition">
            </div>
            <button id="search-skins" class="rounded-sm border border-[#5b7cff] px-10 py-4 text-xs font-black uppercase tracking-widest text-white hover:bg-[#5b7cff] transition">
                {{ __('Buscar') }}
            </button>
            <button id="clear-search" class="rounded-sm border border-[#222222] px-10 py-4 text-xs font-black uppercase tracking-widest text-slate-400 hover:border-white hover:text-white transition">
                {{ __('Limpiar') }}
            </button>
        </div>

        <div class="mt-8 flex items-center justify-between text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">
            <span id="status">{{ __('Selecciona filtros o busca una skin.') }}</span>
            <div class="flex items-center gap-4">
                <button id="prev-page" class="rounded-sm border border-[#222222] px-4 py-2 hover:border-white transition disabled:opacity-30 disabled:cursor-not-allowed" disabled>
                    {{ __('Anterior') }}
                </button>
                <span id="page-indicator">{{ __('Pagina') }} 1</span>
                <button id="next-page" class="rounded-sm border border-[#222222] px-4 py-2 hover:border-white transition disabled:opacity-30 disabled:cursor-not-allowed" disabled>
                    {{ __('Siguiente') }}
                </button>
            </div>
        </div>

        <div id="skins-grid" class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4"></div>
    </div>
</div>

<div id="purchase-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-md p-4">
    <div class="w-full max-w-md rounded-sm border border-[#222222] bg-[#1b1b1b] p-8 shadow-2xl">
        <h3 class="text-2xl font-black uppercase italic tracking-tighter text-white">{{ __('Confirmar compra') }}</h3>
        <p id="modal-skin-name" class="mt-4 text-sm font-bold text-[#5b7cff]"></p>
        <p class="mt-4 text-[10px] uppercase tracking-widest font-bold text-slate-500 leading-relaxed">{{ __('Esta es una plantilla, la compra real se conectara mas adelante.') }}</p>
        <div class="mt-8 flex gap-4">
            <button id="confirm-buy" class="flex-1 rounded-sm bg-[#5b7cff] py-4 text-xs font-black uppercase tracking-widest text-white hover:bg-[#7c5cff] shadow-lg shadow-black/20 transition">
                {{ __('Confirmar') }}
            </button>
            <button id="close-modal" class="flex-1 rounded-sm border border-[#222222] py-4 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-white transition">
                {{ __('Cancelar') }}
            </button>
        </div>
    </div>
</div>

<script>
(() => {
    const typeSelect = document.getElementById('type-select');
    const weaponSelect = document.getElementById('weapon-select');
    const searchInput = document.getElementById('search-input');
    const loadButton = document.getElementById('load-skins');
    const searchButton = document.getElementById('search-skins');
    const clearButton = document.getElementById('clear-search');
    const grid = document.getElementById('skins-grid');
    const status = document.getElementById('status');
    const prevPage = document.getElementById('prev-page');
    const nextPage = document.getElementById('next-page');
    const pageIndicator = document.getElementById('page-indicator');
    const modal = document.getElementById('purchase-modal');
    const modalName = document.getElementById('modal-skin-name');
    const closeModal = document.getElementById('close-modal');
    const confirmBuy = document.getElementById('confirm-buy');

    const weaponMap = @json($weapons);

    let currentPage = 1;
    let hasMore = false;

    const renderWeaponOptions = (type) => {
        const options = weaponMap[type] || ['All'];
        weaponSelect.innerHTML = options
            .map((weapon) => `<option value="${weapon}">${weapon}</option>`)
            .join('');
    };

    const renderCards = (items) => {
        if (!items.length) {
            grid.innerHTML = `<div class="col-span-full text-slate-500 font-bold uppercase tracking-widest text-xs py-20 text-center border border-dashed border-[#222222] rounded-sm">{{ __('No hay skins para esta seleccion.') }}</div>`;
            return;
        }

        grid.innerHTML = items
            .map((item) => {
                return `
                    <div class="rounded-sm border border-[#222222] bg-[#121212] p-5 group hover:border-[#5b7cff]/40 transition shadow-lg shadow-black/10">
                        <div class="aspect-square overflow-hidden rounded-sm border border-[#222222] bg-[#1b1b1b] relative">
                            <img src="${item.image}" alt="${item.name}" class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-[#1b1b1b] to-transparent opacity-40"></div>
                        </div>
                        <p class="mt-4 text-xs font-black text-white uppercase tracking-tighter italic">${item.name}</p>
                        <button data-skin="${item.name}" class="mt-4 w-full rounded-sm bg-[#5b7cff] py-3 text-[10px] font-black uppercase tracking-widest text-white hover:bg-[#7c5cff] transition shadow-lg shadow-black/20">
                            {{ __('Comprar') }}
                        </button>
                    </div>
                `;
            })
            .join('');
    };

    const updatePagination = () => {
        pageIndicator.textContent = `{{ __('Pagina') }} ${currentPage}`;
        prevPage.disabled = currentPage <= 1;
        nextPage.disabled = !hasMore;
    };

    const loadSkins = async () => {
        const type = typeSelect.value;
        const weapon = weaponSelect.value;
        const search = searchInput.value.trim();

        status.textContent = "{{ __('Cargando skins...') }}";
        grid.innerHTML = '';

        try {
            const response = await fetch(\`{{ route('store.skins') }}?type=\${encodeURIComponent(type)}&weapon=\${encodeURIComponent(weapon)}&search=\${encodeURIComponent(search)}&page=\${currentPage}&per_page=16\`);
            const data = await response.json();

            if (!data.success) {
                status.textContent = data.message || "{{ __('No se pudo cargar.') }}";
                return;
            }

            hasMore = data.meta.has_more;
            status.textContent = \`{{ __('Mostrando') }} \${data.data.length} {{ __('skins') }}\`;
            renderCards(data.data);
            updatePagination();
        } catch (error) {
            status.textContent = "{{ __('Error al conectar con el catalogo local.') }}";
        }
    };

    grid.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-skin]');
        if (!button) {
            return;
        }
        modalName.textContent = button.dataset.skin;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });

    const closeModalHandler = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    closeModal.addEventListener('click', closeModalHandler);
    confirmBuy.addEventListener('click', closeModalHandler);

    loadButton.addEventListener('click', () => {
        currentPage = 1;
        loadSkins();
    });

    searchButton.addEventListener('click', () => {
        currentPage = 1;
        loadSkins();
    });

    clearButton.addEventListener('click', () => {
        searchInput.value = '';
        currentPage = 1;
        loadSkins();
    });

    prevPage.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage -= 1;
            loadSkins();
        }
    });

    nextPage.addEventListener('click', () => {
        if (hasMore) {
            currentPage += 1;
            loadSkins();
        }
    });

    typeSelect.addEventListener('change', () => {
        renderWeaponOptions(typeSelect.value);
        currentPage = 1;
        loadSkins();
    });

    renderWeaponOptions(typeSelect.value);
})();
</script>
@endsection
