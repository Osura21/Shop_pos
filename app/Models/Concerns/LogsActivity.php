<?php

namespace App\Models\Concerns;

use App\Support\ActivityLogger;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            ActivityLogger::logModel($model, 'created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            ActivityLogger::logModel($model, 'updated', $model->getOriginal(), $model->getAttributes());
        });

        static::deleted(function ($model) {
            ActivityLogger::logModel($model, 'deleted', $model->getOriginal(), []);
        });
    }
}
