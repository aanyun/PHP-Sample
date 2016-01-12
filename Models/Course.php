<?php
class Course extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'course';
    protected $appends = ['thumbnail'];
    protected $hidden = array('pivot','created_at','updated_at');
    public function getThumbnailAttribute(){
        return "http://".$_SERVER['HTTP_HOST']."/".DOCUMENTPATH."/courses/thumbnail/".$this->attributes['id'];
    }

    public function price(){
        return $this->hasMany('\Price');
    }
    
    public static function scopeValid($query){
        return $query->where('isPublished', '=', '1');
    }

    public function catalog(){
        return $this->belongsToMany('\Catalog');
    }

    public function prerequisite(){
        return $this->belongsToMany('\Course','course_prerequisite','course_id','required_course_id');
    }

}

?>
