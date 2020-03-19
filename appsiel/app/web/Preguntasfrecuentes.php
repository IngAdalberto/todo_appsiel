<?php

namespace App\web;

use Illuminate\Database\Eloquent\Model;

class Preguntasfrecuentes extends Model
{
    protected $table = 'pw_preguntas';
    protected $fillable = ['id', 'titulo', 'descripcion','imagen_fondo', 'widget_id', 'created_at', 'updated_at'];

    public function widget()
    {
        return $this->belongsTo(Widget::class);
    }

    public function itempreguntas()
    {
        return $this->hasMany(Itempregunta::class,'pregunta_id');
    }
}