<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div class="h-100">

        <div class="user-wid text-center py-4">
            <div class="user-img">
                <img src="{{ URL::Asset('build/images/users/avatar-2.jpg') }}" alt="" class="avatar-md mx-auto rounded-circle">
            </div>

            <div class="mt-3">

                <a href="#" class="text-body fw-medium font-size-16">Patrick Becker</a>
                <p class="text-muted mt-1 mb-0 font-size-13">UI/UX Designer</p>

            </div>
        </div>

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect">
                        <i class="mdi mdi-airplay"></i><span class="badge rounded-pill bg-info float-end">2</span>
                        <span>Dashboard</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo') }}">Dashboard 1</a></li>
                        <li><a href="{{ url('demo/index-2') }}">Dashboard 2</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-flip-horizontal"></i>
                        <span>Layouts</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="true">
                        <li><a href="javascript: void(0);" class="has-arrow">Vertical</a>
                            <ul class="sub-menu" aria-expanded="true">
                                <li><a href="{{ url('demo/layouts-compact-sidebar') }}">Compact Sidebar</a></li>
                                <li><a href="{{ url('demo/layouts-icon-sidebar') }}">Icon Sidebar</a></li>
                                <li><a href="{{ url('demo/layouts-boxed') }}">Boxed Layout</a></li>
                                <li><a href="{{ url('demo/layouts-preloader') }}">Preloader</a></li>
                            </ul>
                        </li>

                        <li><a href="javascript: void(0);" class="has-arrow">Horizontal</a>
                            <ul class="sub-menu" aria-expanded="true">
                                <li><a href="{{ url('demo/layouts-horizontal') }}">Horizontal</a></li>
                                <li><a href="{{ url('demo/layouts-hori-topbarlight') }}">Topbar Light</a></li>
                                <li><a href="{{ url('demo/layouts-hori-boxed') }}">Boxed Layout</a></li>
                                <li><a href="{{ url('demo/layouts-hori-preloader') }}">Preloader</a></li>
                            </ul>
                        </li>
                    </ul>

                </li>

                <li>
                    <a href="{{ url('demo/calendar') }}" class=" waves-effect">
                        <i class="mdi mdi-calendar-text"></i>
                        <span>Calendar</span>
                    </a>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-inbox-full"></i>
                        <span>Email</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo/email-inbox') }}">Inbox</a></li>
                        <li><a href="{{ url('demo/email-read') }}">Read Email</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-calendar-check"></i>
                        <span>Tasks</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo/tasks-list') }}">Task List</a></li>
                        <li><a href="{{ url('demo/tasks-kanban') }}">Kanban Board</a></li>
                        <li><a href="{{ url('demo/tasks-create') }}">Create Task</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-account-circle-outline"></i>
                        <span>Pages</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo/pages-login') }}">Login</a></li>
                        <li><a href="{{ url('demo/pages-register') }}">Register</a></li>
                        <li><a href="{{ url('demo/pages-recoverpw') }}">Recover Password</a></li>
                        <li><a href="{{ url('demo/pages-lock-screen') }}">Lock Screen</a></li>
                        <li><a href="{{ url('demo/pages-starter') }}">Starter Page</a></li>
                        <li><a href="{{ url('demo/pages-invoice') }}">Invoice</a></li>
                        <li><a href="{{ url('demo/pages-profile') }}">Profile</a></li>
                        <li><a href="{{ url('demo/pages-maintenance') }}">Maintenance</a></li>
                        <li><a href="{{ url('demo/pages-comingsoon') }}">Coming Soon</a></li>
                        <li><a href="{{ url('demo/pages-timeline') }}">Timeline</a></li>
                        <li><a href="{{ url('demo/pages-faqs') }}">FAQs</a></li>
                        <li><a href="{{ url('demo/pages-pricing') }}">Pricing</a></li>
                        <li><a href="{{ url('demo/pages-404') }}">Error 404</a></li>
                        <li><a href="{{ url('demo/pages-500') }}">Error 500</a></li>
                    </ul>
                </li>

                <li class="menu-title">Components</li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-checkbox-multiple-blank-outline"></i>
                        <span>UI Elements</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo/ui-alerts') }}">Alerts</a></li>
                        <li><a href="{{ url('demo/ui-buttons') }}">Buttons</a></li>
                        <li><a href="{{ url('demo/ui-cards') }}">Cards</a></li>
                        <li><a href="{{ url('demo/ui-carousel') }}">Carousel</a></li>
                        <li><a href="{{ url('demo/ui-dropdowns') }}">Dropdowns</a></li>
                        <li><a href="{{ url('demo/ui-grid') }}">Grid</a></li>
                        <li><a href="{{ url('demo/ui-images') }}">Images</a></li>
                        <li><a href="{{ url('demo/ui-lightbox') }}">Lightbox</a></li>
                        <li><a href="{{ url('demo/ui-modals') }}">Modals</a></li>
                        <li><a href="{{ url('demo/ui-rangeslider') }}">Range Slider</a></li>
                        <li><a href="{{ url('demo/ui-session-timeout') }}">Session Timeout</a></li>
                        <li><a href="{{ url('demo/ui-progressbars') }}">Progress Bars</a></li>
                        <li><a href="{{ url('demo/ui-sweet-alert') }}">Sweet-Alert</a></li>
                        <li><a href="{{ url('demo/ui-tabs-accordions') }}">Tabs & Accordions</a></li>
                        <li><a href="{{ url('demo/ui-typography') }}">Typography</a></li>
                        <li><a href="{{ url('demo/ui-video') }}">Video</a></li>
                        <li><a href="{{ url('demo/ui-general') }}">General</a></li>
                        <li><a href="{{ url('demo/ui-colors') }}">Colors</a></li>
                        <li><a href="{{ url('demo/ui-rating') }}">Rating</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="waves-effect">
                        <i class="mdi mdi-newspaper"></i>
                        <span class="badge rounded-pill bg-danger float-end">6</span>
                        <span>Forms</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo/form-elements') }}">Form Elements</a></li>
                        <li><a href="{{ url('demo/form-validation') }}">Form Validation</a></li>
                        <li><a href="{{ url('demo/form-advanced') }}">Form Advanced</a></li>
                        <li><a href="{{ url('demo/form-editors') }}">Form Editors</a></li>
                        <li><a href="{{ url('demo/form-uploads') }}">Form File Upload</a></li>
                        <li><a href="{{ url('demo/form-xeditable') }}">Form Xeditable</a></li>
                        <li><a href="{{ url('demo/form-repeater') }}">Form Repeater</a></li>
                        <li><a href="{{ url('demo/form-wizard') }}">Form Wizard</a></li>
                        <li><a href="{{ url('demo/form-mask') }}">Form Mask</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-clipboard-list-outline"></i>
                        <span>Tables</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo/tables-basic') }}">Basic Tables</a></li>
                        <li><a href="{{ url('demo/tables-datatable') }}">Data Tables</a></li>
                        <li><a href="{{ url('demo/tables-responsive') }}">Responsive Table</a></li>
                        <li><a href="{{ url('demo/tables-editable') }}">Editable Table</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-chart-donut"></i>
                        <span>Charts</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo/charts-apex') }}">Apex charts</a></li>
                        <li><a href="{{ url('demo/charts-chartjs') }}">Chartjs Chart</a></li>
                        <li><a href="{{ url('demo/charts-flot') }}">Flot Chart</a></li>
                        <li><a href="{{ url('demo/charts-knob') }}">Jquery Knob Chart</a></li>
                        <li><a href="{{ url('demo/charts-sparkline') }}">Sparkline Chart</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-emoticon-happy-outline"></i>
                        <span>Icons</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo/icons-boxicons') }}">Boxicons</a></li>
                        <li><a href="{{ url('demo/icons-materialdesign') }}">Material Design</a></li>
                        <li><a href="{{ url('demo/icons-dripicons') }}">Dripicons</a></li>
                        <li><a href="{{ url('demo/icons-fontawesome') }}">Font awesome</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-map-marker-outline"></i>
                        <span>Maps</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ url('demo/maps-google') }}">Google Maps</a></li>
                        <li><a href="{{ url('demo/maps-vector') }}">Vector Maps</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="mdi mdi-file-tree"></i>
                        <span>Multi Level</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="true">
                        <li><a href="javascript: void(0);">Level 1.1</a></li>
                        <li><a href="javascript: void(0);" class="has-arrow">Level 1.2</a>
                            <ul class="sub-menu" aria-expanded="true">
                                <li><a href="javascript: void(0);">Level 2.1</a></li>
                                <li><a href="javascript: void(0);">Level 2.2</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->