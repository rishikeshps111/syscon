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
        $driverDocumentExpiryAlerts = \App\Models\DriverLicenseExpiryAlert::where('user_id', auth()->id())
            ->whereNotNull('driver_profile_id')
            ->where('notified_at', '>=', now()->subDays(3))
            ->with('driverProfile.user')
            ->oldest('notified_at')
            ->get();
        $driverDocumentExpiryAlertPayloads = $driverDocumentExpiryAlerts->map(fn ($alert) => [
            'id' => $alert->id,
            'driver_name' => $alert->driverProfile?->user?->name,
            'document_type' => $alert->document_type,
            'title' => $alert->document_type === 'badge' ? 'Badge Going to Expire' : 'License Going to Expire',
            'expiry_date' => $alert->expiry_date?->format('d M Y'),
            'message' => ($alert->driverProfile?->user?->name ?: 'Driver') . "'s " . ($alert->document_type === 'badge' ? 'badge' : 'license') . ' expires on ' . ($alert->expiry_date?->format('d M Y') ?: '-') . '. Please renew and update the system.',
            'url' => $alert->driverProfile?->user_id
                ? route('driver-management.edit', $alert->driverProfile->user_id)
                : route('driver-management.index'),
            'notified_at' => $alert->notified_at?->toIso8601String(),
        ])->values();
    @endphp
    <script>
        $(function () {
            var driverManagementUrl = @json(route('driver-management.index'));
            var initialDriverDocumentAlerts = @json($driverDocumentExpiryAlertPayloads);
            var driverDocumentAlertQueue = [];
            var driverDocumentAlertShowing = false;
            var chromeNotificationButton = null;

            function renderChromeNotificationButton() {
                if (!('Notification' in window) || Notification.permission === 'granted') {
                    if (chromeNotificationButton) {
                        chromeNotificationButton.remove();
                        chromeNotificationButton = null;
                    }
                    return;
                }

                if (!chromeNotificationButton) {
                    chromeNotificationButton = $('<button>', {
                        type: 'button',
                        class: 'btn btn-warning shadow',
                        css: {
                            position: 'fixed',
                            right: '20px',
                            bottom: '20px',
                            zIndex: 99999
                        }
                    }).appendTo(document.body);
                }

                if (Notification.permission === 'denied') {
                    chromeNotificationButton
                        .text('Chrome notifications blocked — enable them in Site settings')
                        .prop('disabled', true);
                    return;
                }

                chromeNotificationButton
                    .text('Enable Chrome Notifications')
                    .prop('disabled', false)
                    .off('click')
                    .on('click', requestChromeNotificationPermission);
            }

            function requestChromeNotificationPermission() {
                if (!('Notification' in window) || Notification.permission !== 'default') {
                    return;
                }

                Notification.requestPermission().then(function (permission) {
                    renderChromeNotificationButton();

                    if (permission === 'granted') {
                        showNextDriverDocumentAlert();
                    }
                }).catch(function () {});
            }

            function showChromeNotification(data, url, message, onFinished) {
                if (!('Notification' in window) || Notification.permission !== 'granted') {
                    return false;
                }

                try {
                    var notification = new Notification(
                        data.title || (data.document_type === 'badge' ? 'Badge Going to Expire' : 'License Going to Expire'),
                        {
                            body: message,
                            icon: @json(asset('favicon.png')),
                            tag: 'driver-document-expiry-' + (data.id || Date.now()),
                            requireInteraction: true
                        }
                    );
                } catch (error) {
                    console.error('Unable to display Chrome notification:', error);
                    return false;
                }
                var finished = false;

                function finish() {
                    if (finished) {
                        return;
                    }

                    finished = true;
                    onFinished();
                }

                notification.onclick = function () {
                    window.focus();
                    window.location.href = url;
                    notification.close();
                };
                notification.onclose = finish;
                notification.onerror = finish;

                setTimeout(function () {
                    notification.close();
                    finish();
                }, 12000);

                return true;
            }

            renderChromeNotificationButton();

            function alertStorageKey(data) {
                return 'driver_document_expiry_alert_' + (data.id || data.notified_at || 'latest');
            }

            function queueDriverDocumentNotification(data) {
                driverDocumentAlertQueue.push(data);

                if (driverDocumentAlertShowing) {
                    return;
                }

                showNextDriverDocumentAlert();
            }

            function showNextDriverDocumentAlert() {
                if (!driverDocumentAlertQueue.length) {
                    driverDocumentAlertShowing = false;
                    return;
                }

                if (!('Notification' in window) || Notification.permission !== 'granted') {
                    driverDocumentAlertShowing = false;
                    return;
                }

                driverDocumentAlertShowing = true;
                var data = driverDocumentAlertQueue.shift();
                var url = data.url || driverManagementUrl;
                var documentName = data.document_type === 'badge' ? 'badge' : 'license';
                var message = data.message || ('Driver ' + documentName + ' is going to expire. Please renew and update the system.');
                var displayed = showChromeNotification(data, url, message, function () {
                    driverDocumentAlertShowing = false;
                    showNextDriverDocumentAlert();
                });

                if (displayed) {
                    localStorage.setItem(alertStorageKey(data), 'shown');
                } else {
                    driverDocumentAlertQueue.unshift(data);
                    driverDocumentAlertShowing = false;
                }
            }

            initialDriverDocumentAlerts.forEach(function (alert) {
                var key = alertStorageKey(alert);

                if (localStorage.getItem(key) !== 'shown') {
                    queueDriverDocumentNotification(alert);
                }
            });

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
                    queueDriverDocumentNotification(data || {});
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
