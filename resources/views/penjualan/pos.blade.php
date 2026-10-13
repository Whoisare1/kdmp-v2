<x-layouts.app :title="$title" eyebrow="Kasir / POS">
    @if (session('success'))
        <div class="mb-4 rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 px-4 py-3 text-sm text-merah-800">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sm border border-merah-300 bg-merah-50 p-4 text-sm text-merah-800">
            <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm font-medium text-rose-700">Penjualan</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Kasir / POS</h1>
        </div>
        <a href="{{ route("$routeBase.index") }}" class="rounded-sm border border-paper-300 px-3 py-2 text-sm font-medium text-ink-700 hover:border-rose-300 hover:bg-rose-50">Riwayat transaksi</a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="min-w-0">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="font-semibold text-ink-900">Pilih barang</h2>
                    <p class="mt-1 text-sm text-ink-600">Stok demo awal 100 satuan dasar per barang; penjualan dan retur dihitung dari catatan transaksi.</p>
                </div>
                <div class="flex w-full flex-col gap-2 sm:max-w-sm sm:flex-row">
                    <label class="min-w-0 flex-1 text-sm">
                        <span class="sr-only">Unit usaha</span>
                        <select id="unit-select" name="id_unit_usaha" form="checkout-form" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                            <option value="">Pilih unit usaha</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id_unit_usaha }}">{{ $unit->nama_unit_usaha }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="min-w-0 flex-1">
                    <span class="sr-only">Cari barang</span>
                    <input id="product-search" type="search" placeholder="Cari nama barang..." class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                    </label>
                </div>
            </div>

            <div id="product-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @forelse ($products as $product)
                    <button type="button" data-product-card data-add-product="{{ $product->id_barang }}" data-name="{{ $product->nama_barang }}" data-price="{{ (float) $product->harga_jual_standar }}" data-unit="{{ $product->satuanDasar?->kode_satuan ?? 'unit' }}" data-unit-id="{{ $product->id_unit_usaha }}" data-stock="{{ number_format((float) $product->stok_demo, 4, '.', '') }}" @disabled($product->stok_demo <= 0) class="min-h-36 rounded-sm border border-paper-300 bg-paper-50 p-3 text-left transition hover:border-rose-400 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500 disabled:cursor-not-allowed disabled:opacity-50">
                        <span class="mb-3 grid size-9 place-items-center rounded-sm bg-rose-100 text-lg text-rose-700" aria-hidden="true">▦</span>
                        <span class="block line-clamp-2 min-h-10 text-sm font-semibold text-ink-900">{{ $product->nama_barang }}</span>
                        <span class="mt-2 block text-sm font-semibold text-rose-700">Rp {{ number_format((float) $product->harga_jual_standar, 0, ',', '.') }}</span>
                        <span class="text-xs text-ink-600">/{{ $product->satuanDasar?->kode_satuan ?? 'unit' }}</span>
                        <span data-stock-label class="mt-1 block text-xs font-medium text-emerald-800">Stok demo {{ number_format((float) $product->stok_demo, 0, ',', '.') }}</span>
                    </button>
                @empty
                    <div class="col-span-full border border-dashed border-paper-300 bg-paper-50 px-4 py-12 text-center">
                        <p class="font-medium text-ink-800">Belum ada barang untuk dijual.</p>
                        <p class="mt-1 text-sm text-ink-600">Barang aktif dan pengaturan harga jual koperasi akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="self-start overflow-hidden rounded-sm border border-rose-200 bg-paper-50">
            <div class="border-b border-rose-200 bg-rose-50 px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-rose-950">Keranjang</h2>
                        <p id="cart-count" class="mt-0.5 text-xs text-rose-800">0 barang</p>
                    </div>
                    <button id="clear-cart" type="button" class="text-xs font-semibold text-rose-700 hover:text-rose-900">Kosongkan</button>
                </div>
            </div>
            <form id="checkout-form" method="POST" action="{{ route("$routeBase.store") }}" class="space-y-3 p-4">
                @csrf
                <div id="cart-empty" class="border border-dashed border-paper-300 px-3 py-8 text-center text-sm text-ink-600">Pilih barang untuk memulai transaksi.</div>
                <div id="cart-items" class="divide-y divide-paper-200"></div>
                <div id="cart-inputs"></div>

                <label class="block text-sm">
                    <span class="mb-1 block font-medium text-ink-700">Pembeli</span>
                    <select name="id_pihak" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                        <option value="">Pilih pembeli</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id_pihak }}">{{ $customer->nama_pihak }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block font-medium text-ink-700">Metode pembayaran</span>
                    <select name="metode_bayar" required class="w-full rounded-sm border border-paper-300 bg-paper-50 px-3 py-2">
                        <option value="">Pilih metode</option>
                        <option value="tunai">Tunai</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </label>

                <div class="border-t border-paper-200 pt-3">
                    <div class="flex items-center justify-between text-sm text-ink-700"><span>Total</span><span id="cart-total" class="text-lg font-bold text-rose-700">Rp 0</span></div>
                    <button id="checkout-button" type="submit" disabled class="mt-4 w-full rounded-sm bg-rose-600 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">Simpan Penjualan</button>
                </div>
            </form>
        </aside>
    </div>

    <script>
        (() => {
            const cart = new Map();
            const cartItems = document.getElementById('cart-items');
            const cartInputs = document.getElementById('cart-inputs');
            const emptyState = document.getElementById('cart-empty');
            const unitSelect = document.getElementById('unit-select');
            const customerSelect = document.querySelector('select[name="id_pihak"]');
            const paymentSelect = document.querySelector('select[name="metode_bayar"]');
            const checkoutButton = document.getElementById('checkout-button');
            const formatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

            function syncProducts() {
                document.querySelectorAll('[data-product-card]').forEach((card) => {
                    const item = cart.get(card.dataset.addProduct);
                    const remaining = Math.max(0, Number(card.dataset.stock) - (item?.quantity ?? 0));
                    const stockLabel = card.querySelector('[data-stock-label]');
                    stockLabel.textContent = `Stok demo ${formatter.format(remaining)}`;
                    card.disabled = remaining <= 0 || (unitSelect.value && card.dataset.unitId !== unitSelect.value);
                    card.classList.toggle('hidden', Boolean(unitSelect.value && card.dataset.unitId !== unitSelect.value));
                });
            }

            function syncSubmitState() {
                checkoutButton.disabled = cart.size === 0
                    || !unitSelect.value
                    || !customerSelect.value
                    || !paymentSelect.value;
            }

            function renderCart() {
                cartItems.replaceChildren();
                cartInputs.replaceChildren();
                let total = 0;
                let count = 0;
                let index = 0;

                cart.forEach((item, id) => {
                    total += item.price * item.quantity;
                    count += item.quantity;

                    const row = document.createElement('div');
                    row.className = 'flex items-start justify-between gap-3 py-3';
                    const details = document.createElement('div');
                    details.className = 'min-w-0';
                    const name = document.createElement('p');
                    name.className = 'truncate text-sm font-medium text-ink-900';
                    name.textContent = item.name;
                    const subtotal = document.createElement('p');
                    subtotal.className = 'mt-1 text-xs text-ink-600';
                    subtotal.textContent = `Rp ${formatter.format(item.price * item.quantity)}`;
                    details.append(name, subtotal);

                    const controls = document.createElement('div');
                    controls.className = 'flex shrink-0 items-center gap-2';
                    const decrease = document.createElement('button');
                    decrease.type = 'button';
                    decrease.className = 'grid size-7 place-items-center rounded-sm border border-paper-300 text-ink-700 hover:border-rose-300 hover:text-rose-700';
                    decrease.textContent = '−';
                    decrease.setAttribute('aria-label', `Kurangi ${item.name}`);
                    decrease.addEventListener('click', () => {
                        if (item.quantity <= 1) cart.delete(id);
                        else item.quantity -= 1;
                        renderCart();
                    });
                    const quantity = document.createElement('span');
                    quantity.className = 'min-w-5 text-center text-sm font-medium';
                    quantity.textContent = item.quantity;
                    const increase = document.createElement('button');
                    increase.type = 'button';
                    increase.className = 'grid size-7 place-items-center rounded-sm border border-paper-300 text-ink-700 hover:border-rose-300 hover:text-rose-700';
                    increase.textContent = '+';
                    increase.setAttribute('aria-label', `Tambah ${item.name}`);
                    increase.disabled = item.quantity >= item.available;
                    increase.addEventListener('click', () => {
                        if (item.quantity < item.available) item.quantity += 1;
                        renderCart();
                    });
                    controls.append(decrease, quantity, increase);
                    row.append(details, controls);
                    cartItems.append(row);

                    [
                        ['id_barang', id],
                        ['qty_dasar', item.quantity],
                    ].forEach(([field, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `items[${index}][${field}]`;
                        input.value = value;
                        cartInputs.append(input);
                    });
                    index += 1;
                });

                emptyState.classList.toggle('hidden', cart.size > 0);
                document.getElementById('cart-count').textContent = `${count} barang`;
                document.getElementById('cart-total').textContent = `Rp ${formatter.format(total)}`;
                syncProducts();
                syncSubmitState();
            }

            document.querySelectorAll('[data-add-product]').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = button.dataset.addProduct;
                    if (!unitSelect.value) unitSelect.value = button.dataset.unitId;
                    if (unitSelect.value !== button.dataset.unitId) return;
                    const item = cart.get(id) ?? {
                        name: button.dataset.name,
                        price: Number(button.dataset.price),
                        unit: button.dataset.unit,
                        unitId: button.dataset.unitId,
                        available: Number(button.dataset.stock),
                        quantity: 0,
                    };
                    if (item.quantity >= item.available) return;
                    item.quantity += 1;
                    cart.set(id, item);
                    renderCart();
                });
            });

            document.getElementById('clear-cart').addEventListener('click', () => { cart.clear(); renderCart(); });
            unitSelect.addEventListener('change', () => {
                for (const [id, item] of cart) {
                    if (item.unitId !== unitSelect.value) cart.delete(id);
                }
                renderCart();
            });
            customerSelect.addEventListener('change', syncSubmitState);
            paymentSelect.addEventListener('change', syncSubmitState);
            document.getElementById('product-search').addEventListener('input', (event) => {
                const search = event.target.value.trim().toLocaleLowerCase('id-ID');
                document.querySelectorAll('[data-product-card]').forEach((card) => {
                    const matchesName = card.dataset.name.toLocaleLowerCase('id-ID').includes(search);
                    const matchesUnit = !unitSelect.value || card.dataset.unitId === unitSelect.value;
                    card.classList.toggle('hidden', !matchesName || !matchesUnit);
                });
            });

            renderCart();
        })();
    </script>
</x-layouts.app>