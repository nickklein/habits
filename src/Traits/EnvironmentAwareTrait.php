<?php

namespace NickKlein\Habits\Traits;

use App\Enums\AppEnvironmentEnums;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait EnvironmentAwareTrait
{
    /**
     * Check if the current environment is home environment
     *
     * @return bool
     */
    private function isHomeEnvironment(): bool
    {
        return app()->environment([AppEnvironmentEnums::HOME->value]);
    }

    /**
     * Get the appropriate database connection based on environment
     * In home environment, use cloud connection for timer operations
     *
     * @return string
     */
    private function getDatabaseConnection(): string
    {
        return $this->isHomeEnvironment() ? 'cloud' : 'mysql';
    }

    /**
     * Get database connection instance
     *
     * @return \Illuminate\Database\Connection
     */
    private function getConnection()
    {
        return DB::connection($this->getDatabaseConnection());
    }

    /**
     * Set the database connection on a model instance
     *
     * @param Model $model
     * @return Model
     */
    private function setModelConnection(Model $model): Model
    {
        $model->setConnection($this->getDatabaseConnection());

        return $model;
    }
}
