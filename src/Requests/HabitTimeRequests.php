<?php

namespace NickKlein\Habits\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HabitTimeRequests extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'habit_id' => 'required|integer',
            'habit_type' => 'required|in:time,unit,ml',
        ];

        if ($this->habit_type === 'time') {
            $rules += [
                'start_date' => 'required|date_format:Y-m-d',
                'start_time' => 'required',
                'end_date' => 'required|date_format:Y-m-d',
                'end_time' => 'required',
            ];

            return $rules;
        }

        $rules += [
            'value' => 'required|integer',
        ];

        return $rules;
    }
}
