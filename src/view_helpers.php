<?php
declare(strict_types=1);

/**
 * HTML-escape mit konsistenten Flags.
 *
 * - ENT_QUOTES: escapt sowohl doppelte als auch einfache Anführungszeichen
 * - ENT_SUBSTITUTE: ersetzt ungültige UTF-8-Sequenzen durch U+FFFD (�),
 *   statt einen leeren String zurückzugeben. Verhindert, dass bei
 *   kaputtem Input Content stillschweigend verschwindet.
 */
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}