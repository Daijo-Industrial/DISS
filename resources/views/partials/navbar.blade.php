<nav class="navbar px-4 py-3 shadow-sm" style="background: linear-gradient(90deg,#14385e 0%, #1e4e84 100%);">
    <h4 class="mb-0 text-white">Daijo Industrial Support System</h4>
    <div class="d-flex align-items-center gap-3">
        <div>
            <livewire:notifications.menu />
        </div>

        {{-- Profile --}}
        <div class="dropdown">
            <button class="btn text-white d-flex align-items-center gap-2" id="profileDropdown" data-bs-toggle="dropdown"
                aria-expanded="false" aria-label="Profile menu">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}" class="rounded-circle"
                    alt="Profile picture" width="40" height="40">
                <span class="fw-bold text-capitalize d-none d-md-inline">{{ Auth::user()->name }}</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end px-3 mb-2" aria-labelledby="profileDropdown">
                <div class="text-center pt-2">
                    <span class="fs-6 fw-bold text-capitalize">{{ Auth::user()->name }}</span><br>
                    <span
                        class="fw-medium text-secondary-emphasis">{{ optional(Auth::user()->department)->name }}</span>
                </div>
                <hr>
                <a class="dropdown-item" href="{{ route('account.settings', ['tab' => 'profile']) }}">
                    <i class='bi bi-person me-2'></i>{{ __('Profile & Account') }}
                </a>
                <a class="dropdown-item" href="{{ route('account.settings', ['tab' => 'security']) }}">
                    <i class='bi bi-key me-2'></i>{{ __('Change Password') }}
                </a>
                <a class="dropdown-item" href="{{ route('account.settings', ['tab' => 'notifications']) }}">
                    <i class='bi bi-bell me-2'></i>{{ __('Notification Settings') }}
                </a>
                <a href="{{ route('signatures.manage') }}" class="dropdown-item">
                    <i class='bi bi-pencil-square me-2'></i>{{ __('My Signatures') }}
                </a>
                @if (Auth::user()->hasRole('super-admin'))
                    <a class="dropdown-item text-primary" href="{{ route('account.settings', ['tab' => 'abilities']) }}">
                        <i class='bi bi-shield-check me-2'></i>{{ __('System Abilities (Admin)') }}
                    </a>
                @endif
                <hr>
                <a class="dropdown-item text-danger" href="#"
                    onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                    <i class='bi bi-door-closed me-2'></i>{{ __('Logout') }}
                </a>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>
        </div>
    </div>
</nav>
