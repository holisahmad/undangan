@php
    $tabs = [
        ['route' => 'admin.weddings.index', 'pattern' => 'admin.weddings.*', 'label' => 'Undangan'],
        ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'label' => 'Pengguna'],
        ['route' => 'admin.orders.index', 'pattern' => 'admin.orders.*', 'label' => 'Pesanan'],
        ['route' => 'admin.leads.index', 'pattern' => 'admin.leads.*', 'label' => 'Lead'],
        ['route' => 'admin.testimonials.index', 'pattern' => 'admin.testimonials.*', 'label' => 'Testimoni'],
        ['route' => 'admin.analytics.index', 'pattern' => 'admin.analytics.*', 'label' => 'Analitik'],
        ['route' => 'admin.audit-logs.index', 'pattern' => 'admin.audit-logs.*', 'label' => 'Audit log'],
    ];
@endphp

<nav class="mt-6 flex flex-wrap gap-2 border-b border-stone-200 pb-2 text-sm" aria-label="Navigasi admin">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           @if (request()->routeIs($tab['pattern'])) aria-current="page" @endif
           class="rounded-md px-3 py-1.5 font-medium {{ request()->routeIs($tab['pattern']) ? 'bg-stone-900 text-white' : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
