<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

$tsPaises = [
   'AF' => 'Afganist&aacute;n',
   'AL' => 'Albania',
   'DE' => 'Alemania',
   'DZ' => 'Argelia',
   'AD' => 'Andorra',
   'AO' => 'Angola',
   'AI' => 'Anguila',
   'AG' => 'Antigua y Barbuda',
   'AQ' => 'Ant&aacute;rtida',
   'SA' => 'Arabia Saudita',
   'AR' => 'Argentina',
   'AM' => 'Armenia',
   'AW' => 'Aruba',
   'AU' => 'Australia',
   'AT' => 'Austria',
   'AZ' => 'Azerbaiy&aacute;n',
   'BS' => 'Bahamas',
   'BH' => 'Bahr&eacute;in',
   'BD' => 'Bangladesh',
   'BB' => 'Barbados',
   'BZ' => 'Belice',
   'BJ' => 'Benin',
   'BM' => 'Bermudas',
   'BY' => 'Bielorrusia',
   'BO' => 'Bolivia',
   'BA' => 'Bosnia y Herzegovina',
   'BW' => 'Botswana',
   'BR' => 'Brasil',
   'BN' => 'Brun&eacute;i',
   'BG' => 'Bulgaria',
   'BF' => 'Burkina Faso',
   'BI' => 'Burundi',
   'BT' => 'But&aacute;n',
   'BE' => 'B&eacute;lgica',
   'CV' => 'Cabo Verde',
   'KH' => 'Camboya',
   'CM' => 'Camer&uacute;n',
   'CA' => 'Canad&aacute;',
   'TD' => 'Chad',
   'CL' => 'Chile',
   'CN' => 'China',
   'CY' => 'Chipre',
   'VA' => 'Ciudad del Vaticano',
   'CO' => 'Colombia',
   'KM' => 'Comoras',
   'KP' => 'Corea del Norte',
   'KR' => 'Corea del Sur',
   'CR' => 'Costa Rica',
   'CI' => 'Costa de Marfil',
   'HR' => 'Croacia',
   'CU' => 'Cuba',
   'DK' => 'Dinamarca',
   'DM' => 'Dominica',
   'EC' => 'Ecuador',
   'EG' => 'Egipto',
   'SV' => 'El Salvador',
   'AE' => 'Emiratos &aacute;rabes Unidos',
   'ER' => 'Eritrea',
   'SK' => 'Eslovaquia',
   'SI' => 'Eslovenia',
   'ES' => 'Espa&ntilde;a',
   'US' => 'Estados Unidos',
   'EE' => 'Estonia',
   'ET' => 'Etiop&iacute;a',
   'PH' => 'Filipinas',
   'FI' => 'Finlandia',
   'FJ' => 'Fiyi',
   'FR' => 'Francia',
   'GA' => 'Gab&oacute;n',
   'GM' => 'Gambia',
   'GE' => 'Georgia',
   'GH' => 'Ghana',
   'GI' => 'Gibraltar',
   'GD' => 'Granada',
   'GR' => 'Grecia',
   'GL' => 'Groenlandia',
   'GP' => 'Guadalupe',
   'GU' => 'Guam',
   'GT' => 'Guatemala',
   'GF' => 'Guayana Francesa',
   'GG' => 'Guernsey',
   'GN' => 'Guinea',
   'GQ' => 'Guinea Ecuatorial',
   'GW' => 'Guinea-Bissau',
   'GY' => 'Guyana',
   'HT' => 'Hait&iacute;',
   'HN' => 'Honduras',
   'HK' => 'Hong Kong',
   'HU' => 'Hungr&iacute;a',
   'IN' => 'India',
   'ID' => 'Indonesia',
   'IQ' => 'Iraq',
   'IE' => 'Irlanda',
   'IR' => 'Ir&aacute;n',
   'BV' => 'Isla Bouvet',
   'IM' => 'Isla de Man',
   'CX' => 'Isla de Navidad',
   'IS' => 'Islandia',
   'KY' => 'Islas Caim&aacute;n',
   'CC' => 'Islas Cocos',
   'CK' => 'Islas Cook',
   'FO' => 'Islas Feroe',
   'GS' => 'Islas Georgias del Sur y Sandwich del Sur',
   'HM' => 'Islas Heard y McDonald',
   'MP' => 'Islas Marianas del Norte',
   'MH' => 'Islas Marshall',
   'PN' => 'Islas Pitcairn',
   'SB' => 'Islas Salom&oacute;n',
   'TC' => 'Islas Turcas y Caicos',
   'VG' => 'Islas V&iacute;rgenes Brit&aacute;nicas',
   'VI' => 'Islas V&iacute;rgenes Estadounidenses',
   'UM' => 'Islas ultramarinas de Estados Unidos',
   'IL' => 'Israel',
   'IT' => 'Italia',
   'JM' => 'Jamaica',
   'JP' => 'Jap&oacute;n',
   'JE' => 'Jersey',
   'JO' => 'Jordania',
   'KZ' => 'Kazajist&aacute;n',
   'KE' => 'Kenia',
   'KG' => 'Kirguist&aacute;n',
   'KI' => 'Kiribati',
   'KW' => 'Kuwait',
   'LA' => 'Laos',
   'LS' => 'Lesoto',
   'LV' => 'Letonia',
   'LR' => 'Liberia',
   'LY' => 'Libia',
   'LI' => 'Liechtenstein',
   'LT' => 'Lituania',
   'LU' => 'Luxemburgo',
   'LB' => 'L&iacute;bano',
   'MO' => 'Macao',
   'MG' => 'Madagascar',
   'MY' => 'Malasia',
   'MW' => 'Malaui',
   'MV' => 'Maldivas',
   'MT' => 'Malta',
   'ML' => 'Mal&iacute;',
   'MA' => 'Marruecos',
   'MQ' => 'Martinica',
   'MU' => 'Mauricio',
   'MR' => 'Mauritania',
   'YT' => 'Mayotte',
   'FM' => 'Micronesia',
   'MD' => 'Moldavia',
   'MN' => 'Mongolia',
   'ME' => 'Montenegro',
   'MS' => 'Montserrat',
   'MZ' => 'Mozambique',
   'MM' => 'Myanmar',
   'MX' => 'M&eacute;xico',
   'MC' => 'M&oacute;naco',
   'NA' => 'Namibia',
   'NR' => 'Nauru',
   'NP' => 'Nepal',
   'NI' => 'Nicaragua',
   'NE' => 'Niger',
   'NG' => 'Nigeria',
   'NU' => 'Niue',
   'NF' => 'Norfolk',
   'NO' => 'Noruega',
   'NC' => 'Nueva Caledonia',
   'NZ' => 'Nueva Zelanda',
   'OM' => 'Om&aacute;n',
   'PK' => 'Pakist&aacute;n',
   'PW' => 'Palaos',
   'PA' => 'Panam&aacute;',
   'PG' => 'Pap&uacute;a Nueva Guinea',
   'PY' => 'Paraguay',
   'NL' => 'Pa&iacute;ses Bajos',
   'PE' => 'Per&uacute;',
   'PF' => 'Polinesia Francesa',
   'PL' => 'Polonia',
   'PT' => 'Portugal',
   'PR' => 'Puerto Rico',
   'QA' => 'Qatar',
   'GB' => 'Reino Unido',
   'CF' => 'Rep&uacute;blica Centroafricana',
   'CZ' => 'Rep&uacute;blica Checa',
   'CD' => 'Rep&uacute;blica Democr&aacute;tica del Congo',
   'DO' => 'Rep&uacute;blica Dominicana',
   'MK' => 'Rep&uacute;blica de Macedonia',
   'CG' => 'Rep&uacute;blica del Congo',
   'EH' => 'Rep&uacute;blica &aacute;rabe Saharaui Democr&aacute;tica',
   'RE' => 'Reuni&oacute;n',
   'RW' => 'Ruanda',
   'RO' => 'Rumania',
   'RU' => 'Rusia',
   'MF' => 'Saint-Martin',
   'WS' => 'Samoa',
   'AS' => 'Samoa Americana',
   'BL' => 'San Bartolom&eacute;',
   'KN' => 'San Crist&oacute;bal y Nieves',
   'SM' => 'San Marino',
   'PM' => 'San Pedro y Miquel&oacute;n',
   'VC' => 'San Vicente y las Granadinas',
   'SH' => 'Santa Helena',
   'LC' => 'Santa Luc&iacute;a',
   'ST' => 'Sao Tom&eacute; y Principe',
   'SN' => 'Senegal',
   'RS' => 'Serbia',
   'SC' => 'Seychelles',
   'SL' => 'Sierra Leona',
   'SG' => 'Singapur',
   'SY' => 'Siria',
   'SO' => 'Somalia',
   'LK' => 'Sri Lanka',
   'SZ' => 'Suazilandia',
   'ZA' => 'Sud&aacute;frica',
   'SD' => 'Sud&aacute;n',
   'SE' => 'Suecia',
   'CH' => 'Suiza',
   'SR' => 'Surinam',
   'SJ' => 'Svalbard y Jan Mayen',
   'TH' => 'Tailandia',
   'TW' => 'Taiw&aacute;n',
   'TZ' => 'Tanzania',
   'TJ' => 'Tayikist&aacute;n',
   'IO' => 'Territorio Brit&aacute;nico del Oc&eacute;ano &iacute;ndico',
   'TF' => 'Territorios Australes Franceses',
   'PS' => 'Territorios palestinos',
   'TL' => 'Timor Oriental',
   'TG' => 'Togo',
   'TK' => 'Tokelau',
   'TO' => 'Tonga',
   'TT' => 'Trinidad y Tobago',
   'TM' => 'Turkmenist&aacute;n',
   'TR' => 'Turqu&iacute;a',
   'TV' => 'Tuvalu',
   'TN' => 'T&uacute;nez',
   'UA' => 'Ucrania',
   'UG' => 'Uganda',
   'UY' => 'Uruguay',
   'UZ' => 'Uzbekist&aacute;n',
   'VU' => 'Vanuatu',
   'VE' => 'Venezuela',
   'VN' => 'Vietnam',
   'WF' => 'Wallis y Futuna',
   'YE' => 'Yemen',
   'DJ' => 'Yibuti',
   'ZM' => 'Zambia',
   'ZW' => 'Zimbabue'
];
/**
 * Meses
*/
$tsMeces = [
   1 => 'Enero',
   2 => 'Febrero',
   3 => 'Marzo',
   4 => 'Abril',
   5 => 'Mayo',
   6 => 'Junio',
   7 => 'Julio',
   8 => 'Agosto',
   9 => 'Septiembre',
   10 => 'Octubre',
   11 => 'Noviembre',
   12 => 'Diciembre',
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
    	$tsCore->settings['titulo'].' no es un foro',
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