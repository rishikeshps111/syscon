@section('title')
    Holiday Calendar
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Holiday Calendar</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item"><a href="{{ route('holidays.index') }}">Holidays</a></li>
                    <li class="breadcrumb-item active">Calendar</li>
                </ol>
            </nav>
        </div>
    </section>

    <section class="section dashboard">
        <div class="holiday-calendar-wrapper">
            <div class="holiday-calendar-card">

                <div class="holiday-calendar-header">
                    <h4>Holiday Calendar</h4>
                    <div class="holiday-calendar-controls">
                        <button type="button" class="hc-btn" id="prevMonth">&lt;</button>
                        <span id="monthYear"></span>
                        <button type="button" class="hc-btn" id="nextMonth">&gt;</button>
                    </div>
                </div>

                <div class="holiday-calendar-grid" id="calendarGrid"></div>

                <div class="holiday-legend">
                    <span><span class="legend-dot holiday-dot"></span> Holiday</span>
                    <span><span class="legend-dot weekend-dot"></span> Weekend</span>
                </div>

            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var currentDate = new Date();
                var holidays = [];

                loadCalendar();

                $('#prevMonth').on('click', function () {
                    currentDate.setMonth(currentDate.getMonth() - 1);
                    loadCalendar();
                });

                $('#nextMonth').on('click', function () {
                    currentDate.setMonth(currentDate.getMonth() + 1);
                    loadCalendar();
                });

                function loadCalendar() {
                    $.get("{{ route('holidays.calendar') }}", {
                        year: currentDate.getFullYear(),
                        status: 1
                    }, function (response) {
                        holidays = response;
                        renderCalendar();
                    });
                }

                function renderCalendar() {
                    var year = currentDate.getFullYear();
                    var month = currentDate.getMonth();
                    var monthName = currentDate.toLocaleString('default', { month: 'long' });
                    var firstDay = new Date(year, month, 1).getDay();
                    var lastDate = new Date(year, month + 1, 0).getDate();
                    var today = new Date();
                    var html = '';

                    $('#monthYear').text(monthName + ' ' + year);

                    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(function (day) {
                        html += '<div class="hc-day hc-day-name">' + day + '</div>';
                    });

                    for (var blank = 0; blank < firstDay; blank++) {
                        html += '<div></div>';
                    }

                    for (var day = 1; day <= lastDate; day++) {
                        var date = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                        var dayHolidays = holidays.filter(function (holiday) {
                            return holiday.date === date;
                        });
                        var isWeekend = new Date(year, month, day).getDay() === 0 || new Date(year, month, day).getDay() === 6;
                        var isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === day;
                        var classes = ['hc-day'];

                        if (dayHolidays.length) {
                            classes.push('hc-holiday');
                        }

                        if (isWeekend) {
                            classes.push('hc-weekend');
                        }

                        if (isToday) {
                            classes.push('hc-today');
                        }

                        html += '<div class="' + classes.join(' ') + '" title="' + dayHolidays.map(function (holiday) {
                            return holiday.name;
                        }).join(', ') + '">';
                        html += '<strong>' + day + '</strong>';

                        dayHolidays.forEach(function (holiday) {
                            html += '<div class="hc-holiday-name">' + holiday.name + '</div>';
                        });

                        html += '</div>';
                    }

                    $('#calendarGrid').html(html);
                }
            });
        </script>
    @endsection
</x-app-layout>
