<?php
class MyCourses extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'my_courses';
    protected $fillable = array('user_id','course_id');
}

?>