<?php

namespace App\Exceptions;

class BookingSchemaNotReady extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Estamos actualizando la agenda. Inténtalo de nuevo en unos minutos. Si persiste, contacta con g95410464@proton.me.');
    }
}
