<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="{{ url('demo') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ URL::asset('build/images/logo-sm-dark.png') }}" alt="" height="20">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="18">
                    </span>
                </a>

                <a href="{{ url('demo') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ URL::asset('build/images/logo-sm-dark.png') }}" alt="" height="20">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="18">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 d-lg-none header-item waves-effect waves-light"
                data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <div class="topnav">
                <nav class="navbar navbar-light navbar-expand-lg topnav-menu">

                    <div class="collapse navbar-collapse" id="topnav-menu-content">
                        <ul class="navbar-nav">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-dashboard"
                                    role="button">
                                    Dashboard <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-dashboard">
                                    <a href="{{ url('demo') }}" class="dropdown-item">Dashboard 1</a>
                                    <a href="{{ url('demo/index-2') }}" class="dropdown-item">Dashboard 2</a>
                                </div>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-ui-element"
                                    role="button">
                                    UI Elements <div class="arrow-down"></div>
                                </a>

                                <div class="dropdown-menu mega-dropdown-menu px-2 dropdown-mega-menu-xl"
                                    aria-labelledby="topnav-ui-element">
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div>
                                                <a href="{{ url('demo/ui-alerts') }}" class="dropdown-item">Alerts</a>
                                                <a href="{{ url('demo/ui-buttons') }}" class="dropdown-item">Buttons</a>
                                                <a href="{{ url('demo/ui-cards') }}" class="dropdown-item">Cards</a>
                                                <a href="{{ url('demo/ui-carousel') }}" class="dropdown-item">Carousel</a>
                                                <a href="{{ url('demo/ui-dropdowns') }}" class="dropdown-item">Dropdowns</a>
                                                <a href="{{ url('demo/ui-grid') }}" class="dropdown-item">Grid</a>
                                                <a href="{{ url('demo/ui-images') }}" class="dropdown-item">Images</a>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div>
                                                <a href="{{ url('demo/ui-lightbox') }}" class="dropdown-item">Lightbox</a>
                                                <a href="{{ url('demo/ui-modals') }}" class="dropdown-item">Modals</a>
                                                <a href="{{ url('demo/ui-rangeslider') }}" class="dropdown-item">Range Slider</a>
                                                <a href="{{ url('demo/ui-session-timeout') }}" class="dropdown-item">Session
                                                    Timeout</a>
                                                <a href="{{ url('demo/ui-progressbars') }}" class="dropdown-item">Progress Bars</a>
                                                <a href="{{ url('demo/ui-sweet-alert') }}" class="dropdown-item">Sweet-Alert</a>
                                                <a href="{{ url('demo/ui-tabs-accordions') }}" class="dropdown-item">Tabs &
                                                    Accordions</a>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div>
                                                <a href="{{ url('demo/ui-typography') }}" class="dropdown-item">Typography</a>
                                                <a href="{{ url('demo/ui-video') }}" class="dropdown-item">Video</a>
                                                <a href="{{ url('demo/ui-general') }}" class="dropdown-item">General</a>
                                                <a href="{{ url('demo/ui-colors') }}" class="dropdown-item">Colors</a>
                                                <a href="{{ url('demo/ui-rating') }}" class="dropdown-item">Rating</a>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-app-pages"
                                    role="button">
                                    Apps <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-app-pages">

                                    <a href="{{ url('demo/calendar') }}" class="dropdown-item">Calendar</a>
                                    <div class="dropdown">
                                        <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-email"
                                            role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            Email <div class="arrow-down"></div>
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="topnav-email">
                                            <a href="{{ url('demo/email-inbox') }}" class="dropdown-item">Inbox</a>
                                            <a href="{{ url('demo/email-read') }}" class="dropdown-item">Read Email</a>
                                        </div>
                                    </div>

                                    <div class="dropdown">
                                        <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-task"
                                            role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            Tasks <div class="arrow-down"></div>
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="topnav-task">
                                            <a href="{{ url('demo/tasks-list') }}" class="dropdown-item">Task List</a>
                                            <a href="{{ url('demo/tasks-kanban') }}" class="dropdown-item">Kanban Board</a>
                                            <a href="{{ url('demo/tasks-create') }}" class="dropdown-item">Create Task</a>
                                        </div>
                                    </div>

                                </div>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-components"
                                    role="button">
                                    Components <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-components">

                                    <div class="dropdown">
                                        <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-form"
                                            role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            Forms <div class="arrow-down"></div>
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="topnav-form">
                                            <a href="{{ url('demo/form-elements') }}" class="dropdown-item">Form Elements</a>
                                            <a href="{{ url('demo/form-validation') }}" class="dropdown-item">Form Validation</a>
                                            <a href="{{ url('demo/form-advanced') }}" class="dropdown-item">Form Advanced</a>
                                            <a href="{{ url('demo/form-editors') }}" class="dropdown-item">Form Editors</a>
                                            <a href="{{ url('demo/form-uploads') }}" class="dropdown-item">Form File Upload</a>
                                            <a href="{{ url('demo/form-xeditable') }}" class="dropdown-item">Form Xeditable</a>
                                            <a href="{{ url('demo/form-repeater') }}" class="dropdown-item">Form Repeater</a>
                                            <a href="{{ url('demo/form-wizard') }}" class="dropdown-item">Form Wizard</a>
                                            <a href="{{ url('demo/form-mask') }}" class="dropdown-item">Form Mask</a>
                                        </div>
                                    </div>

                                    <div class="dropdown">
                                        <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-table"
                                            role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            Tables <div class="arrow-down"></div>
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="topnav-table">
                                            <a href="{{ url('demo/tables-basic') }}" class="dropdown-item">Basic Tables</a>
                                            <a href="{{ url('demo/tables-datatable') }}" class="dropdown-item">Data Tables</a>
                                            <a href="{{ url('demo/tables-responsive') }}" class="dropdown-item">Responsive Table</a>
                                            <a href="{{ url('demo/tables-editable') }}" class="dropdown-item">Editable Table</a>
                                        </div>
                                    </div>

                                    <div class="dropdown">
                                        <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-charts"
                                            role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            Charts <div class="arrow-down"></div>
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="topnav-charts">
                                            <a href="{{ url('demo/charts-apex') }}" class="dropdown-item">Apex charts</a>
                                            <a href="{{ url('demo/charts-chartjs') }}" class="dropdown-item">Chartjs Chart</a>
                                            <a href="{{ url('demo/charts-flot') }}" class="dropdown-item">Flot Chart</a>
                                            <a href="{{ url('demo/charts-knob') }}" class="dropdown-item">Jquery Knob Chart</a>
                                            <a href="{{ url('demo/charts-sparkline') }}" class="dropdown-item">Sparkline Chart</a>
                                        </div>
                                    </div>

                                    <div class="dropdown">
                                        <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-icons"
                                            role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            Icons <div class="arrow-down"></div>
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="topnav-icons">
                                            <a href="{{ url('demo/icons-boxicons') }}" class="dropdown-item">Boxicons</a>
                                            <a href="{{ url('demo/icons-materialdesign') }}" class="dropdown-item">Material Design</a>
                                            <a href="{{ url('demo/icons-dripicons') }}" class="dropdown-item">Dripicons</a>
                                            <a href="{{ url('demo/icons-fontawesome') }}" class="dropdown-item">Font awesome</a>
                                        </div>
                                    </div>

                                    <div class="dropdown">
                                        <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-map"
                                            role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            Maps <div class="arrow-down"></div>
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="topnav-map">
                                            <a href="{{ url('demo/maps-google') }}" class="dropdown-item">Google Maps</a>
                                            <a href="{{ url('demo/maps-vector') }}" class="dropdown-item">Vector Maps</a>
                                        </div>
                                    </div>

                                    <div class="dropdown">
                                        <a class="dropdown-item dropdown-toggle arrow-none" href="#" id="topnav-layouts"
                                            role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                            Layouts <div class="arrow-down"></div>
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="topnav-layouts">
                                            <div class="dropdown">
                                                <a class="dropdown-item dropdown-toggle arrow-none" href="#"
                                                    id="topnav-vertical" role="button" data-bs-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false">
                                                    Vertical <div class="arrow-down"></div>

                                                </a>

                                                <div class="dropdown-menu" aria-labelledby="topnav-vertical">
                                                    <a href="{{ url('demo/layouts-compact-sidebar') }}" class="dropdown-item">Compact
                                                        Sidebar</a>
                                                    <a href="{{ url('demo/layouts-icon-sidebar') }}" class="dropdown-item">Icon
                                                        Sidebar</a>
                                                    <a href="{{ url('demo/layouts-boxed') }}" class="dropdown-item">Boxed Layout</a>
                                                    <a href="{{ url('demo/layouts-preloader') }}" class="dropdown-item">Preloader</a>
                                                </div>
                                            </div>

                                            <div class="dropdown">
                                                <a class="dropdown-item dropdown-toggle arrow-none" href="#"
                                                    id="topnav-horizontal" role="button" data-bs-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false">
                                                    Horizontal<div class="arrow-down"></div>

                                                </a>

                                                <div class="dropdown-menu" aria-labelledby="topnav-horizontal">
                                                    <a href="{{ url('demo/layouts-horizontal') }}"
                                                        class="dropdown-item">Horizontal</a>
                                                    <a href="{{ url('demo/layouts-hori-topbarlight') }}" class="dropdown-item">Topbar
                                                        Light</a>
                                                    <a href="{{ url('demo/layouts-hori-boxed') }}" class="dropdown-item">Boxed
                                                        Layout</a>
                                                    <a href="{{ url('demo/layouts-hori-preloader') }}"
                                                        class="dropdown-item">Preloader</a>
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                </div>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-pages" role="button">
                                    Pages <div class="arrow-down"></div>
                                </a>

                                <div class="dropdown-menu mega-dropdown-menu px-2 dropdown-mega-menu-lg dropdown-menu-right"
                                    aria-labelledby="topnav-pages">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div>

                                                <a href="{{ url('demo/pages-login') }}" class="dropdown-item">Login</a>
                                                <a href="{{ url('demo/pages-register') }}" class="dropdown-item">Register</a>
                                                <a href="{{ url('demo/pages-recoverpw') }}" class="dropdown-item">Recover
                                                    Password</a>
                                                <a href="{{ url('demo/pages-lock-screen') }}" class="dropdown-item">Lock Screen</a>
                                                <a href="{{ url('demo/pages-starter') }}" class="dropdown-item">Starter Page</a>
                                                <a href="{{ url('demo/pages-invoice') }}" class="dropdown-item">Invoice</a>
                                                <a href="{{ url('demo/pages-profile') }}" class="dropdown-item">Profile</a>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div>

                                                <a href="{{ url('demo/pages-maintenance') }}" class="dropdown-item">Maintenance</a>
                                                <a href="{{ url('demo/pages-comingsoon') }}" class="dropdown-item">Coming Soon</a>
                                                <a href="{{ url('demo/pages-timeline') }}" class="dropdown-item">Timeline</a>
                                                <a href="{{ url('demo/pages-faqs') }}" class="dropdown-item">FAQs</a>
                                                <a href="{{ url('demo/pages-pricing') }}" class="dropdown-item">Pricing</a>
                                                <a href="{{ url('demo/pages-404') }}" class="dropdown-item">Error 404</a>
                                                <a href="{{ url('demo/pages-500') }}" class="dropdown-item">Error 500</a>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </li>

                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        <div class="d-flex">
            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="mdi mdi-magnify"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0"
                    aria-labelledby="page-header-search-dropdown">

                    <form class="p-3">
                        <div class="form-group m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search ..."
                                    aria-label="Recipient's username">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i
                                            class="mdi mdi-magnify"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dropdown d-none d-sm-inline-block">
                <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <img class="" src="{{ URL::asset('build/images/flags/us.jpg') }}" alt="Header Language" height="16">
                </button>
                <div class="dropdown-menu dropdown-menu-end">

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <img src="{{ URL::asset('build/images/flags/spain.jpg') }}" alt="user-image" class="me-1" height="12"> <span
                            class="align-middle">Spanish</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <img src="{{ URL::asset('build/images/flags/germany.jpg') }}" alt="user-image" class="me-1" height="12"> <span
                            class="align-middle">German</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <img src="{{ URL::asset('build/images/flags/italy.jpg') }}" alt="user-image" class="me-1" height="12"> <span
                            class="align-middle">Italian</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <img src="{{ URL::asset('build/images/flags/russia.jpg') }}" alt="user-image" class="me-1" height="12"> <span
                            class="align-middle">Russian</span>
                    </a>
                </div>
            </div>

            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
                    <i class="mdi mdi-fullscreen"></i>
                </button>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon waves-effect"
                    id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <i class="mdi mdi-bell-outline"></i>
                    <span class="badge bg-danger rounded-pill">3</span>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-notifications-dropdown">
                    <div class="p-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0"> Notifications </h6>
                            </div>
                            <div class="col-auto">
                                <a href="#!" class="small"> View All</a>
                            </div>
                        </div>
                    </div>
                    <div data-simplebar style="max-height: 230px;">
                        <a href="" class="text-reset notification-item">
                            <div class="d-flex align-items-start">
                                <div class="avatar-xs me-3">
                                    <span class="avatar-title bg-primary rounded-circle font-size-16">
                                        <i class="bx bx-cart"></i>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <h6 class="mt-0 mb-1">Your order is placed</h6>
                                    <div class="font-size-12 text-muted">
                                        <p class="mb-1">If several languages coalesce the grammar</p>
                                        <p class="mb-0"><i class="mdi mdi-clock-outline"></i> 3 min ago</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <a href="" class="text-reset notification-item">
                            <div class="d-flex align-items-start">
                                <img src="{{ URL::asset('build/images/users/avatar-3.jpg') }}" class="me-3 rounded-circle avatar-xs"
                                    alt="user-pic">
                                <div class="flex-1">
                                    <h6 class="mt-0 mb-1">James Lemire</h6>
                                    <div class="font-size-12 text-muted">
                                        <p class="mb-1">It will seem like simplified English.</p>
                                        <p class="mb-0"><i class="mdi mdi-clock-outline"></i> 1 hours ago</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <a href="" class="text-reset notification-item">
                            <div class="d-flex align-items-start">
                                <div class="avatar-xs me-3">
                                    <span class="avatar-title bg-success rounded-circle font-size-16">
                                        <i class="bx bx-badge-check"></i>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <h6 class="mt-0 mb-1">Your item is shipped</h6>
                                    <div class="font-size-12 text-muted">
                                        <p class="mb-1">If several languages coalesce the grammar</p>
                                        <p class="mb-0"><i class="mdi mdi-clock-outline"></i> 3 min ago</p>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <a href="" class="text-reset notification-item">
                            <div class="d-flex align-items-start">
                                <img src="{{ URL::asset('build/images/users/avatar-4.jpg') }}" class="me-3 rounded-circle avatar-xs"
                                    alt="user-pic">
                                <div class="flex-1">
                                    <h6 class="mt-0 mb-1">Salena Layfield</h6>
                                    <div class="font-size-12 text-muted">
                                        <p class="mb-1">As a skeptical Cambridge friend of mine occidental.</p>
                                        <p class="mb-0"><i class="mdi mdi-clock-outline"></i> 1 hours ago</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="p-2 border-top d-grid">
                        <a class="btn btn-sm btn-link font-size-14 btn-block text-center" href="javascript:void(0)">
                            <i class="mdi mdi-arrow-right-circle me-1"></i> View More..
                        </a>
                    </div>
                </div>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user" src="{{ URL::asset('build/images/users/avatar-2.jpg') }}"
                        alt="Header Avatar">
                    <span class="d-none d-xl-inline-block ms-1">Patrick</span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <!-- item-->
                    <a class="dropdown-item" href="#"><i class="bx bx-user font-size-16 align-middle me-1"></i>
                        Profile</a>
                    <a class="dropdown-item" href="#"><i class="bx bx-wallet font-size-16 align-middle me-1"></i> My
                        Wallet</a>
                    <a class="dropdown-item d-block" href="#"><span class="badge bg-success float-end">11</span><i
                            class="bx bx-wrench font-size-16 align-middle me-1"></i> Settings</a>
                    <a class="dropdown-item" href="#"><i class="bx bx-lock-open font-size-16 align-middle me-1"></i>
                        Lock screen</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> Logout</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon right-bar-toggle waves-effect" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample">
                    <i class="mdi mdi-settings-outline"></i>
                </button>
            </div>
        </div>
    </div>
</header>