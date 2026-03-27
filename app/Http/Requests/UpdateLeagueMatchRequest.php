<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeagueMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|\Illuminate\Validation\Rules\In>>
     */
    public function rules(): array
    {
        return [
            'home_goals' => ['required', 'integer', 'min:0', 'max:30'],
            'away_goals' => ['required', 'integer', 'min:0', 'max:30'],
        ];
    }
}
