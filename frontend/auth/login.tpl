<div class="auth-screen">
    <div class="auth-hero-side">
        <div class="brand-logo">PHPost</div>
        <div class="hero-quote">
            <p>"La comunidad se construye un hilo a la vez."</p>
        </div>
        <div class="hero-footer text-sm" style="opacity: 0.7;">© {$smarty.now|date_format:'Y'} PHPost LTS. Todos los derechos reservados.</div>
    </div>

    <div class="auth-main-side">
        <div class="auth-container flow-lg">
            <div class="auth-header flow-sm">
                <h1>Bienvenido de vuelta</h1>
                <p>Ingresa a tu cuenta para continuar.</p>
            </div>

            <form method="POST" class="flow-md" name="form_login_user">
                <div class="form-group">
                    <label class="form-label" for="username">Nombre de usuario o Email</label>
                    <input class="form-input" type="text" id="username" name="nickname" placeholder="ej. jhonDoe" required>
                </div>

                <div class="form-group">
                    <div class="flex-row-center">
                        <label class="form-label" for="password">Contraseña</label>
                        <span role="button" data-action="password" class="auth-link text-sm">¿La olvidaste?</span>
                    </div>
                    <input class="form-input" type="password" id="password" name="password" autocomplete="current-password" placeholder="••••••••" required>
                </div>

                <div class="form-group phpost_tls_test">
                    <label for="artscript">Si ves esto, déjalo en blanco:</label>
                    <input type="text" id="artscript" name="artscript" autocomplete="off">
                </div>

                <div class="flex-row-center">
                    <label class="choice-control" for="remember-me">
                        <input type="checkbox" id="remember-me" name="remember">
                        <span>Recordarme en este dispositivo</span>
                    </label>
                </div>

                <button type="submit" data-variant="primary">Iniciar sesión</button>
            </form>

            <div class="text-center text-sm" style="color: var(--color-text-muted);">
                <p><span role="button" data-action="validation" class="auth-link text-sm">¿No llegó el correo de validación?</span></p>
                ¿No tienes una cuenta? <a href="{$tsConfig.url}/auth/register" class="auth-link">Regístrate aquí</a>
            </div>
        </div>
    </div>
</div>