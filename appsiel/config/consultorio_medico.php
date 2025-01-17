<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Variables globales 
    |--------------------------------------------------------------------------
    |
    | Variable globales
    |
    */

    'secciones_consulta' => '{
                                "0":{ 
                                        "nombre_seccion":"Datos consulta",
                                        "url_vista_show":"consultorio_medico.consultas.seccion_datos_consulta",
                                        "activo":1,
                                        "orden":1
                                }, 
                                "1":{ 
                                        "nombre_seccion":"Anamnesis",
                                        "url_vista_show":"consultorio_medico.consultas.anamnesis",
                                        "activo":1,
                                        "orden":1
                                }, 
                                "2":{ 
                                        "nombre_seccion":"Exámenes",
                                        "url_vista_show":"consultorio_medico.consultas.examenes",
                                        "activo":1,
                                        "orden":2
                                }, 
                                "3":{ 
                                        "nombre_seccion":"Fórmula Óptica",
                                        "url_vista_show":"consultorio_medico.consultas.formula_optica",
                                        "activo":1,
                                        "orden":3
                                }, 
                                "4":{ 
                                        "nombre_seccion":"Diagnóstico",
                                        "url_vista_show":"consultorio_medico.consultas.diagnostico",
                                        "activo":0,
                                        "orden":4
                                }, 
                                "5":{ 
                                	"nombre_seccion":"Observaciones",
                                	"url_vista_show":"consultorio_medico.consultas.observaciones",
                                	"activo":0,
                                	"orden":6
                                }, 
                                "6":{ 
                                	"nombre_seccion":"Remisión",
                                	"url_vista_show":"consultorio_medico.consultas.remision",
                                	"activo":0,
                                	"orden":7
                                }, 
                                "7":{ 
                                	"nombre_seccion":"Plan y/o tratamiento",
                                	"url_vista_show":"consultorio_medico.consultas.plan_de_tratamiento",
                                	"activo":0,
                                	"orden":8
                                }, 
                                "8":{ 
                                	"nombre_seccion":"Revisión por Sistemas",
                                	"url_vista_show":"consultorio_medico.consultas.revision_por_sistemas",
                                	"activo":0,
                                	"orden":9
                                }, 
                                "9":{ 
                                	"nombre_seccion":"Paraclínicos",
                                	"url_vista_show":"consultorio_medico.consultas.paraclinicos",
                                	"activo":0,
                                	"orden":10
                                }, 
                                "10":{ 
                                        "nombre_seccion":"Resultados de la consulta",
                                        "url_vista_show":"consultorio_medico.consultas.resultados",
                                        "activo":1,
                                        "orden":4
                                    }, 
                                "11":{ 
                                        "nombre_seccion":"Historia Médica Ocupacional",
                                        "url_vista_show":"consultorio_medico.consultas.historia_medica_ocupacional",
                                        "activo":1,
                                        "orden":1
                                    }, 
                                "12":{ 
                                        "nombre_seccion":"Prescripción Farmacológica",
                                        "url_vista_show":"consultorio_medico.consultas.prescripciones_farmacologicas",
                                        "activo":0,
                                        "orden":5
                                    }, 
                                "13":{ 
                                        "nombre_seccion":"Revisión por sistemas",
                                        "url_vista_show":"consultorio_medico.consultas.anamnesis_odontologia",
                                        "activo":0,
                                        "orden":2
                                    }, 
                                "14":{ 
                                        "nombre_seccion":"Odontograma",
                                        "url_vista_show":"consultorio_medico.odontologia.odontograma",
                                        "activo":0,
                                        "orden":3
                                    },
                                "15":{ 
                                        "nombre_seccion":"Endodoncia",
                                        "url_vista_show":"consultorio_medico.odontologia.endodoncia.index",
                                        "activo":0,
                                        "orden":4
                                    },
                                "16":{ 
                                        "nombre_seccion":"Diagnóstico",
                                        "url_vista_show":"consultorio_medico.diagnostico_cie.index",
                                        "activo":1,
                                        "orden":4
                                    }, 
                                "17":{ 
                                        "nombre_seccion":"Tratamiento",
                                        "url_vista_show":"consultorio_medico.procedimientos_cups.index",
                                        "activo":0,
                                        "orden":5
                                    }, 
                                "18":{ 
                                        "nombre_seccion":"Fórmula médica",
                                        "url_vista_show":"consultorio_medico.odontologia.formula_medica",
                                        "activo":0,
                                        "orden":0
                                    }, 
                                "19":{ 
                                        "nombre_seccion":"Evolución",
                                        "url_vista_show":"consultorio_medico.odontologia.evolucion",
                                        "activo":0,
                                        "orden":0
                                    }, 
                                "20":{ 
                                        "nombre_seccion":"RIPS",
                                        "url_vista_show":"consultorio_medico.rips",
                                        "activo":0,
                                        "orden":0
                                    }
    						}',
                            'mostrar_datos_laborales_paciente' => '1',
                            'lbl_tipo_evaluacion_medica_ocupacional' => 'EXÁMEN OCUPACIONAL CON ÉNFASIS OSTEOMUSCULAR'
];