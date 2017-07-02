<?php

namespace App\Http\Middleware\Role\Contract;
use App\User;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Very simplistic role system.
 * Parent class of all role middleware
 * Determines if a user is of given role
 *
 * Class Role
 */
abstract class Role{

    /**
     * Handles incoming request
     * 
     * @param $request
     * @param \Closure $next
     * @param null $guard
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    final public function handle($request, \Closure $next, $guard = null)
    {

        if (!\Auth::check()) {
            return redirect('/');
            die();
        }
        $user = User::find(\Auth::user()->id);
        if($this->isRole($user->role)){
            return $next($request);
        }else{
            throw new UnauthorizedHttpException('Unauthorized', 'Unauthorized');
        }
    }

    /**
     * Marks the hierarchy of the roles 
     * 
     * @return array
     */
    final protected function roleHierarchy(){
        return [
            User::ROLE_USER => [
                User::ROLE_USER,
                User::ROLE_ADMIN,
                User::ROLE_LAWYER
            ],
            User::ROLE_LAWYER => [
                User::ROLE_LAWYER,
                User::ROLE_ADMIN,
            ],
            User::ROLE_ADMIN => [
                User::ROLE_ADMIN
            ]
        ];
    }

    /**
     * Determines if user role implies given role
     * 
     * @param $role
     * @return bool
     */
    final protected function isRole($role){
        return in_array($role, $this->roleHierarchy()[$this->getRole()]);
    }
    
    protected abstract function getRole();
}