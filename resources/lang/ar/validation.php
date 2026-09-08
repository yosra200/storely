<?php

return [

    // 🔹 General Validation Messages
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عندما تكون :other :value.',
    'before_required_when_in_progress' => 'صور قبل البدء مطلوبة عندما تكون الحالة قيد التنفيذ.',
    'email'    => 'يجب أن يكون :attribute بريد إلكتروني صحيح.',
    'unique'   => ':attribute مستخدم من قبل.',
    'exists'   => ':attribute غير موجود.',
    'image'    => 'يجب أن يكون :attribute صورة.',
    'mimes'    => 'يجب أن يكون :attribute من نوع: :values.',

    'regex'    => 'صيغة :attribute غير صحيحة.',
    'string'   => 'يجب أن يكون :attribute نصًا.',
    'lowercase' => 'يجب أن يكون :attribute بأحرف صغيرة.',
    'in'       => 'القيمة المحددة لـ :attribute غير صحيحة.',
    'different' => 'يجب أن يختلف :attribute عن :other.',
    'required_without' => 'حقل :attribute مطلوب عند عدم إدخال :values.',
    'prohibited_with' => 'لا يمكن إدخال :attribute مع :values.',
    'between' => [
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
    ],
    'mixed_case' => 'يجب أن يحتوي :attribute على أحرف كبيرة وصغيرة.',
    'numbers' => 'يجب أن يحتوي :attribute على رقم واحد على الأقل.',
    'symbols' => 'يجب أن يحتوي :attribute على رمز واحد على الأقل.',


    'max' => [
        'numeric' => 'يجب ألا يزيد :attribute عن :max.',
        'file'    => 'يجب ألا يتجاوز حجم الملف :max كيلوبايت.',
        'string'  => 'يجب ألا يتجاوز :attribute :max حرف.',
    ],

    'min' => [
        'numeric' => 'يجب ألا يقل :attribute عن :min.',
        'string'  => 'يجب ألا يقل :attribute عن :min أحرف.',
    ],

    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'digits'    => 'يجب أن يكون :attribute مكون من :digits أرقام.',
    'numeric'   => 'يجب أن يكون :attribute رقم.',

    'phone.regex' => 'رقم الجوال يجب أن يكون رقم سعودي صحيح (يبدأ بـ 05 أو 5 ويتكون من 9 أرقام).',

    'attributes' => [
        'installment_provider' => 'مزود التقسيط',
        'payment_type' => 'نوع الدفع',
        'rate' => 'التقييم',
        'comment' => 'التعليق',
        'elevators' => 'المصاعد',
        'elevators.*.city' => 'فرع المصعد',
        'elevators.*.elevator_type' => 'نوع المصعد',
        'elevators.*.location' => 'موقع المصعد',
        'elevators.*.official_number' => 'رقم المسؤول',
        'elevators.*.address' => 'عنوان المصعد',
        'elevators.*.start_date' => 'تاريخ البدء',
        'elevators.*.end_date' => 'تاريخ الانتهاء',
        'elevators.*.payment_plan' => 'التقسيط',
        'order_id'     => 'رقم الطلب',
        'status'       => 'الحالة',
        'reason'       => 'السبب',
        'image_before' => 'صورة قبل البدء',
        'image_after'  => 'صورة بعد الانتهاء',
        'before'       => 'صور قبل البدء',
        'after'        => 'صور بعد الانتهاء',
        'email'        => 'البريد الإلكتروني',
        'password'     => 'كلمة المرور',
        'name'         => 'الاسم',
        'phone'        => 'رقم الجوال',
        'city'         => 'المدينة',
        'address'      => 'العنوان',
        'lat'          => 'خط العرض',
        'long'         => 'خط الطول',
        'code'         => 'كود التحقق',
        'official_number' => 'رقم المسؤول',
        'secondary_phone' => 'رقم هاتف إضافي',
        'is_previous_client' => 'عميل سابق',
        'location' => 'الموقع',
        'elevators_count' => 'عدد المصاعد',
        'elevator_type' => 'نوع المصعد',
        'commercial_register' => 'السجل التجاري',
        'tax_card' => 'الرقم الضريبي',
        'changed_phone' => 'رقم الهاتف الجديد',
        'system_type' => 'نوع النظام',
        'role' => 'الصلاحية',
        'otp' => 'رمز التحقق',
        'image' => 'الصورة',
        'current_password' => 'كلمة المرور الحالية',
        'new_password' => 'كلمة المرور الجديدة',
    ],

    'values' => [
        'status' => [
            'in_progress' => 'قيد التنفيذ',
            'complete' => 'مكتملة',
            'not_complete' => 'غير مكتملة',
            'rejected' => 'مرفوضة',
        ],
    ],

];
