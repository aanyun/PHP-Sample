<?php
class Permission extends \Illuminate\Database\Eloquent\Model{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permission';
    protected $hidden = array('created_at', 'updated_at');
}

?>