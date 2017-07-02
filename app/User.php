<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    const STATUS_PARTNER_PENDING = 1;
    const STATUS_PARTNER_ACCEPTED = 2;
    const STATUS_PARTNER_REJECTED = 3;

    const ROLE_USER = 0;
    const ROLE_LAWYER = 1;
    const ROLE_ADMIN = 2;

    const VERIFICATION_STATUS_NONE = 0;
    const VERIFICATION_STATUS_PENDING = 1;
    const VERIFICATION_STATUS_DONE = 2;
    
    const VERIFICATION_VIDEO = 1;

    public static function getStatus($status)
    {
        $statuses = [
            self::STATUS_PARTNER_PENDING    => 'pending',
            self::STATUS_PARTNER_ACCEPTED   => 'accepted',
            self::STATUS_PARTNER_REJECTED   => 'rejected',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name','last_name', 'phone', 'email', 'password', 'scans', 'id_card', 'phone', 'address', 'city', 'video'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * All partners who sent request to current user
     *
     * @return $this
     */
    public function partners_to()
    {
        return $this->belongsToMany('App\User', 'users_users','right_partner','left_partner')->withPivot('status');
    }

    /**
    * All partners current user sent request to
    *
    * @return $this
    */
    public function partners_from()
    {
        return $this->belongsToMany('App\User', 'users_users','left_partner','right_partner')->withPivot('status');
    }

    /**
     * All documents of current user
     *
     * @return $this
     */
    public function agreements()
    {
        return $this->belongsToMany('App\UserDocument', 'user_document_users','user_id','doc_id')->withPivot(['status', 'permission']);
    }

    /**
     * Captures changes in model
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function($model){
            $qins = \SphinxQL::query()->insert()->into('user');
            $qins->set(['gid' => $model->id, 'id' => $model->id, 'first_name' => $model->first_name, 'last_name' => $model->last_name, 'email' => $model->email])->execute();
        });

        static::updated(function($model){
            $qrep = \SphinxQL::query()->replace()->into('user');
            $qrep->set(['gid' => $model->id, 'id' => $model->id, 'first_name' => $model->first_name, 'last_name' => $model->last_name, 'email' => $model->email])->execute();
        });

        static::deleted(function($model){
            \SphinxQL::query()->delete()->from('user')->where('id',$model->id)->execute();
        });
    }

    /**
     * Show status of $this towards current user
     * 
     * @return array|bool
     */
    public function partnerStatus()
    {
        $me = $this->partners_from()->find(
            \Auth::user()->id
        );
        $direction = 'right';
        if(!$me){
            $me = $this->partners_to()->find(\Auth::user()->id);
            $direction = 'left';
        }
        if(isset($me->pivot)){
            return [
                'status' => $me->pivot->status,
                'direction' => $direction
            ];
        }else{
            return false;
        }
    }

    /**
     * @return array
     */
    public function getScans()
    {
        If($this->scans){
            return explode("\n", $this->scans);
        }else{
            return [];
        }
    }
}
