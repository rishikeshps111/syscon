<!-- Vendor JS Files -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js')}}"></script>
<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ asset('assets/vendor/chart.js/chart.umd.js')}}"></script>
<script src="{{ asset('assets/vendor/echarts/echarts.min.js')}}"></script>
<script src="{{ asset('assets/vendor/quill/quill.js')}}"></script>
<script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
<script src="{{ asset('assets/vendor/tinymce/tinymce.min.js')}}"></script>
<script src="{{ asset('assets/vendor/php-email-form/validate.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- Template Main JS File -->
<script src="{{ asset('assets/js/main.js')}}"></script>
<script src="{{ asset('assets/js/calendar.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js"></script>
<script src="https://kit.fontawesome.com/111740f521.js" crossorigin="anonymous"></script>
<script src="{{ asset('assets/js/common.js')}}"></script>
@if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Staff']) || auth()->user()->can('driver-management.view')) && filled(config('broadcasting.connections.pusher.key')))
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
@endif
<script>
    window.updateChatUnreadBadge = function (count) {
        count = Number(count || 0);
        $('#chatUnreadBadge, #chatSidebarBadge')
            .toggleClass('d-none', count <= 0)
            .text(count);
    };

    window.playChatBeep = function () {
        try {
            var soundUrl = @json(asset('assets/audio/chat-notification.mp3'));

            if (!window.chatNotificationAudio) {
                window.chatNotificationAudio = new Audio(soundUrl);
                window.chatNotificationAudio.preload = 'auto';
            }

            window.chatNotificationAudio.currentTime = 0;
            window.chatNotificationAudio.play().catch(function () {
                window.playFallbackChatBeep();
            });
        } catch (error) {
            window.playFallbackChatBeep();
        }
    };

    window.playFallbackChatBeep = function () {
        try {
            var AudioContext = window.AudioContext || window.webkitAudioContext;

            if (!AudioContext) {
                return;
            }

            var context = new AudioContext();
            var oscillator = context.createOscillator();
            var gain = context.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, context.currentTime);
            gain.gain.setValueAtTime(0.001, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.18, context.currentTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.18);

            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.start(context.currentTime);
            oscillator.stop(context.currentTime + 0.2);
        } catch (error) {
            // Browsers may block audio until the user interacts with the page.
        }
    };
</script>
@if(auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Staff']) && filled(config('broadcasting.connections.pusher.key')))
    <script>
        $(function () {
            var currentChatUserId = {{ auth()->id() }};
            var globalPusher = new Pusher(@json(config('broadcasting.connections.pusher.key')), {
                cluster: @json(config('broadcasting.connections.pusher.options.cluster') ?: 'mt1'),
                channelAuthorization: {
                    endpoint: '/broadcasting/auth',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }
            });
            var globalChannelName = @json(auth()->user()->hasRole('Super Admin') ? 'private-chat.admin' : 'private-chat.user.' . auth()->id());
            var globalChannel = globalPusher.subscribe(globalChannelName);

            function refreshGlobalChatCount() {
                $.get(@json(route('chat.unread-count'))).done(function (response) {
                    window.updateChatUnreadBadge(response.count || 0);
                });
            }

            globalChannel.bind('message.sent', function (data) {
                if (Number(data.message?.sender_id) !== currentChatUserId) {
                    window.playChatBeep();
                }

                refreshGlobalChatCount();
            });
            globalChannel.bind('messages.seen', refreshGlobalChatCount);
        });
    </script>
@endif
@if(auth()->check() && auth()->user()->can('driver-management.view'))
    @php
        $driverLicenseExpiryAlert = \App\Models\DriverLicenseExpiryAlert::where('user_id', auth()->id())
            ->where('notified_at', '>=', now()->subDays(3))
            ->latest('notified_at')
            ->first();
        $driverLicenseExpiryAlertPayload = $driverLicenseExpiryAlert ? [
            'id' => $driverLicenseExpiryAlert->id,
            'expired_count' => $driverLicenseExpiryAlert->expired_count,
            'message' => $driverLicenseExpiryAlert->expired_count . ' driver license' . ($driverLicenseExpiryAlert->expired_count === 1 ? ' has' : 's have') . ' expired.',
            'url' => route('driver-management.index', ['expiry_filter' => 'license_expired']),
            'notified_at' => $driverLicenseExpiryAlert->notified_at?->toIso8601String(),
        ] : null;
    @endphp
    <script>
        $(function () {
            var expiredLicenseUrl = @json(route('driver-management.index', ['expiry_filter' => 'license_expired']));
            var initialLicenseAlert = @json($driverLicenseExpiryAlertPayload);

            function alertStorageKey(data) {
                return 'driver_license_expired_alert_' + (data.id || data.notified_at || 'latest');
            }

            function showDriverLicenseExpiredToast(data) {
                var count = Number(data.expired_count || 0);

                if (count <= 0) {
                    return;
                }

                var url = data.url || expiredLicenseUrl;
                var message = data.message || (count + ' driver licenses have expired.');

                Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: 'warning',
                    title: 'Expired Driver Licenses',
                    html: message,
                    showConfirmButton: true,
                    confirmButtonText: 'View Expired Licenses',
                    showCloseButton: true,
                    timer: 12000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'driver-license-expired-toast'
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            }

            if (initialLicenseAlert) {
                var key = alertStorageKey(initialLicenseAlert);

                if (localStorage.getItem(key) !== 'shown') {
                    showDriverLicenseExpiredToast(initialLicenseAlert);
                    localStorage.setItem(key, 'shown');
                }
            }

            @if(filled(config('broadcasting.connections.pusher.key')))
                var licensePusher = new Pusher(@json(config('broadcasting.connections.pusher.key')), {
                    cluster: @json(config('broadcasting.connections.pusher.options.cluster') ?: 'mt1'),
                    channelAuthorization: {
                        endpoint: '/broadcasting/auth',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                    }
                });
                var licenseChannel = licensePusher.subscribe(@json('private-license-alert.user.' . auth()->id()));

                licenseChannel.bind('driver-license.expired', function (data) {
                    showDriverLicenseExpiredToast(data || {});
                });
            @endif
        });
    </script>
@endif

@yield('scripts')

@if(session('success'))
    <script>
        showToast('success', '{{ session('success') }}');
    </script>
@endif

@if(session('error'))
    <script>
        showToast('error', '{{ session('error') }}');
    </script>
@endif

@if(session('warning'))
    <script>
        showToast('warning', '{{ session('warning') }}');
    </script>
@endif

@if ($errors->any())
    <script>
        showToast('error', @json($errors->first()));
    </script>
@endif
