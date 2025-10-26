<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        {{-- <a class="sidebar-brand" href="{{ route('home') }}">
            <span class="align-middle">Harvest Intel</span>
        </a> --}}

        <ul class="sidebar-nav">
            <li class="sidebar-header">
                Pages
            </li>

            <li class="sidebar-item {{ request()->routeIs('home') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('home') }}">
                    <i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
                </a>
            </li>

            <li class="sidebar-item {{ request()->routeIs('profile') ? 'active' : '' }} ">
                <a class="sidebar-link" href="{{ route('profile') }}">
                    <i class="align-middle" data-feather="user"></i> <span class="align-middle">Profile</span>
                </a>
            </li>

            <li class="sidebar-item {{ request()->routeIs('operations.index') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('operations.index') }}">
                    <i class="align-middle me-2" data-feather="settings"></i></i> <span
                        class="align-middle">Operations</span>
                </a>
            </li>
            {{-- <li class="sidebar-item {{ request()->routeIs('costCategory') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('costCategory') }}">
                    <i class="align-middle me-2" data-feather="settings"></i></i> <span class="align-middle">Cost
                        category</span>
                </a>
            </li> --}}
            <li class="sidebar-item {{ request()->routeIs('getCosts') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('getCosts') }}">
                    <i class="align-middle me-2" data-feather="settings"></i></i> <span class="align-middle">
                        Cost</span>
                </a>
            </li>


            <li class="sidebar-item {{ request()->routeIs('operations.available') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('operations.available') }}">
                    <i class="align-middle" data-feather="user-plus"></i> <span class="align-middle">Available
                        Operations</span>
                </a>
            </li>


            {{-- <li class="sidebar-item {{ request()->routeIs('blank') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('blank') }}">
                    <i class="align-middle" data-feather="book"></i> <span class="align-middle">Blank</span>
                </a>
            </li> --}}

            <li class="sidebar-header">
                Training
            </li>

            <li class="sidebar-item {{ request()->routeIs('getOpeCost') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('getOpeCost') }}">
                    <i class="align-middle" data-feather="square"></i> <span class="align-middle">Check operation vice
                        costs</span>
                </a>
            </li>

            <li class="sidebar-item {{ request()->routeIs('costPredict') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('costPredict') }}">
                    <i class="align-middle" data-feather="check-square"></i> <span class="align-middle">Predict
                        Cost</span>
                </a>
            </li>

            <li class="sidebar-item {{ request()->routeIs('costAnalisis') ? 'active' : '' }}">
        <a class="sidebar-link" href="{{ route('costAnalisis') }}">
          <i class="align-middle" data-feather="grid"></i> <span class="align-middle">Cost Anaylysis</span>
        </a>
      </li>

      <li class="sidebar-item {{ request()->routeIs('categoryTrain') ? 'active' : '' }}">
        <a class="sidebar-link" href="{{ route('categoryTrain') }}">
          <i class="align-middle" data-feather="align-left"></i> <span class="align-middle">Train Category </span>
        </a>
      </li>

      {{-- <li class="sidebar-item {{ request()->routeIs('ui.icons') ? 'active' : '' }}">
        <a class="sidebar-link" href="{{ route('ui.icons') }}">
          <i class="align-middle" data-feather="coffee"></i> <span class="align-middle">Icons</span>
        </a>
      </li>

      <li class="sidebar-header">
        Plugins & Addons
      </li>

      <li class="sidebar-item {{ request()->routeIs('charts') ? 'active' : '' }}">
        <a class="sidebar-link" href="{{ route('charts') }}">
          <i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Charts</span>
        </a>
      </li>

      <li class="sidebar-item {{ request()->routeIs('maps') ? 'active' : '' }}">
        <a class="sidebar-link" href="{{ route('maps') }}">
          <i class="align-middle" data-feather="map"></i> <span class="align-middle">Maps</span>
        </a>
      </li> --}}


        </ul>
    </div>
</nav>
