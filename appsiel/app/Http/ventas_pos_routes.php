<?php

Route::resource('ventas_pos', 'VentasPos\AplicacionController');

Route::get('pos_get_saldos_caja_pdv/{pdv_id}/{fecha_desde}/{fecha_hasta}', 'VentasPos\ReporteController@get_saldos_caja_pdv');
Route::get('pos_consultar_documentos_pendientes/{pdv_id}/{fecha}', 'VentasPos\ReporteController@consultar_documentos_pendientes');

Route::get('pos_factura_imprimir/{doc_encabezado_id}', 'VentasPos\FacturaPosController@imprimir');

Route::get('pos_factura_anular/{doc_encabezado_id}', 'VentasPos\FacturaPosController@anular_factura_pos');
Route::get('pos_factura_acumular/{pdv_id}', 'VentasPos\FacturaPosController@acumular');

Route::get('ventas_pos_form_registro_ingresos_gastos/{pdv_id}/{id_modelo}/{id_transaccion}', 'VentasPos\FacturaPosController@form_registro_ingresos_gastos');
Route::post('ventas_pos_form_registro_ingresos_gastos', 'VentasPos\FacturaPosController@store_registro_ingresos_gastos');



Route::resource('pos_factura', 'VentasPos\FacturaPosController');
