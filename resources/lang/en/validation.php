<?php

return [
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'before_required_when_in_progress' => 'The before files are required when the status is in progress.',
    'email'    => 'The :attribute must be a valid email address.',
    'unique'   => 'The :attribute has already been taken.',
    'exists'   => 'The selected :attribute is invalid.',
    'image'    => 'The :attribute must be an image.',
    'mimes'    => 'The :attribute must be a file of type: :values.',
    'max'      => [
        'numeric' => 'The :attribute must not be greater than :max.',
        'file'    => 'The :attribute must not be greater than :max kilobytes.',
        'string'  => 'The :attribute must not be greater than :max characters.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'installment_provider' => 'installment provider',
        'payment_type' => 'payment type',
        'rate' => 'rate',
        'comment' => 'comment',
        'elevators' => 'elevators',
        'elevators.*.city' => 'elevator branch',
        'elevators.*.elevator_type' => 'elevator type',
        'elevators.*.location' => 'elevator location',
        'elevators.*.official_number' => 'official number',
        'elevators.*.address' => 'elevator address',
        'elevators.*.start_date' => 'start date',
        'elevators.*.end_date' => 'end date',
        'elevators.*.payment_plan' => 'payment plan',
        'order_id'     => 'order ID',
        'status'       => 'status',
        'reason'       => 'reason',
        'image_before' => 'image before',
        'image_after'  => 'image after',
        'before'       => 'before files',
        'after'        => 'after files',
        'email'        => 'email address',
        'password'     => 'password',
        'name'         => 'name',
        'phone'        => 'phone number',
        'secondary_phone' => 'secondary phone',
        'city'         => 'city',
        'address'      => 'address',
        'is_previous_client' => 'previous client',
        'location' => 'location',
        'elevators_count' => 'elevators count',
        'elevator_type' => 'elevator type',
        'commercial_register' => 'commercial register',
        'tax_card' => 'tax card',
        'report'       => 'report',
        'official_number' => 'official number',
        'changed_phone' => 'changed phone',


    ],

    'values' => [
        'status' => [
            'in_progress' => 'in progress',
            'complete' => 'complete',
            'not_complete' => 'not complete',
            'rejected' => 'rejected',
        ],
    ],
];
