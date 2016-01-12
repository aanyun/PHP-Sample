<?php
class User extends \Illuminate\Database\Eloquent\Model{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';
    protected $appends = ['avatar'];
    public function getAvatarAttribute(){
        return "http://".$_SERVER['HTTP_HOST']."/".DOCUMENTPATH."/users/avatar/".$this->attributes['id'];
    }
    public function enrollment(){
    	return $this->hasMany('\Enrollment');
    }
    public function companies(){
        return $this->belongsToMany('\Company','user_company');
    }
    public function groups(){
        return $this->belongsToMany('\Group','group_user_role')->withPivot('role_id');
    }
    public function invitations(){
        return $this->hasMany('\Invitation','receiver_id');
    }
    public function roles(){
        return $this->belongsToMany('\Role','user_role');
    }
    
}

?>