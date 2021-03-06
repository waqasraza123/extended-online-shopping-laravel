<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductData extends Model
{
    protected $fillable = ['stock', 'shop_id', 'mobile_id', 'local_online', 'image', 'link', 'current_price', 'old_price', 'discount'];
    public $table = 'product_data';

    public function shop(){
        return $this->belongsTo('App\Shop', 'shop_id');
    }

    public function colors(){
        return $this->belongsToMany('App\Color', 'mobile_colors', 'product_id', 'color_id');
    }

    public function storages(){
        return $this->belongsToMany('App\Storage', 'mobile_storages', 'product_id', 'storage_id');
    }

    public function mobile(){
        return $this->belongsTo('App\Mobile', 'mobile_id', 'id');
    }
}
