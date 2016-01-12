<?php
class Role extends \Illuminate\Database\Eloquent\Model{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'role';
    protected $hidden = array('created_at', 'updated_at');
    public function permissions(){
        return $this->belongsToMany('\Permission','role_permission');
    }
}

?>