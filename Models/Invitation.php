<?php
class Invitation extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'group_invitation';
	protected $guarded = array('id');
	protected $hidden = array('updated_at','created_at');
    public function sender()
    {
        return $this->belongsTo('\User','sender_id');
    }
    public function group()
    {
        return $this->belongsTo('\Group');
    }
}

?>