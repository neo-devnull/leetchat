<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Users extends Model
{
    use \Staudenmeir\LaravelUpsert\Eloquent\HasUpsertQueries;
    protected $table = 'users';
    protected $fillable = [
        'socketId'
    ];
    public $timestamps = false;

    /**
     * A local scope that injects MATCH AGAINST into the query since laravel does not support 
     * it out of the box.
     * dafuk laravel?
     */
    public function scopeInterests($query,$interests){
        return $query->WhereRaw("MATCH(interests) AGAINST ('{$interests}') ");
    }    
}
