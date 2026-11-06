<div class="auth-screen">
    <div class="auth-hero-side">
        <div class="brand-logo">PHPost</div>
        <div class="hero-quote">
            <p>"Únete a nuestra comunidad hoy mismo."</p>
        </div>
        <div class="hero-footer text-sm" style="opacity: 0.7;">© {$smarty.now|date_format:'Y'} PHPost LTS. Todos los derechos reservados.</div>
    </div>

    <div class="auth-main-side">
        <div class="auth-container flow-lg">
            <div class="auth-header flow-sm">
                <h1>Crea tu cuenta</h1>
                <p>Únete a nuestra comunidad hoy mismo.</p>
            </div>

            {if $tsRegisterOpen}
                <!-- Se cambió el name del formulario a form_register_user -->
                <form method="POST" class="flow-md" name="form_register_user">

                    <!-- Nombre de usuario -->
                    <div class="form-group">
                        <label class="form-label" for="username">Nombre de usuario</label>
                        <input class="form-input" type="text" id="username" name="nickname" pattern="^[a-zA-Z0-9_\-]+$" minlength="4" maxlength="20" placeholder="ej. jhonDoe" autocomplete="nickname" required>
                        <small class="form-helper" data-target="nickname"></small>
                    </div>

                    <!-- Correo electrónico -->
                    <div class="form-group">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input class="form-input" type="email" id="email" name="email" placeholder="jhondoe@example.com" autocomplete="email" required>
                        <small class="form-helper" data-target="email"></small>
                    </div>

                    <!-- Contraseña -->
                    <div class="form-group">
                        <label class="form-label" for="password">Contraseña</label>
                        <input class="form-input" type="password" id="password" name="password" minlength="4" maxlength="32" placeholder="••••••••" autocomplete="new-password" required>
                        <div class="password-show">
                            <img src="{$tsRoutes['assets']['images']}/icons/eye.svg" title="Mostrar contraseña">
                        </div>
                        <div class="password-strength-meter"><div id="strength-bar"></div></div>
                        <small class="form-helper" data-target="password"></small>
                        <ul class="password-requirements">
                            <li id="req-length">Entre 4 y 32 caracteres (Obligatorio)</li>
                            <li id="req-upper">Al menos una letra mayúscula (Opcional)</li>
                            <li id="req-number">Al menus un número (Opcional)</li>
                            <li id="req-special">Al menus un símbolo especial (Opcional)</li>
                        </ul>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="form-group">
                        <label class="form-label" for="password_confirm">Confirmar contraseña</label>
                        <input class="form-input" type="password" id="password_confirm" name="password_confirm" minlength="4" maxlength="32" placeholder="••••••••" autocomplete="confirm-password" required>
                        <small class="form-helper" data-target="password_confirm"></small>
                    </div>

                    <!-- Filtro Honeypot (Anti-bots) -->
                    <div class="form-group phpost_tls_test">
                        <label for="artscript">Si ves esto, déjalo en blanco:</label>
                        <input type="text" id="artscript" name="artscript" autocomplete="off">
                    </div>

                    <!-- Términos y condiciones (Reemplaza a "Recordarme") -->
                    <div class="flex-row-center">
                        <label class="choice-control" for="terms">
                            <input type="checkbox" id="terms" name="terms" required>
                            <span>Acepto los términos y condiciones de la comunidad</span>
                        </label>
                    </div>

                    <input type="hidden" name="response" id="response" class="g-recaptcha">
                    <button type="submit" data-sitekey="{$tsRegisterPKey}" disabled data-variant="primary">Registrarse</button>
                </form>

                <div class="text-center text-sm" style="color: var(--color-text-muted);">
                    ¿Ya tienes una cuenta? <a href="{$tsConfig.url}/auth/login" class="auth-link">Inicia sesión aquí</a>
                </div>
            {else}
                <div class="box-end">
                    <p>El registro se encuentra cerrado por el momento...</p>
                </div>
            {/if}
        </div>
    </div>
</div>
