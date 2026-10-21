@extends('layouts.error', [
    'code' => '404',
    'title' => 'Página no encontrada',
    'message' => 'La página que buscas no existe o fue movida.',
    'actions' => [
        ['label' => 'Volver al dashboard', 'url' => url('/dashboard')],
        ['label' => 'Ir al inicio', 'url' => url('/')],
    ],
])
