@php
    /**
     * master-ajinsafro : layout partagé entre le portail agent et quelques pages admin.
     *  - portail agent (routes agent.* ou utilisateur « portail agent ») : layouts.master-ajinsafro-agent (inchangé) ;
     *  - administration : coque unique Espace Admin v2, avec le socle de scripts Qovex (vendor-scripts)
     *    dont dépendent encore ces pages.
     */
    $useAgentPortal = request()->routeIs('agent.*')
        || request()->attributes->get('agent_reservation_mode', false)
        || \App\Services\View\AgentPortalLayout::shouldUse(auth()->user());
@endphp

@extends($useAgentPortal ? 'layouts.master-ajinsafro-agent' : 'layouts.espace-admin-v2')

@unless($useAgentPortal)
    @section('body_class', 'admin-premium-ui aj-admin-compact')

    @section('core_scripts')
        @include('layouts.vendor-scripts')
    @endsection

    @push('styles')
        <link href="{{ URL::asset('css/admin-compact.css') }}?v=workspace-fixed-v7" rel="stylesheet" type="text/css" />
    @endpush
@endunless
