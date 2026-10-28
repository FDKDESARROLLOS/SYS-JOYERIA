<?php

namespace App\Exceptions;

use RuntimeException;

class InventoryReservationException extends RuntimeException
{
    public static function insufficientStock(): self
    {
        return new self('No hay stock disponible suficiente para reservar la cantidad solicitada.');
    }

    public static function alreadyExists(): self
    {
        return new self('El artículo del pedido ya tiene una reserva registrada.');
    }

    public static function inactive(): self
    {
        return new self('La reserva no está activa y no puede consumirse.');
    }

    public static function variantUnavailable(): self
    {
        return new self('La variante no está disponible para reservarse.');
    }

    public static function inconsistent(): self
    {
        return new self('La reserva no coincide con el inventario relacionado.');
    }
}
