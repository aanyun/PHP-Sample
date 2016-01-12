<?php
class Auth_Token extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auth_token';
    protected $guarded = array('id');
}

?>