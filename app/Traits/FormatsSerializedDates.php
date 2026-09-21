<?php

namespace App\Traits;

use DateTimeInterface;

trait FormatsSerializedDates
{
    /**
     * Prepare a date for array / JSON serialization without ugly ISO8601 microseconds.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        // Pure date without time or midnight 00:00:00
        if ($date->format('H:i:s') === '00:00:00') {
            return $date->format('Y-m-d');
        }

        return $date->format('Y-m-d h:i A');
    }
}
