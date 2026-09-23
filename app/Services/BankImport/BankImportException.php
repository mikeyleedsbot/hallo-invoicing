<?php

namespace App\Services\BankImport;

use RuntimeException;

/** Fout met een boodschap die rechtstreeks aan de gebruiker getoond mag worden. */
class BankImportException extends RuntimeException
{
}
