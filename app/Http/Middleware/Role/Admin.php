<?php

namespace App\Http\Middleware\Role;
use App\Http\Middleware\Role\Contract\Role;

/**
 * Determines if a user has admin role
 *
 * Class User
 */
class Admin extends Role{
    
    protected function getRole(){
        return \App\User::ROLE_ADMIN;
    }
    
}