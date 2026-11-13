<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 * @license MIT
 */

$tsPage = "admin";

$tsLevel = 'administrador';

$tsAjax = !isset($_GET['ajax']);

$tsContinue = true;

require_once dirname(__DIR__, 2) . "/header.php"; // INCLUIR EL HEADER

$tsTitle = $tsCore->settings['titulo'].' - '.$tsCore->settings['slogan']; 	// TITULO DE LA PAGINA ACTUAL

// Nivel y permisos de acceso
$tsLevelMsg = $tsUser->setLevel(
		level: $tsLevel
	);
if($tsLevelMsg > 1) {
	$tsPage = 'aviso';
	$tsContinue = false;
	$smarty->assign("tsAviso", $tsLevelMsg);
}

//
if($tsContinue) {
	// ACTION
	$action = htmlspecialchars($_GET['action'] ?? '');
	// ACTION 2
	$act = htmlspecialchars($_GET['act'] ?? '');

	// CLASE ADMIN
	require_once TS_CLASS . '/c.admin.php';
	$tsAdmin = $container->get(tsAdmin::class);

	# CENTRO DE ADMINISTRACION
	if(empty($action)) {
		$smarty->assign("tsAdmins", $tsAdmin->getAdmins());
		$smarty->assign("tsInst", $tsAdmin->getInst());

	# SOPORTE Y CREDITOS
	} elseif($action === 'creditos'){
		$smarty->assign("tsVersion", $tsAdmin->getVersions());

	# CONFIGURACION
	} elseif($action === 'configs'){
		// GUARDAR CONFIGURACION
		if(!empty($_POST['titulo'])) {
			if($tsAdmin->saveConfig()) $tsCore->navigate('/admin/configs?save=true');
		}

	# NOTICIAS
	} elseif($action === 'news'){
		if(empty($act)) $smarty->assign("tsNews", $tsAdmin->getNoticias());
		elseif($act == 'nuevo' && !empty($_POST['not_body'])){
			if($tsAdmin->newNoticia()) $tsCore->navigate('/admin/news?save=true');
		} elseif($act == 'editar'){
			if(!empty($_POST['not_body'])){
				if($tsAdmin->editNoticia()) $tsCore->navigate('/admin/news?save=true');
			} else $smarty->assign("tsNew", $tsAdmin->getNoticia());
		}  elseif($act == 'borrar'){
			if($tsAdmin->delNoticia()) $tsCore->navigate('/admin/news?borrar=true');
		}

	# TEMAS
	} elseif($action === 'temas'){
		// VER TEMAS
		if(empty($act)) $smarty->assign("tsTemas", $tsAdmin->getTemas());
		// EDITAR TEMA
		elseif($act == 'editar'){
			// GUARDAR
			if(!empty($_POST['save'])){
				if($tsAdmin->saveTema()) $tsCore->navigate('/admin/temas?save=true');
			// MOSTRAR
			} else $smarty->assign("tsTema", $tsAdmin->getTema());
		// CAMBIAR TEMA
		} elseif($act == 'usar'){
			// GUARDAR
			if(!empty($_POST['confirm'])) {
				if($tsAdmin->changeTema()) $tsCore->navigate('/admin/temas?save=true');
			}
			// TITULO
			$smarty->assign("tt", $_GET['tt']);
		} elseif($act == 'borrar'){
			// GUARDAR
			if(!empty($_POST['confirm'])) {
				if($tsAdmin->deleteTema()) $tsCore->navigate('/admin/temas?save=true');
			}
			// TITULO
			$smarty->assign("tt", $_GET['tt']);
		} elseif($act == 'nuevo'){
			// GUARDAR
			if(!empty($_POST['path'])) {
				$install = $tsAdmin->newTema();
				if($install == 1) $tsCore->navigate('/admin/temas?save=true');
				else $smarty->assign("tsError", $install);
			}
		}
	# PUBLICIDADES
	} elseif($action === 'ads'){
		if(!empty($_POST['save'])){
			if($tsAdmin->saveAds()) $tsCore->navigate('/admin/ads?save=true');
		}
	# POSTS
	} elseif($action === 'posts'){
		 if(!$act) {
		 $smarty->assign("tsAdminPosts", $tsAdmin->GetAdminPosts());
		 }
	# FOTOS
	} elseif($action === 'fotos'){
		if(!$act) $smarty->assign("tsAdminFotos", $tsAdmin->GetAdminFotos());
	# ESTADÍSTICAS
	} elseif($action === 'stats'){
		$smarty->assign("tsAdminStats", $tsAdmin->GetAdminStats());
	# CAMBIOS DE NOMBRE DE USUARIO
	} elseif($action === 'nicks'){
		$smarty->assign("tsAdminNicks", $tsAdmin->getChangeNicks($act));
	// LISTA NEGRA
	} elseif($action === 'blacklist') {
		if(!$act) $smarty->assign("tsBlackList", $tsAdmin->getBlackList());
		elseif($act === 'editar' OR $act === 'nuevo'){
			if($_POST['edit'] OR $_POST['new']){
				$mode = ($_POST['edit']) ? $tsAdmin->saveBlock() : $tsAdmin->newBlock();
				if($mode == 1) $tsCore->navigate('/admin/blacklist?save=true');
				else $smarty->assign("tsError", $mode);
				$merge = [
					'value' => $_POST['value'],
					'type' => $_POST['type']
				];
				if(isset($_POST['new'])) $merge = array_merge($merge, ['reason' => $_POST['reason']]);
				$smarty->assign("tsBL", $merge);

			} else $smarty->assign("tsBL", $tsAdmin->getBlock());
		}
	// CENSURAS
	} elseif($action === 'badwords'){
		 if(!$act) {
		 $smarty->assign("tsBadWords", $tsAdmin->getBadWords());
		 }elseif($act == 'editar'){
			if($_POST['edit']){
					 $editar = $tsAdmin->saveBadWord();
				if($editar == 1) $tsCore->navigate('/admin/badwords?save=true');
				else $smarty->assign("tsError", $editar); $smarty->assign("tsBW",array(word => $_POST['before'], swop => $_POST['after'], method => $_POST['method'], type => $_POST['type']));
			}else $smarty->assign("tsBW", $tsAdmin->getBadWord());
		 }elseif($act == 'nuevo'){
		  if($_POST['new']){
					 $nuevo = $tsAdmin->newBadWord();
				if($nuevo == 1) $tsCore->navigate('/admin/badwords?save=true');
				else $smarty->assign("tsError", $nuevo); $smarty->assign("tsBW",array(word => $_POST['before'], swop => $_POST['after'], method => $_POST['method'], type => $_POST['type'], reason => $_POST['reason']));
			 }
			 }
	// CONECTADOS A LA COMUNIDAD
	} elseif($action === 'sesiones'){
		 if(!$act) {
		 $smarty->assign("tsAdminSessions", $tsAdmin->GetSessions());
		 }
	# MEDALLAS
	} elseif($action === 'medals') {
		# Incluimos el archivo
		include TS_ROOT . "/inc/class/c.medals.php";
		$tsMedal = new tsMedal();
		#
		if(empty($act)) $smarty->assign("tsMedals", $tsMedal->adGetMedals());
		elseif($act == 'nueva') {
			if($_POST['save']) {
				$agregar = $tsMedal->adNewMedal();
				if($agregar == 1) $tsCore->navigate('/admin/medals?save=true');
				else $smarty->assign("tsError", $agregar);
			}
		} elseif($act == 'showassign') $smarty->assign("tsAsignaciones", $tsMedal->adGetAssign());
		elseif($act == 'editar') {
			if($_POST['edit']) {
				$editar = $tsMedal->editMedal();
				if($editar == 1) $tsCore->navigate('/admin/medals?act=editar&mid='.$_GET['mid'].'&save=true');
				else $smarty->assign("tsError", $editar);
			} else $smarty->assign("tsMed", $tsMedal->adGetMedal());
		}
		# Evitamos que se repita
		if($act == 'nueva' or $act != 'editar') {
			//ICONOS PARA LAS MEDALLAS
			$smarty->assign("tsIcons", $tsAdmin->getExtraIcons('med', 32));
			//RANGOS DISPONIBLES
			$smarty->assign("tsRangos", $tsAdmin->getAllRangos());
			if($_POST["save"] or $_POST["edit"]) {
				$smarty->assign("tsMed", [
					'm_title' => $_POST['med_title'],
					'm_description' => $_POST['med_desc'],
					'm_image' => $_POST['med_img'],
					'm_cant' => $_POST['med_cant'],
					'm_type' => $_POST['med_type'],
					'm_cond_user' => $_POST['med_cond_user'],
					'm_cond_user_rango' => $_POST['med_cond_user_rango'],
					'm_cond_post' => $_POST['med_cond_post'],
					'm_cond_foto' => $_POST['med_cond_foto']
				]);
			}
		}
	# AFILIADOS
	} elseif($action === 'afs'){
		  // CLASS
		  include("../class/c.afiliado.php");
		  $tsAfiliado = new tsAfiliado;
		  // QUE HACER
		if($act == ''){
		  // AFILIADOS
		  $smarty->assign("tsAfiliados", $tsAfiliado->getAfiliados('admin'));
		} elseif($act == 'editar'){
				if($_POST['edit']){
					 if($tsAfiliado->EditarAfiliado()) $tsCore->navigate('/admin/afs?act=editar&aid='.$_GET['aid'].'&save=true');
				}
				$smarty->assign("tsAf", $tsAfiliado->getAfiliado('admin'));


		  }
	} elseif($action === 'pconfigs'){
		if(!empty($_POST['save'])){
			if($tsAdmin->savePConfigs()) $tsCore->navigate('/admin/pconfigs?save=true');
		}
	} elseif($action === 'cats'){
		if(!empty($_GET['ordenar'])){
			$tsAdmin->saveOrden();
		} elseif($act == 'editar'){
			if($_POST['save']){
				if($tsAdmin->saveCat()) $tsCore->navigate('/admin/cats?save=true');
			} else {
				$smarty->assign("tsType", $_GET['t']);
				$smarty->assign("tsCat", $tsAdmin->getCat());
				// SOLO LAS CATEGORIAS TIENEN ICONOS
				$smarty->assign("tsIcons", $tsAdmin->getExtraIcons());
			}
		} elseif($act == 'nueva'){
			if($_POST['save']){
				if($tsAdmin->newCat()) $tsCore->navigate('/admin/cats?save=true');
			} else {
				$smarty->assign("tsType", $_GET['t']);
				$smarty->assign("tsCID", $_GET['cid']);
				$smarty->assign("tsIcons", $tsAdmin->getExtraIcons());
			}
		} elseif($act == 'change'){
			if($_POST['save']){
				if($tsAdmin->MoveCat()) $tsCore->navigate('/admin/cats?save=true');
			}
		} elseif($act == 'borrar'){
			if($_POST['save']){
				// BORRAR CATEGORIA
				if($_GET['t'] == 'cat'){
					$save = $tsAdmin->delCat();
					if($save == 1) $tsCore->navigate('/admin/cats?save=true');
					else $smarty->assign("tsError", $save);
				// BORRAR SUBCATEGORIA
				} elseif($_GET['t'] == 'sub'){
					$save = $tsAdmin->delSubcat();
					if($save == 1) $tsCore->navigate('/admin/cats?save=true');
					else $smarty->assign("tsError", $save);
				}
			}
			//
			$smarty->assign("tsType", $_GET['t']);
			$smarty->assign("tsCID", $_GET['cid']);
			$smarty->assign("tsSID", $_GET['sid']);
		}
	} elseif($action === 'rangos'){
			// PORTADA
			if(empty($act)) {
				$smarty->assign("tsRangos", $tsAdmin->getRangos());
			// LISTAR USUARIOS DEPENDIENDO EL RANGO
			} elseif($act == 'list'){
				$smarty->assign("tsMembers", $tsAdmin->getRangoUsers());
			// EDITAR RANGO
			} elseif($act == 'editar'){
				if(!empty($_POST['save'])) {
					if($tsAdmin->saveRango()) $tsCore->navigate('/admin/rangos?save=true');
				} else {
					$smarty->assign("tsRango", $tsAdmin->getRango());
					$smarty->assign("tsIcons", $tsAdmin->getExtraIcons('ran'));
					$smarty->assign("tsType", $_GET['t']);
				}
			// NUEVO RANGO
			} elseif($act == 'nuevo'){
				if(!empty($_POST['save'])){
					$save = $tsAdmin->newRango();
					if($save == 1) $tsCore->navigate('/admin/rangos?save=true');
					else {
						$smarty->assign("tsError", $save);
						$smarty->assign("tsIcons", $tsAdmin->getExtraIcons('ran'));
					}
				} else {
					$smarty->assign("tsIcons", $tsAdmin->getExtraIcons('ran'));
						  $smarty->assign("tsType", $_GET['t']);
				}
			} elseif($act == 'borrar'){
				if(empty($_POST['save'])){
					$smarty->assign("tsRangos", $tsAdmin->getAllRangos());
				}else{
					if($tsAdmin->delRango()) $tsCore->navigate('/admin/rangos?save=true');
				}
			}
			// CAMBIAR RANGO PREDETERMINADO DEL REGISTRO
			elseif($act == 'setdefault'){
					if($tsAdmin->SetDefaultRango()) $tsCore->navigate('/admin/rangos?save=true');
			}
	} elseif($action === 'users'){
		if(empty($act)) $smarty->assign("tsMembers", $tsAdmin->getUsuarios());
		elseif($act == 'show'){
			$do = intval($_GET['t']);
			$user_id = intval($_GET['uid']);
			// HACER
			if($do === 5 OR $do === 6) {
				require_once TS_EXTRA . "datos.php";
				$smarty->assign("tsPerfil", $tsAdmin->getUserPrivacidad());
				$smarty->assign("tsPrivacidad", $tsPrivacidad);
				$smarty->assign("tsContenido", $tsContenido);
			}
			switch($do){
				case 5:
					if(!empty($_POST['save'])) {
						$update = $tsAdmin->setUserPrivacidad($user_id);
						if($update == 'OK') $tsCore->navigate('/admin/users?act=show&uid='.$user_id.'&save=true');
						else $smarty->assign("tsError", $update);
					}
				break;
				case 6:
					if(!empty($_POST['save'])) {
						$delete = $tsAdmin->deleteContent($user_id);
						if($delete == 'OK') $tsCore->navigate('/admin/users?act=show&uid='.$user_id.'&save=true');
						else $smarty->assign("tsError", $delete);
					}
				break;
				case 7:
					if(!empty($_POST['save'])){
						$update = $tsAdmin->setUserRango($user_id);
						if($update == 'OK') $tsCore->navigate('/admin/users?act=show&uid='.$user_id.'&save=true');
						else $smarty->assign("tsError", $update);
					}
					$smarty->assign("tsUserR", $tsAdmin->getUserRango($user_id));
				break;
				case 8:
					if(!empty($_POST['save'])){
						$update = $tsAdmin->setUserFirma($user_id);
						if($update == 'OK') $tsCore->navigate('/admin/users?act=show&uid='.$user_id.'&save=true');
						else $smarty->assign("tsError", $update);
					}
					$smarty->assign("tsUserF", $tsAdmin->getUserData());
				break;
				default:
					if(!empty($_POST['save'])){
						$update = $tsAdmin->setUserData($user_id);
						if($update == 'OK') $tsCore->navigate('/admin/users?act=show&uid='.$user_id.'&save=true');
						else $smarty->assign("tsError", $update);
					}
					$smarty->assign("tsUserD", $tsAdmin->getUserData());
				break;
			}
			// TIPO
			$smarty->assign("tsType", $_GET['t']);
			$smarty->assign("tsUserID", $user_id);
			$smarty->assign("tsUsername", $tsUser->getUserData(type: 'name', user: $user_id));
		}
	}

	// ACCION?
	$smarty->assign("tsAction", $action);
	//
	$smarty->assign("tsAct", $act);
	//
}

if($tsAjax)
{
	// Asignamos título
	$smarty->assign("tsTitle",$tsTitle);
	$smarty->assign("tsSave", $_GET['save']);
	// Incluir footer
	require_once TS_ROOT . "/footer.php";
}