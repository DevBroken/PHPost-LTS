<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

$parrafo = "font-family:Arial,sans-serif;font-size:16px;color:#444;line-height:1.7;margin:8px 0;";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>{TITLE}</title>
<style>
@media only screen and (max-width:600px) {
    .email-container { width:100% !important; max-width:600px !important; }
    .email-content { padding:20px !important; }
    .email-header img { max-height:32px !important; }
    .email-header td { display:block !important; text-align:center !important; padding:4px 0 !important; }
}
</style>
</head>
<body style="margin:0;padding:0;background-color:#f4f6fb;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#2563eb;">
        <tr>
            <td align="center" style="padding:20px;">
                <table width="100%" cellpadding="0" cellspacing="0" class="email-container" style="max-width:600px;">
                    <tr>
                        <td class="email-header">
                            <img src="{LOGOWEB}" alt="{TITLE}" style="max-height:40px;">
                        </td>
                        <td align="right" style="color:#fff;font-weight:700;font-size:13px;letter-spacing:1px;">{TITLE}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6fb;">
		<tr>
			<td align="center" style="padding:30px 20px;">
				<table width="100%" cellpadding="0" cellspacing="0" class="email-container" style="max-width:600px;background-color:#ffffff;border-radius:10px;overflow:hidden;">
					<tr>
						<td class="email-content" style="padding:32px 40px;">
							<h2 style="font-family:Arial,sans-serif;font-weight:700;font-size:26px;color:#111;margin:10px 0 4px;line-height:1.2;text-align:center;">Activá tu cuenta</h2>
							<p style="<?= $parrafo ?>">Hola {USERNAME}!</p>
							<p style="<?= $parrafo ?>">¡Te damos la bienvenida a {TITLE}!</p>
							<p style="<?= $parrafo ?>">Para finalizar con el proceso de registro, confirma tu dirección de email accediendo a <a href="{LINK}" style="color:#2563eb;text-decoration:underline;font-weight:500;">este enlace</a> y luego ingresando este pin <strong style="display:block;text-align:center;padding:1rem 0;font-size:32px;letter-spacing:4px;">{PIN}</strong></p>
							<div style="padding:8px 0;">
								<hr style="border:none;border-top:1px solid #e5e7eb;margin:0;">
							</div>
							<p style="<?= $parrafo ?>">Posteriormente podrá acceder con las siguientes credenciales:</p>
							<p style="<?= $parrafo ?>">Usuario: {USERNAME}</p>
							<p style="<?= $parrafo ?>">Contraseña: {PASSWORD}</p>
							<div style="padding:8px 0;">
								<hr style="border:none;border-top:1px solid #e5e7eb;margin:0;">
							</div>
							<p style="<?= $parrafo ?>">Antes de empezar a interactuar con la comunidad, te recomendamos que visites el <a href="{LINK}" style="color:#2563eb;text-decoration:underline;font-weight:500;">protocolo</a> del sitio.</p>
							<p style="<?= $parrafo ?>">Esperamos que disfrutes enormemente tu visita.</p>
							<p style="<?= $parrafo ?>">¡Te damos la bienvenida a Muchas gracias!</p>
							<div style="margin-top:24px;padding-top:16px;border-top:1px solid #e5e7eb;">
							    <p style="font-family:Arial,sans-serif;font-size:13px;color:#9ca3af;margin:0;">El Staff de {TITLE}</p>
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:20px;font-family:'DM Sans',Arial,sans-serif;font-size:11px;color:#9ca3af;">
                <a href="{TERMS}" style="color:#9ca3af;">T&eacute;rminos &amp; condiciones</a> &nbsp;|&nbsp;
                <a href="{PRIVACY}" style="color:#9ca3af;">Privacidad</a>
            </td>
        </tr>
    </table>
</body>
</html>