<?php
class Price extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $hidden = array('updated_at', 'created_at');
    public $timestamps = false;
    protected $table = 'course_sale';
    
    public function course()
    {
        return $this->belongsTo('\Course');
    }
    
    public function coupon()
    {
        return $this->hasMany('\Coupon','course_sale_id');
    }

}

?>
