<?php
class Log extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'log';
	protected $guarded = array('id');
	protected $hidden = array('updated_at','created_at');

}

?>