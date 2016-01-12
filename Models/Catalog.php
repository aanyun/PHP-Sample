<?php
class Catalog extends \Illuminate\Database\Eloquent\Model
{
	 /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'catalog';
    public function course()
    {
        return $this->belongsToMany('\Course','catalog_course');
    }
    
}

?>
