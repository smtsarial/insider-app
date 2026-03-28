<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GenerateLeagueFixturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * When `teams` is sent (clubs step), clubs are updated before fixtures are generated.
     * When omitted (e.g. redraw on the fixtures step), existing DB rows are used.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teams' => ['sometimes', 'array', 'size:4'],
            'teams.*.id' => ['required_with:teams', 'integer', 'exists:teams,id'],
            'teams.*.name' => ['required_with:teams', 'string', 'max:255'],
            'teams.*.short_name' => ['required_with:teams', 'string', 'max:8'],
            'teams.*.power' => ['required_with:teams', 'integer', 'min:40', 'max:99'],
            'teams.*.sort_order' => ['required_with:teams', 'integer', 'min:1', 'max:4'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->failed() || ! is_array($this->input('teams'))) {
                return;
            }

            /** @var list<array{id: int, sort_order: int}> $teams */
            $teams = $this->input('teams', []);
            $submittedIds = collect($teams)->pluck('id')->sort()->values()->all();
            $existingIds = Team::query()->orderBy('id')->pluck('id')->sort()->values()->all();

            if ($submittedIds !== $existingIds) {
                $validator->errors()->add('teams', __('The submitted clubs must include exactly the registered teams.'));
            }

            $orders = collect($teams)->pluck('sort_order');
            if ($orders->unique()->count() !== 4) {
                $validator->errors()->add('teams', __('Each draw position (1–4) must be used once.'));
            }
        });
    }
}
