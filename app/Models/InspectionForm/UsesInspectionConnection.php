<?php

namespace App\Models\InspectionForm;

trait UsesInspectionConnection
{
    /**
     * Get the database connection name for the model.
     *
     * Resolves the connection name dynamically from configuration,
     * allowing zero-downtime Strangler Fig migration between DISS
     * and Daijo_MES databases while preserving test isolation.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return config('database.inspection_connection', 'mes_mysql');
    }
}
