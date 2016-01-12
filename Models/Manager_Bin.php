<?php
class Manager_Bin extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bin';
    public function seats(){
        return $this->belongsToMany('\Enrollment','bin_enrollment_transfer','bin_id')->withPivot('sender_id','receiver_id','course_sale_id')->withTimestamps();
    }
    public function transferOut(){
        return $this->belongsToMany('\Manager_Bin','bin_transfer','bin_id','to_bin_id')->withPivot('sender_id','receiver_id','course_sale_id','quantity')->withTimestamps();
    }
}

?>