<?php

use App\Presentation\Http\Requests\UserRequest;

test('store rules configure employeeId as nullable and not required', function () {
    $rules = UserRequest::storeRules();

    expect($rules)->toHaveKey('employeeId');
    expect($rules['employeeId'])->toContain('nullable');
    expect($rules['employeeId'])->toContain('integer');
    expect($rules['employeeId'])->toContain('exists:employees,id');
    expect($rules['employeeId'])->not->toContain('required');
});

test('update rules also have employeeId as nullable', function () {
    $rules = UserRequest::updateRules(1);

    expect($rules)->toHaveKey('employeeId');
    expect($rules['employeeId'])->toContain('nullable');
    expect($rules['employeeId'])->not->toContain('required');
});

test('messages array contains expected custom messages', function () {
    $messages = UserRequest::messagesArray();

    expect($messages)->toHaveKey('employeeId.required');
    expect($messages)->toHaveKey('employeeId.exists');
});
