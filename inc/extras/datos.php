<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

/**
 * Meses
*/
$tsMeses = [
   1 => 'Enero',
   'Febrero',
   'Marzo',
   'Abril',
   'Mayo',
   'Junio',
   'Julio',
   'Agosto',
   'Septiembre',
   'Octubre',
   'Noviembre',
   'Diciembre',
];

/**
 * Perfil
*/
$tsPerfilData = [];

/**
 * Privacidad
*/
$tsPrivacidad = [
   'none'       => 'Nadie',                           /* 0 */
   'mutual'     => 'Usuarios que sigo y me siguen',   /* 1 */
   'either'     => 'Usuarios que sigo o me siguen',   /* 2 */
   'followers'  => 'Mis seguidores',                  /* 3 */
   'following'  => 'Usuarios que sigo',               /* 4 */
   'registered' => 'Usuarios registrados',            /* 5 */
   'public'     => 'Todos'                            /* 6 */
];

/**
 * Denuncias
*/
$tsDenuncias = [
  'users' => [
      '',
      'Perfil falso/clon',
      'Usuario insultante y agresivo',
      'Publicaciones inapropiadas',
      'Foto del perfil inapropiada',
      'Publicidad no deseada (SPAM)',
   ],
   'posts' => [
      '',
    	'Re-post',
    	'Se hace Spam',
    	'Tiene links muertos',
    	'Es racista o irrespetuoso',
    	'Contiene informaci&oacute;n personal',
    	'El t&iacute;tulo esta en may&uacute;scula',
    	'Contiene pedofilia',
    	'Es gore o asqueroso',
    	'Est&aacute; mal la fuente',
    	'Post demasiado pobre / Crap',
    	'No cumple con el protocolo',
    	'Otra raz&oacute;n (especificar)'
   ],
	'fotos' => [
      '',
    	'Ya est&aacute; publicada',
    	'Se hace Spam',
    	'La imagen est&aacute; ca&iacute;da',
    	'Es racista o irrespetuosa',
    	'Contiene informaci&oacute;n personal',
    	'Contiene pedofilia',
    	'Es gore o asquerosa',
    	'Otra raz&oacute;n (especificar)'
   ]
];

# Eliminar contenido (Admistrar usuario)
$tsContenido = [
   [
      'for' => 'posts', 
      'title' => 'Posts', 
      'desc' => 'Se eliminar&aacute;n todos sus posts y sus comentarios.'
   ], [
      'for' => 'fotos', 
      'title' => 'Fotos', 
      'desc' => 'Se eliminar&aacute;n todas sus fotos publicadas y sus comentarios.'
   ], [
      'for' => 'estados', 
      'title' => 'Estados', 
      'desc' => 'Se eliminar&aacute;n todas sus publicaciones de muros'
   ], [
      'for' => 'composts', 
      'title' => 'Comentarios de Posts', 
      'desc' => 'Se eliminar&aacute;n todos sus comentarios en posts.'
   ], [
      'for' => 'comfotos', 
      'title' => 'Comentarios de Fotos',
      'desc' => 'Se eliminar&aacute;n todos sus comentarios en fotos.'
   ], [
      'for' => 'comestados', 
      'title' => 'Comentarios en Estados',
      'desc' => 'Se eliminar&aacute;n todos sus comentarios en estados'
   ], [
      'for' => 'like', 
      'title' => 'Like',
      'desc' => 'Se eliminar&aacute;n sus likes en estados y comentarios en estados'
   ], [
      'for' => 'seguidores', 
      'title' => 'Seguidores',
      'desc' => 'Se eliminar&aacute; la lista de todos sus seguidores.'
   ], [
      'for' => 'siguiendo', 
      'title' => 'Siguiendo',
      'desc' => 'Se eliminar&aacute; la lista de todos a los que sigue.'
   ], [
      'for' => 'favoritos', 
      'title' => 'Favoritos',
      'desc' => 'Se eliminar&aacute; la lista de favoritos que haya agregado.'
   ], [
      'for' => 'votosposts', 
      'title' => 'Votos en Posts',
      'desc' => 'Se eliminar&aacute;n los votos de puntos que haya dejado en posts.'
   ], [
      'for' => 'votosfotos', 
      'title' => 'Votos en Fotos',
      'desc' => 'Se eliminar&aacute;n los votos positivos y negativos que haya dejado en fotos.'
   ], [
      'for' => 'actividad', 
      'title' => 'Actividad',
      'desc' => 'Se eliminar&aacute; toda su actividad.'
   ], [
      'for' => 'avisos', 
      'title' => 'Avisos',
      'desc' => 'Se eliminar&aacute;n todos los avisos que ha recibido.'
   ], [
      'for' => 'bloqueos', 
      'title' => 'Bloqueos',
      'desc' => 'Se eliminar&aacute;n todos los bloqueos que ha recibido.'
   ], [
      'for' => 'mensajes', 
      'title' => 'Mensajes Privados',
      'desc' => 'Se eliminar&aacute;n todos los mensajes que ha enviado y recibido.'
   ], [
      'for' => 'sesiones', 
      'title' => 'Sesiones',
      'desc' => 'Se eliminar&aacute;n todas las sesiones.'
   ], [
      'for' => 'visitas', 
      'title' => 'Visitas',
      'desc' => 'Se eliminar&aacute;n todo rastro de visitas de este usuario en perfiles, posts y fotos.'
   ]
];