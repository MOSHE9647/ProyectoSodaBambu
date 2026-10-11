<?php

use App\Enums\UserRole;
use App\Models\User;

test('CP-FUN-04 - an administrator can switch between attendance tabs from the browser', function () {
    // Given: an administrator user exists in the database.
    $adminPassword = 'adminpassword';
    $adminUser = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'attendance-admin@example.com',
        'password' => $adminPassword,
    ]);

    // When: the administrator user logs in through the browser helper.
    $page = loginAsUser($adminUser, $adminPassword);

    // And: the administrator opens the attendance module.
    $page->navigate(route('attendance.index'));

    // Then: the main attendance view loads successfully.
    $page->assertSee('Registro de Asistencia')
        ->assertSee('Registrar Horas')
        ->wait(5);

    // And: the history tab can be opened and rendered without manual refresh.
    $page->click('#nav-history-tab')
        ->wait(5)
        ->assertSee('Historial de Asistencia')
        ->assertSee('Colaborador');

    // And: the payroll tab can be opened and rendered without manual refresh.
    $page->click('#nav-salary-tab')
        ->wait(5)
        ->assertSee('Calcular Salario por Colaborador')
        ->assertSee('Selecciona un colaborador y presiona "Calcular Salario" para ver el desglose.');

    // And: the history tab remains available after alternating between tabs.
    $page->click('#nav-history-tab')
        ->wait(5)
        ->assertSee('Historial de Asistencia')
        ->assertSee('Colaborador');
})->group('s8-tests');
