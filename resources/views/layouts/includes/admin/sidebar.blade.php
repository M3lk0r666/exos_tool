@php
    $links = [
        [
            'name' => 'Dashboard',
            'icon' => 'ri-dashboard-line',
            'href' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
        ],
        [
            'header' => 'ANÁLISIS',
        ],
        [
            'name' => 'Clientes',
            'icon' => 'ri-building-line',
            'href' => route('admin.clients.index'),
            'active' => request()->routeIs('admin.clients.*'),
        ],
        [
            'name' => 'Equipos',
            'icon' => 'ri-router-line',
            'href' => route('admin.devices.index'),
            'active' => request()->routeIs('admin.devices.*'),
        ],
        [
            'name' => 'Capturas',
            'icon' => 'ri-upload-cloud-2-line',
            'href' => route('admin.captures.index'),
            'active' => request()->routeIs('admin.captures.*'),
        ],
        [
            'name' => 'Reportes',
            'icon' => 'ri-file-chart-line',
            'href' => route('admin.reports.index'),
            'active' => request()->routeIs('admin.reports.*'),
        ],
        [
            'name' => 'Metodología',
            'icon' => 'ri-shield-check-line',
            'href' => route('admin.methodology'),
            'active' => request()->routeIs('admin.methodology*'),
        ],
        [
            'header' => 'ADMINISTRACIÓN',
        ],
        [
            'name' => 'Usuarios',
            'icon' => 'ri-group-line',
            'href' => route('admin.users.index'),
            'active' => request()->routeIs('admin.users.*'),
            'can' => 'users.manage',
        ],
        [
            'name' => 'Reglas de análisis',
            'icon' => 'ri-settings-3-line',
            'href' => route('admin.rules.index'),
            'active' => request()->routeIs('admin.rules.*'),
            'can' => 'rules.manage',
        ],
        [
            'name' => 'Configuración',
            'icon' => 'ri-tools-line',
            'href' => route('admin.settings.index'),
            'active' => request()->routeIs('admin.settings.*'),
            'can' => 'settings.manage',
        ],
        [
            'name' => 'Auditoría',
            'icon' => 'ri-history-line',
            'href' => route('admin.audit.index'),
            'active' => request()->routeIs('admin.audit.*'),
            'can' => 'audit.view',
        ],
        [
            'name' => 'API',
            'icon' => 'ri-code-s-slash-line',
            'href' => route('admin.api-docs'),
            'active' => request()->routeIs('admin.api-docs'),
        ],
    ];
@endphp

<aside id="app-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-16 transition-[width] duration-200 -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
    aria-label="Sidebar">
    <div class="h-full flex flex-col overflow-y-auto overflow-x-hidden">
        <ul class="flex-1 px-3 py-4 space-y-1 font-medium">
            @foreach ($links as $link)
                @continue(isset($link['can']) && auth()->user()->cannot($link['can']))
                <li>
                    @isset($link['header'])
                        <div class="sidebar-section px-2 pt-4 pb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">
                            {{ $link['header'] }}
                        </div>
                    @else
                        <a href="{{ $link['href'] }}"
                            title="{{ $link['name'] }}"
                            class="group flex items-center gap-3 p-2 rounded-md border-l-2 transition-colors
                                {{ $link['active']
                                    ? 'bg-blue-50 text-blue-700 border-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-semibold'
                                    : 'text-gray-700 border-transparent hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                            <span class="w-6 h-6 inline-flex justify-center items-center shrink-0 text-lg">
                                <i class="{{ $link['icon'] }}"></i>
                            </span>
                            <span class="sidebar-label whitespace-nowrap">{{ $link['name'] }}</span>
                        </a>
                    @endisset
                </li>
            @endforeach
        </ul>

        {{-- Botón para colapsar / expandir --}}
        <div class="border-t border-gray-200 dark:border-gray-700 p-2">
            <button type="button" onclick="toggleSidebar()"
                class="group flex items-center gap-3 w-full p-2 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700"
                title="Colapsar / expandir menú">
                <span class="w-6 h-6 inline-flex justify-center items-center shrink-0 text-lg">
                    <i class="ri-menu-fold-line sidebar-icon-expanded"></i>
                    <i class="ri-menu-unfold-line sidebar-icon-collapsed hidden"></i>
                </span>
                <span class="sidebar-label whitespace-nowrap text-sm">Colapsar menú</span>
            </button>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('exos_sidebar_collapsed', collapsed ? '1' : '0'); } catch (e) {}
    }
</script>
