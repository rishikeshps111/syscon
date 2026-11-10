<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RosterAssignmentConflict extends ValidationException
{
    public function __construct(public readonly array $conflicts, string $message)
    {
        $validator = Validator::make([], []);
        $validator->errors()->add('assignment', $message);
        parent::__construct($validator);
    }
}
