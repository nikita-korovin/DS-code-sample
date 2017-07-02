<?php

namespace App\Http\Middleware\Role;
use App\Http\Middleware\Role\Contract\Role;

/**
 * Determines if a user has lawyer role
 *
 * Class User
 */
class Lawyer extends Role{
    
    protected function getRole(){
        return \App\User::ROLE_LAWYER;
    }
    
}