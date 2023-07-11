<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'm_staff';

    public function setAttribute($key, $value)
    {
        $isRememberTokenAttribute = $key == $this->getRememberTokenName();
        if(!$isRememberTokenAttribute)
        {
            parent::setAttribute($key, $value);
        }
    }  


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'staff_name', 
        'staff_kana',
        'staff_short_name',
        'employee_code',
        'position_code',
        'email', 
        'login_id',
        'password',
        'tel_1',
        'tel_2',
        'mobile_email',
        'retirement_kbn', 
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        //  'remember_token',
    ];

}

