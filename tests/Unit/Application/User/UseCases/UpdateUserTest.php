<?php

use App\Application\User\DTOs\UserData;
use App\Application\User\UseCases\UpdateUser;
use App\Domain\Employee\Repositories\EmployeeRepository;
use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\Email;

afterEach(function () {
    Mockery::close();
});

test('it updates user email successfully when email is not taken', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $user = new User(
        id: 5,
        name: 'John Doe',
        email: new Email('old@example.com'),
        active: true,
        roles: ['staff'],
        employeeId: null,
    );

    $userRepo->shouldReceive('findById')
        ->once()
        ->with(5)
        ->andReturn($user);

    $userRepo->shouldReceive('findByEmail')
        ->once()
        ->with('new@example.com')
        ->andReturnNull();

    $userRepo->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function (User $updatedUser) {
            return (string) $updatedUser->email() === 'new@example.com' &&
                   $updatedUser->name() === 'John Updated';
        }))
        ->andReturn($user);

    $userRepo->shouldReceive('setRoles')
        ->once()
        ->with($user, ['staff']);

    $useCase = new UpdateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'John Updated',
        email: 'new@example.com',
        password: null,
        roles: ['staff'],
        active: true,
        employeeId: null,
    );

    $result = $useCase->execute(5, $dto);

    expect((string) $result->email())->toBe('new@example.com');
    expect($result->name())->toBe('John Updated');
});

test('it throws domain exception when email is already taken by another user', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $currentUser = new User(
        id: 5,
        name: 'John Doe',
        email: new Email('old@example.com'),
        active: true,
        roles: ['staff'],
        employeeId: null,
    );

    $otherUser = new User(
        id: 99,
        name: 'Other User',
        email: new Email('taken@example.com'),
        active: true,
        roles: ['staff'],
        employeeId: null,
    );

    $userRepo->shouldReceive('findById')
        ->once()
        ->with(5)
        ->andReturn($currentUser);

    $userRepo->shouldReceive('findByEmail')
        ->once()
        ->with('taken@example.com')
        ->andReturn($otherUser);

    $useCase = new UpdateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'John Doe',
        email: 'taken@example.com',
        password: null,
        roles: ['staff'],
        active: true,
        employeeId: null,
    );

    expect(fn () => $useCase->execute(5, $dto))
        ->toThrow(DomainException::class, 'Email already in use');
});

test('it updates password when new password is provided', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $user = new User(
        id: 5,
        name: 'John Doe',
        email: new Email('same@example.com'),
        active: true,
        roles: ['staff'],
        employeeId: null,
    );

    $userRepo->shouldReceive('findById')
        ->once()
        ->with(5)
        ->andReturn($user);

    $userRepo->shouldReceive('changeUserPassword')
        ->once()
        ->with(5, 'newSecretPassword123');

    $userRepo->shouldReceive('update')
        ->once()
        ->andReturn($user);

    $userRepo->shouldReceive('setRoles')
        ->once()
        ->with($user, ['staff']);

    $useCase = new UpdateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'John Doe',
        email: 'same@example.com',
        password: 'newSecretPassword123',
        roles: ['staff'],
        active: true,
        employeeId: null,
    );

    $useCase->execute(5, $dto);
});

test('it unlinks employee when employeeId is set to null', function () {
    $userRepo = Mockery::mock(UserRepository::class);
    $employeeRepo = Mockery::mock(EmployeeRepository::class);

    $user = new User(
        id: 5,
        name: 'John Doe',
        email: new Email('same@example.com'),
        active: true,
        roles: ['staff'],
        employeeId: 42,
    );

    $userRepo->shouldReceive('findById')
        ->once()
        ->with(5)
        ->andReturn($user);

    $userRepo->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function (User $updatedUser) {
            return $updatedUser->employeeId() === null;
        }))
        ->andReturn($user);

    $userRepo->shouldReceive('setRoles')
        ->once()
        ->with($user, ['staff']);

    $useCase = new UpdateUser($userRepo, $employeeRepo);

    $dto = new UserData(
        name: 'John Doe',
        email: 'same@example.com',
        password: null,
        roles: ['staff'],
        active: true,
        employeeId: null,
    );

    $result = $useCase->execute(5, $dto);

    expect($result->employeeId())->toBeNull();
});
