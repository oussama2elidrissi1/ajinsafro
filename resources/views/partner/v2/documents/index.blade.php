@extends('partner_v2.layouts.app')
@section('title', 'Documents')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Documents</h1>
    <p class="text-sm text-gray-500 mt-1">Contrat partenaire, grilles, conditions et supports (uniquement vos documents).</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
    @forelse($documents as $doc)
        <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-5 flex items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ $typeLabels[$doc->type] ?? $doc->type }}</div>
                <div class="font-bold text-[#0e3a5a] mt-1 truncate">{{ $doc->name ?: 'Document' }}</div>
                <div class="text-[11px] text-gray-400 font-semibold mt-1 truncate">{{ $doc->file_path }}</div>
            </div>
            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" rel="noopener"
               class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors shrink-0">
                Télécharger
            </a>
        </div>
    @empty
        <div class="col-span-full">
            <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-6 text-gray-600">
                Aucun document disponible pour le moment.
            </div>
        </div>
    @endforelse
</div>
@endsection

