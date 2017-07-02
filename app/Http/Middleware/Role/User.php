<?php

namespace App\Http\Middleware\Role;
use App\Http\Middleware\Role\Contract\Role;

/**
 * Determines if a user has user role
 *
 * Class User
 */
class User extends Role{
    
    protected function getRole(){
        return \App\User::ROLE_USER;
    }
    
}