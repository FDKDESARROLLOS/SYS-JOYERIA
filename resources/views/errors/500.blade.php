@extends('layouts.error', [
    'code' => '500',
    'title' => 'Ocurrió un problema interno',
    'message' => 'No fue posible completar la solicitud. Inténtalo nuevamente más tarde.',
    'actions' => [
        ['label' => 'Volver al dashboard', 'url' => url('/dashboard')],
        ['label' => 'Intentar nuevamente', 'url' => url()->current()],
    ],
])
