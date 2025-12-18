<?php

namespace App\Models;

class Report extends BaseModel{
    protected $primaryKey = 'chartKey';
    protected $keyType = 'string';
    public $timestamps = false;
}