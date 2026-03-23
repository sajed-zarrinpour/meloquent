<?php
namespace SajedZarinpour\Meloquent\Concerns\NonPersistanceConcerns;

trait DateUtils {

    /**
     * Override to prevent date formatting issues when connection is null
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Override to handle date attributes without database connection
     */
    protected function asDateTime($value)
    {
        if ($value instanceof \Carbon\Carbon) {
            return $value;
        }

        if (is_numeric($value)) {
            return \Carbon\Carbon::createFromTimestamp($value);
        }

        if (is_string($value)) {
            return \Carbon\Carbon::parse($value);
        }

        return $value;
    }
}