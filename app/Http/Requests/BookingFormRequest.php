<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'booking_start' => [
                'required',
                'date',
                'after_or_equal:' . now()->toDateTimeString(),
            ],
            'booking_end' => [
                'required',
                'date',
                'after:booking_start',
            ],
            'booking_start' => 'required|date',
            'booking_end' => 'required|date|after:booking_start',
            'purpose' => 'required|string|max:255',
            'duration' => 'required|string',
            'participants' => 'required|integer|min:1',
            'equipment' => 'array',
            'equipment.*.item' => 'required|string',
            'equipment.*.quantity' => 'required|integer|min:1',
            'adviser_email' => 'required|email|max:255',
            'dean_email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:255',
        ];
    }
    public function messages()
    {
        return [
            'booking_start.after_or_equal' => 'The booking start date must be today or a future date.',
            'booking_end.after' => 'The booking end date must be after the start date.',
        ];
    }
}
