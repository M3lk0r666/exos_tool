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

<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
        <ul class="space-y-2 font-medium">
            @foreach ($links as $link)
                @continue(isset($link['can']) && auth()->user()->cannot($link['can']))
                <li>
                    @isset($link['header'])
                        <div class="px-2 py-2 texst-xs font-bold text-gray-500 uppercase">
                            {{ $link['header'] }}
                        </div>
                    @else
                        @isset($link['submenu'])
                            <button type="button"
                                class="flex items-center w-full p-2 text-base text-gray-900 transition duration-75 rounded-lg group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                aria-controls="dropdown-example" data-collapse-toggle="dropdown-example">
                                <span class="w-6 h-6 inline-flex justify-center items-center">
                                    <i class="{{ $link['icon'] }}"></i>
                                </span>
                                <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">{{ $link['name'] }}</span>
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="m1 1 4 4 4-4" />
                                </svg>
                            </button>
                            <ul id="dropdown-example" class="hidden py-2 space-y-2">
                                @foreach ($link['submenu'] as $item)
                                    <li>
                                        <a href="{{ $item['href'] }}"
                                            class="flex items-center w-full p-2 text-gray-900 transition duration-75 rounded-lg pl-11 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">{{ $item['name'] }}</a>
                                    </li>
                                @endforeach

                            </ul>
                        @else
                            <a href="{{ $link['href'] }}"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ $link['active'] ? 'bg-gray-200' : '' }} ">
                                <span class="w-6 h-6 inline-flex justify-center items-center">
                                    <i class="{{ $link['icon'] }}"></i>
                                </span>
                                <span class="ms-3">{{ $link['name'] }}</span>
                            </a>
                        @endisset
                    @endisset
                </li>
            @endforeach
        </ul>
    </div>
</aside>
