<?php

namespace HeadlessLaravel\Formations\Rules;

use HeadlessLaravel\Formations\Field;
use Illuminate\Contracts\Validation\Rule;

class ValidColumns implements Rule
{
    public $fields = [];

    public $invalid = [];

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
                $this->invalid[] = $column;
            }
        }

        return count($this->invalid) === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid columns: ' . implode(', ', $this->invalid);
    }
}
