<?php
class Coupon extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coupon';
    protected $appends = ['new_price'];
    public function price()
    {   
        return $this->belongsTo('\Price','course_sale_id','id');
    }
    
    public static function scopeValid($query)
    {
        return $query->whereRaw("now() >= dtStart and now() <= dtEnd")->where('isDeleted', '=', '0');
    }
    

    public function getNewPriceAttribute()
    {   
        if($this->value<1){
            $newPrice = floatval($this->price->price)-floatval($this->price->price)*floatval($this->value);
        } else {
            $newPrice = floatval($this->price->price)-floatval($this->value);
        }
        return $this->attributes['newPrice'] = $newPrice;
    }

}

?>