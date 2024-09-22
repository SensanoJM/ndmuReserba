<div>
    <h2>{{ date('F Y', strtotime($year . '-' . $month . '-01')) }}</h2>

    <table>
        <thead>
            <tr>
                <th>Sun</th>
                <th>Mon</th>
                <th>Tue</th>
                <th>Wed</th>
                <th>Thu</th>
                <th>Fri</th>
                <th>Sat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($calendar as $week)
                <tr>
                    @foreach ($week as $day)
                        <td wire:click="getEventsForDate('{{ $day['date'] }}')">{{ $day['day'] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        @if (count($events) > 0)
            <h3>Events for {{ date('F j, Y', strtotime($selectedDate)) }}</h3>

            <ul>
                @foreach ($events as $event)
                    <li>{{ $event->title }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>