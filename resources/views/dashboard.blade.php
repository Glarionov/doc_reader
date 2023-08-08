<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    You can upload file here
                    <form method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mt-2">
                            <input type="file" name="file" />
                        </div>

                        <div class="mt-2">
                            <input type="submit" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent rounded" />
                        </div>

                    </form>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-3">
                        @if (!empty($uploaded))
                            File uploaded, you can see uploading status
                            <a href="{{route('reading-status')}}?code={{$savingProcessCode}}" target="_blank"> here</a>
                            <br />
                            And see results
                            <a href="{{route('data-rows')}}" target="_blank"> here</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
