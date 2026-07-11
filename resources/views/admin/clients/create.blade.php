<x-admin-layout :breadcrumbs="[
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Clientes', 'href' => route('admin.clients.index')],
    ['name' => 'Nuevo cliente'],
]">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 max-w-3xl">
        <form method="POST" action="{{ route('admin.clients.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.clients._form')
        </form>
    </div>
</x-admin-layout>
