<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;

class BookingCalendar extends Component
{
    public $month;
    public $year;
    public $events = [];

    public function mount()
    {
        $this->month = date('m');
        $this->year = date('Y');
    }

    public function render()
    {
        $calendar = [];

        $weeksInMonth = Carbon::createFromDate($this->year, $this->month)->weeksInMonth;

        for ($week = 1; $week <= $weeksInMonth; $week++) {
            $days = [];

            for ($day = 1; $day <= 7; $day++) {
                $date = Carbon::createFromDate($this->year, $this->month, 1)->addWeeks($week - 1)->addDays($day - 1);

                $days[] = [
                    'day' => $date->format('j'),
                    'date' => $date->format('Y-m-d'),
                ];
            }

            $calendar[] = $days;
        }

        return view('livewire.booking-calendar', [
            'calendar' => $calendar,
            'selectedDate' => $this->selectedDate,
            'events' => $this->events,
        ]);
    }
}
