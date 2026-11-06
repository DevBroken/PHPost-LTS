<?php

declare(strict_types=1);

/**
 * @author PHPost Risus LTS
 * @copyright 2027
 */

defined('TS_HEADER') OR exit('No se permite el acceso directo al script');

use \PHPMailer\PHPMailer;
use \PHPMailer\Exception;

class tsEmail {

	protected PHPMailer $PHPMailer;
	protected tsCore $tsCore;

	private array $email_info = [];

	private string $url;
	private string $template;
	private string $nickname;
	private string $email;
	private array $subjectInfo = [];

	public function __construct(tsCore $tsCore) {
		$this->PHPMailer = new PHPMailer(true);
		$this->tsCore = $tsCore;
		//
		$this->url = rtrim($this->tsCore->settings['url'], '/');
	}

	/**
	 * Configura y renderiza la plantilla HTML del correo
	 */
	private function setTemplate(string $template, array $params): void {
		$defaultParams = [
			'LOGOWEB' => $this->url . '/assets/images/logo.png',
			'TITLE'   => $this->tsCore->settings['titulo'],
			'TERMS'   => $this->url . '/pages/terminos-y-condiciones',
			'PRIVACY' => $this->url . '/pages/privacidad'
		];
		$params = array_merge($defaultParams, $params);
		$plantilla = $template ?: 'default';
		$tempPath = TS_EXTRA . "/emails/{$plantilla}.php";
		// Existe el archivo?
		if (!file_exists($tempPath)) {
			throw new \InvalidArgumentException("La plantilla de email '{$plantilla}' no existe.");
		}
		// Reemplazamos los placeholders
		$fileContent = file_get_contents($tempPath);
		foreach ($params as $key => $param) {
			$fileContent = str_replace("{{$key}}", (string)$param, $fileContent);
		}
		// Lo que enviará
		$this->template = $fileContent;
	}

	/**
	 * Valida los datos del receptor y carga los asuntos
	 */
	private function setSend(string $asunto, string $nickname, string $email): void {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new \InvalidArgumentException('Dirección de email inválida: ' . $email);
		}
		$subjects = require TS_EXTRA . '/OptionsEmails.php';
		$this->nickname = $nickname;
		$this->email = $email;
		$this->subjectInfo = $subjects[$asunto] ?? [
			'subject' => 'Notificación de ' . $this->tsCore->settings['titulo'],
			'headers' => []
		];
	}

	/**
	 * Prepara los datos necesarios para el envío
	 */
	public function setActionSend(string $plantilla, array $parametros = [], array $usuario = []): void {
		$this->setTemplate($plantilla, $parametros);
		$this->setSend($plantilla, $usuario['nick'] ?? 'Usuario', $usuario['email'] ?? '');
	}



	/**
	 * Configura los parámetros técnicos del servidor SMTP
	 */
	private function configureSMTP(array $settings): void {
		$this->PHPMailer->isSMTP();
		//
		$this->PHPMailer->CharSet   = 'UTF-8';
		$this->PHPMailer->Host      = $settings['mail_hostname'];
		$this->PHPMailer->SMTPAuth  = $settings['mail_auth'];
		$this->PHPMailer->Username  = $settings['mail_username'];
		$this->PHPMailer->Password  = $settings['mail_password'];
		$this->PHPMailer->Port      = $settings['mail_port'];
		$this->PHPMailer->SMTPSecure = ($settings['mail_secure'] === 'TLS')
			? PHPMailer::ENCRYPTION_STARTTLS
			: PHPMailer::ENCRYPTION_SMTPS;

		if (APP_MODE === 'development') {
			$this->PHPMailer->SMTPOptions = [
				'ssl' => [
					'verify_peer'       => false,
					'verify_peer_name'  => false,
					'allow_self_signed' => true,
				],
			];
		}
	}

	/**
	 * Define el cuerpo, asunto y destinatarios del correo
	 */
	private function setContent(): void {
		$this->PHPMailer->setFrom($this->tsCore->settings['email'], $this->tsCore->settings['titulo']);
		$this->PHPMailer->addAddress($this->email, $this->nickname);

		foreach ($this->subjectInfo['headers'] ?? [] as $key => $value) {
			$this->PHPMailer->addCustomHeader($key, $value);
		}

		$this->PHPMailer->isHTML(true);
		$this->PHPMailer->Subject = $this->subjectInfo['subject'];
		$this->PHPMailer->Body    = $this->template;
		$this->PHPMailer->AltBody = strip_tags($this->template);
	}

	/**
	 * Realiza el envío del correo vía SMTP
	 */
	public function send(): bool {
		$settings = require TS_ROOT . '/config.phpmailer.php';
		try {
			$this->configureSMTP($settings);
			$this->setContent();
			$this->PHPMailer->send();
			return true;
		} catch (Exception) {
			error_log("Error de PHPMailer: " . $this->PHPMailer->ErrorInfo);
			return false;
		} finally {
			// Nos aseguramos de limpiar la instancia para futuros envíos
			$this->PHPMailer->clearAddresses();
			$this->PHPMailer->clearCustomHeaders();
		}
	}

	// DESDE ESTE PUNTO QUEDA COMO PARA LOS LEGACY
	/*
		setEmailInfo()
	*/
	function setEmail() {
		$this->emailSubject = $this->setEmailSubject();
		$this->emailHeaders = $this->setEmailHeaders();
		$this->emailBody = $this->setEmailBody();
		$this->emailTo = $this->email_info['user_email'];
	}
	/**
	 * Reemplazamos el contenido
	*/
	function replaceHTML($body) {
		global $tsCore;
		$emailBody = file_get_contents(TS_EXTRA . "emails/basic.html");
		$emailBody = str_replace(
		   ["_WEBSITE_", "_CONTENTBODY_", "_LOGOWEB_", "_TERMS_", "_PRIV_"],
		   [
			  $tsCore->settings['titulo'],
			  $body,
			  $tsCore->settings['images'] . '/phpostmin.gif',
			  $tsCore->settings['url'] . "/pages/terminos-y-condiciones/",
			  $tsCore->settings['url'] . "/pages/privacidad/"
		   ],
		   $emailBody
		);
		return $emailBody;
	}
	/*
		sendEmail()
	*/
	function sendEmail() {
		$mail = mail(
			$this->emailTo,
			$this->emailSubject,
			$this->replaceHTML($this->emailBody),
			$this->emailHeaders
		);
		return ($mail) ? true : false;
	}
	/*
		setEmailSubject()
	*/
	function setEmailSubject(){
		switch($this->email_info['ref']) {
			case 'signup' :
				$subject = "Por favor completa tu registro.";
			break;
		}

		// ENCODE SUBJECT FOR UTF8
		return "=?UTF-8?B?".base64_encode($subject)."?=";
	}
	/*
		setEmailHeaders()
	*/
	function setEmailHeaders(){
		global $tsCore;
		// SET HEADERS
		$sender = $tsCore->settings['titulo']." <no-reply@".$tsCore->settings['domain'].">";
		//
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
		$headers .= "Content-Transfer-Encoding: 8bit\r\n";
		$headers .= "X-Priority: 1\n"; // Urgent message!
		$headers .= "From: {$sender}\r\n";
		$headers .= "Return-Path: {$sender}\r\n";
		$headers .= "Reply-To: {$sender}\r\n";
		//
		return $headers;
	}
	/*
		setEmailBody()
	*/
	function setEmailBody(){
		switch($this->email_info['ref']) {
			case 'signup' :
				return $this->setRegisterEmail();
			break;
		}
	}
}