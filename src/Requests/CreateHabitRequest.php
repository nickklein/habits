<?php

namespace NickKlein\Habits\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateHabitRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'color_index' => 'required|string|max:10',
            'streak_goal' => 'required|integer|min:1',
            'streak_time_type' => 'required|in:daily,weekly',
            'habit_type' => 'required|in:time,ml,unit',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Habit name is required',
            'name.string' => 'Habit name must be a string',
            'name.max' => 'Habit name cannot exceed 255 characters',
            'color_index.required' => 'Color selection is required',
            'streak_goal.required' => 'Streak goal is required',
            'streak_goal.integer' => 'Streak goal must be a number',
            'streak_goal.min' => 'Streak goal must be at least 1',
            'streak_time_type.required' => 'Streak time type is required',
            'streak_time_type.in' => 'Streak time type must be daily or weekly',
            'habit_type.required' => 'Habit type is required',
            'habit_type.in' => 'Habit type must be time, ml, or unit',
        ];
    }
}