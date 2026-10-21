@extends('layouts.error', [
    'code' => '403',
    'title' => 'Acceso restringido',
    'message' => 'No tienes permisos para acceder a esta sección.',
    'actions' => [
        ['label' => 'Volver al dashboard', 'url' => url('/dashboard')],
    ],
])
