<?php

use App\Application\User\DTOs\UserData;
use App\Application\User\UseCases\CreateUser;
use App\Domain\Employee\Entities\Employee;
use App\Domain\Employee\Repositories\EmployeeRepository;
use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\Email;

afterEach(function () {
    Mockery::close();
});

test('it creates a user without an employee successfully', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $userRepo->shouldReceive('findByEmail')
        ->once()
        ->with('john@example.com')
        ->andReturnNull();

    // Employee repository should not even be queried when employeeId is null
    $employeeRepo->shouldNotReceive('findById');
    $userRepo->shouldNotReceive('findByEmployeeId');

    $createdUser = new User(
        id: 10,
        name: 'John Doe',
        email: new Email('john@example.com'),
        active: true,
        roles: ['staff'],
        employeeId: null,
    );

    $userRepo->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (User $user) {
            return $user->name() === 'John Doe' &&
                (string) $user->email() === 'john@example.com' &&
                $user->employeeId() === null &&
                $user->isActive() === true;
        }), 'secretPassword123')
        ->andReturn($createdUser);

    $userRepo->shouldReceive('setRoles')
        ->once()
        ->with($createdUser, ['staff']);

    $useCase = new CreateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'secretPassword123',
        roles: ['staff'],
        active: true,
        employeeId: null,
    );

    $result = $useCase->execute($dto);

    expect($result->id())->toBe(10);
    expect($result->name())->toBe('John Doe');
    expect((string) $result->email())->toBe('john@example.com');
    expect($result->employeeId())->toBeNull();
});

test('it creates a user with a linked employee successfully', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $userRepo->shouldReceive('findByEmail')
        ->once()
        ->with('jane@example.com')
        ->andReturnNull();

    $employee = new Employee(
        id: 42,
        nik: '12345',
        name: 'Jane Smith',
    );

    $employeeRepo->shouldReceive('findById')
        ->once()
        ->with(42)
        ->andReturn($employee);

    $userRepo->shouldReceive('findByEmployeeId')
        ->once()
        ->with(42)
        ->andReturnNull();

    $createdUser = new User(
        id: 11,
        name: 'Jane Smith',
        email: new Email('jane@example.com'),
        active: true,
        roles: ['staff'],
        employeeId: 42,
    );

    $userRepo->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (User $user) {
            return $user->name() === 'Jane Smith' &&
                (string) $user->email() === 'jane@example.com' &&
                $user->employeeId() === 42 &&
                $user->isActive() === true;
        }), 'secretPassword123')
        ->andReturn($createdUser);

    $userRepo->shouldReceive('setRoles')
        ->once()
        ->with($createdUser, ['staff']);

    $useCase = new CreateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'Jane Smith',
        email: 'jane@example.com',
        password: 'secretPassword123',
        roles: ['staff'],
        active: true,
        employeeId: 42,
    );

    $result = $useCase->execute($dto);

    expect($result->id())->toBe(11);
    expect($result->employeeId())->toBe(42);
});

test('it throws domain exception when email is already in use', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $existingUser = new User(
        id: 1,
        name: 'Existing',
        email: new Email('taken@example.com'),
    );

    $userRepo->shouldReceive('findByEmail')
        ->once()
        ->with('taken@example.com')
        ->andReturn($existingUser);

    $useCase = new CreateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'Test',
        email: 'taken@example.com',
        password: 'secretPassword123',
        roles: [],
        active: true,
        employeeId: null,
    );

    expect(fn () => $useCase->execute($dto))
        ->toThrow(DomainException::class, 'Email already in use');
});

test('it throws domain exception when employee is not found', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $userRepo->shouldReceive('findByEmail')
        ->once()
        ->with('john@example.com')
        ->andReturnNull();

    $employeeRepo->shouldReceive('findById')
        ->once()
        ->with(999)
        ->andReturnNull();

    $useCase = new CreateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'secretPassword123',
        roles: [],
        active: true,
        employeeId: 999,
    );

    expect(fn () => $useCase->execute($dto))
        ->toThrow(DomainException::class, 'Employee not found');
});

test('it throws domain exception when employee already has a user account', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $userRepo->shouldReceive('findByEmail')
        ->once()
        ->with('john@example.com')
        ->andReturnNull();

    $employee = new Employee(
        id: 42,
        nik: '12345',
        name: 'Jane Smith',
    );

    $employeeRepo->shouldReceive('findById')
        ->once()
        ->with(42)
        ->andReturn($employee);

    $existingUser = new User(
        id: 5,
        name: 'Jane Account',
        email: new Email('existing@example.com'),
        employeeId: 42,
    );

    $userRepo->shouldReceive('findByEmployeeId')
        ->once()
        ->with(42)
        ->andReturn($existingUser);

    $useCase = new CreateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'secretPassword123',
        roles: [],
        active: true,
        employeeId: 42,
    );

    expect(fn () => $useCase->execute($dto))
        ->toThrow(DomainException::class, 'This employee already has a user account.');
});

test('it throws domain exception when password is empty or null', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $userRepo->shouldReceive('findByEmail')
        ->once()
        ->with('john@example.com')
        ->andReturnNull();

    $useCase = new CreateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'John Doe',
        email: 'john@example.com',
        password: '',
        roles: [],
        active: true,
        employeeId: null,
    );

    expect(fn () => $useCase->execute($dto))
        ->toThrow(DomainException::class, 'Password is required when creating a user.');
});
