@extends('layouts.error', [
    'code' => '419',
    'title' => 'Sesión expirada',
    'message' => 'Tu sesión ha expirado. Inicia sesión nuevamente para continuar.',
    'actions' => [
        ['label' => 'Volver al inicio de sesión', 'url' => url('/login')],
    ],
])
