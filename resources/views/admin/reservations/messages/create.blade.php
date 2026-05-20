@extends('layouts.admin-v6')

@section('title', 'Nouveau message')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Nouveau message</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.reservations.messages') }}">Messages</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ route('admin.reservations.messages.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Objet <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required maxlength="255" placeholder="Objet du message">
                            @error('subject')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="body" class="form-control" rows="8" required placeholder="Contenu du message�?�">{{ old('body') }}</textarea>
                            @error('body')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <p class="text-muted small">Le message sera envoyé au nom de votre agence.</p>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-send me-1"></i> Envoyer</button>
                            <a href="{{ route('admin.reservations.messages') }}" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


