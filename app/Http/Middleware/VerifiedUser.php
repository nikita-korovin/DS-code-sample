<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 05.11.2016
 * Time: 3:07
 */

namespace App\Http\Middleware;


class VerifiedUser
{
    public function handle($request, \Closure $next, $guard = null)
    {
        if (!\Auth::user()->varified) {
            return redirect('/register');
        }
        return $next($request);
    }
}