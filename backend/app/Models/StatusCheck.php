<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Str;

class StatusCheck extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'status_checks';

    protected $fillable = [
        'id',
        'client_name',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
            $model->timestamp = now();
        });
    }
}
