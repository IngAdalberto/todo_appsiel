<?php

namespace App\web;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    protected $table = 'pw_widget';
    protected $fillable = ['id','orden','estado','pagina_id','seccion_id','created_at','updated_at'];

    public function pagina(){
        return $this->belongsTo(Pagina::class,'pagina_id');
    }

    public function seccion(){
        return $this->belongsTo(Seccion::class,'seccion_id');
    }

}
