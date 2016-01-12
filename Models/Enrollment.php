<?php
class Enrollment extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'enrollment';
    protected $fillable = array('user_id','course_id','Status','end_at');
    public function course()
    {
        return $this->hasOne('\Course','id','course_id');
    }
    public function user()
    {
        return $this->belongsTo('\User');
    }

}

?>