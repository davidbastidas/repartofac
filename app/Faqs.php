<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Faqs extends Model
{
    protected $table = 'faqs';

    public function user() {
      return $this->belongsTo('App\AdminTable', 'id_user', 'id');
    }
}
