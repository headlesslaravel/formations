<?php

namespace HeadlessLaravel\Formations\Http\Requests;

use HeadlessLaravel\Formations\Manager;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return app(Manager::class)
            ->formation()
            ->rulesForUpdating();
    }
}
