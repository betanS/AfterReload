@extends('layouts.app')

@section('title', 'Tienda')

@section('content')
<div class="max-w-6xl mx-auto p-8">
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black mb-2">Tienda</h2>
                <p class="text-sm text-slate-300">Usa los filtros para explorar categorias, o busca cualquier skin con el buscador.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div>
                <label class="text-xs uppercase tracking-widest text-blue-300">Tipo de arma</label>
                <select id="type-select" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100">
                    @foreach($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs uppercase tracking-widest text-blue-300">Arma</label>
                <select id="weapon-select" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100"></select>
            </div>
            <div class="flex items-end">
                <button id="load-skins" class="w-full rounded-md bg-blue-600 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-blue-500">
                    Ver skins
                </button>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[240px]">
                <label class="text-xs uppercase tracking-widest text-blue-300">Buscar skin</label>
                <input id="search-input" type="text" placeholder="Ej: Dragon Lore" class="mt-2 w-full rounded-md border border-slate-800 bg-slate-950/70 p-3 text-sm text-slate-100">
            </div>
            <button id="search-skins" class="rounded-md border border-blue-500 px-6 py-3 text-sm font-bold uppercase tracking-wide text-blue-200 hover:bg-blue-500/10">
                Buscar
            </button>
            <button id="clear-search" class="rounded-md border border-slate-800 px-6 py-3 text-sm font-semibold text-slate-200 hover:border-slate-700">
                Limpiar
            </button>
        </div>

        <div class="mt-6 flex items-center justify-between text-sm text-slate-400">
            <span id="status">Selecciona filtros o busca una skin.</span>
            <div class="flex items-center gap-2">
                <button id="prev-page" class="rounded-md border border-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 hover:border-slate-700" disabled>
                    Anterior
                </button>
                <span id="page-indicator" class="text-xs">Pagina 1</span>
                <button id="next-page" class="rounded-md border border-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 hover:border-slate-700" disabled>
                    Siguiente
                </button>
            </div>
        </div>

        <div id="skins-grid" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"></div>
    </div>
</div>

<div id="purchase-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4">
    <div class="w-full max-w-md rounded-xl border border-slate-800 bg-slate-900 p-6">
        <h3 class="text-xl font-bold text-white">Confirmar compra</h3>
        <p id="modal-skin-name" class="mt-2 text-sm text-slate-300"></p>
        <p class="mt-4 text-xs text-slate-400">Esta es una plantilla, la compra real se conectara mas adelante.</p>
        <div class="mt-6 flex gap-3">
            <button id="confirm-buy" class="flex-1 rounded-md bg-blue-600 py-2 text-sm font-bold uppercase text-white hover:bg-blue-500">
                Confirmar
            </button>
            <button id="close-modal" class="flex-1 rounded-md border border-slate-800 py-2 text-sm font-semibold text-slate-200 hover:border-slate-700">
                Cancelar
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
            grid.innerHTML = '<div class="col-span-full text-slate-400">No hay skins para esta seleccion.</div>';
            return;
        }

        grid.innerHTML = items
            .map((item) => {
                return `
                    <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                        <div class="aspect-square overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                            <img src="${item.image}" alt="${item.name}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-100">${item.name}</p>
                        <button data-skin="${item.name}" class="mt-3 w-full rounded-md bg-blue-600 py-2 text-xs font-bold uppercase text-white hover:bg-blue-500">
                            Comprar
                        </button>
                    </div>
                `;
            })
            .join('');
    };

    const updatePagination = () => {
        pageIndicator.textContent = `Pagina ${currentPage}`;
        prevPage.disabled = currentPage <= 1;
        nextPage.disabled = !hasMore;
    };

    const loadSkins = async () => {
        const type = typeSelect.value;
        const weapon = weaponSelect.value;
        const search = searchInput.value.trim();

        status.textContent = 'Cargando skins...';
        grid.innerHTML = '';

        try {
            const response = await fetch(`{{ route('store.skins') }}?type=${encodeURIComponent(type)}&weapon=${encodeURIComponent(weapon)}&search=${encodeURIComponent(search)}&page=${currentPage}&per_page=16`);
            const data = await response.json();

            if (!data.success) {
                status.textContent = data.message || 'No se pudo cargar.';
                return;
            }

            hasMore = data.meta.has_more;
            status.textContent = `Mostrando ${data.data.length} skins`;
            renderCards(data.data);
            updatePagination();
        } catch (error) {
            status.textContent = 'Error al conectar con el catalogo local.';
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