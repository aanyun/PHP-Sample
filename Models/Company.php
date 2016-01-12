<?php
class Company extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'company';
	protected $guarded = array('id');
	protected $hidden = array('updated_at','created_at');

}

?>