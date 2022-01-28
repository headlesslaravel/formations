<?php

namespace HeadlessLaravel\Formations\Rules;

use HeadlessLaravel\Formations\Field;
use Illuminate\Contracts\Validation\Rule;

class ValidColumns implements Rule
{
    /** @var Field[] */
    public $fields = [];

    /**
     * Create a new rule instance.
     *
     * @param array $fields
     *
     * @return void
     */
    public function __construct($fields = [])
    {
        $this->fields = $fields;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $columns = explode(',', $value);
        $fieldNames = collect($this->fields)->pluck('key')->toArray();
        foreach ($columns as $column) {
            if (!in_array($column, $fieldNames)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid Column Name Passed';
    }
}
