<?php
class Group extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'group';
	protected $guarded = array('id');
	protected $hidden = array('updated_at','created_at');
    protected $attributes = array(
       'is_exclusive' => 1
    );
	public function members(){
        return $this->belongsToMany('\User','group_user_role')->withPivot('role_id')->withTimestamps();
    }
}

?>