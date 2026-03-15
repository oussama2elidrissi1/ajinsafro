@extends('layouts.master-without-nav')
@section('title')
    Compte en attente
@endsection
@section('content')
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="card-body text-center p-5">
                            <div class="avatar-lg mx-auto mb-4">
                                <span class="avatar-title rounded-circle bg-warning bg-opacity-10 text-warning font-size-24">
                                    <i class="bx bx-time-five"></i>
                                </span>
                            </div>
                            <h4 class="mb-3">Compte en cours de validation</h4>
                            <p class="text-muted mb-4">
                                Votre demande d’inscription a bien été reçue. <strong>Votre compte partenaire doit être validé par un administrateur.</strong>
                            </p>
                            <p class="text-muted mb-4">
                                Vous recevrez un email dès que votre compte sera activé. Vous pourrez alors accéder à votre espace partenaire (réservations, clients).
                            </p>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary">Se déconnecter</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
