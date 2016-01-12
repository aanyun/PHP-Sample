<?php
class UserTmp extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_tmp';
    protected $fillable = ['user_id', 'tmp_email'];
}

?>