<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDocumentSigned extends Model
{
    protected $table = 'user_document_users';

    protected $fillable = ['doc_id', 'user_id', 'status', 'permission'];

    public function document(){
        return $this->belongsTo('App\UserDocument', 'doc_id', 'id');
    }

    public function setUpdatedAtAttribute($value)
    {
        // to Disable updated_at
    }
}
