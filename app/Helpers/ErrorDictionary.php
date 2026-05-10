<?php

namespace App\Helpers;

class ErrorDictionary {
    
    // Diccionario de códigos SQLSTATE a mensajes amigables
    private static array $sqlErrors = [
        '23505' => 'El registro ya existe y no puede duplicarse (violación de restricción única).',
        '23503' => 'Violación de llave foránea: El recurso hace referencia a un dato que no existe o no se puede eliminar porque está en uso.',
        '23502' => 'Falta un valor requerido que no puede ser nulo en la base de datos.',
        '08006' => 'Error de conexión con la base de datos.',
        '42P01' => 'Error interno: La tabla consultada no existe.',
        '42703' => 'Error interno: La columna consultada no existe.',
    ];

    /**
     * Traduce un código de error (como un SQLSTATE) a un mensaje legible.
     * Si no se encuentra, devuelve un mensaje por defecto.
     */
    public static function translate(string $code, string $defaultMessage = 'Ocurrió un error interno en el servidor.'): string {
        return self::$sqlErrors[$code] ?? $defaultMessage;
    }
}
