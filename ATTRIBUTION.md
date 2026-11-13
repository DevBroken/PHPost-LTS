# Atribución, créditos y marcas

Este documento complementa [`LICENSE`](LICENSE) (MIT) y explica qué se espera
al redistribuir, modificar o desplegar PHPost Risus LTS. **No modifica ni
restringe los permisos que otorga la licencia MIT**: separa lo que es una
obligación legal de lo que es una expectativa del proyecto.

---

## Titulares del copyright

| Periodo | Titular |
| --- | --- |
| 2012-2022 | PHPost (proyecto original) |
| 2022-2027 | DevBroken y colaborador/es de PHPost Risus LTS |

El fork LTS conserva los copyrights originales porque la licencia de partida
era permisiva y así lo exige su propio aviso.

---

## Qué exige la licencia (vinculante)

MIT es una licencia permisiva: permite usar, copiar, modificar, fusionar,
publicar, distribuir, sublicenciar y vender el software, siempre que se cumpla
una única condición:

> El aviso de copyright y el texto de la licencia deben incluirse en todas las
> copias o partes sustanciales del Software.

En la práctica esto significa:

- No eliminar el archivo `LICENSE` de las copias que redistribuyas.
- No eliminar las cabeceras `@author` / `@copyright` de los archivos de código
  que redistribuyas.
- Si publicas una versión modificada, deja claro que es una versión modificada.

La licencia **no** exige publicar tus cambios, ni pide nada a cambio del uso
comercial o privado.

---

## Créditos visibles en el pie de página

El proyecto pide —sin que sea una condición de la licencia— que se conserven
los créditos visibles de la parte pública del sitio.

MIT otorga el derecho a usar el Software "sin restricción", por lo que **no es
legalmente posible convertir esos créditos en una obligación adicional**. El
texto que figuraba antes en la licencia, que autorizaba a los mantenedores a
"auditar o restaurar visualmente" los créditos en instalaciones de terceros, se
retiró: implicaba acceso remoto no consentido a sistemas ajenos y no era
exigible.

Si vas a quitar los créditos visibles, la vía correcta es la de las marcas
(sección siguiente): cambia también el nombre y la identidad visual del sitio.

---

## Marcas

"PHPost Risus LTS", el logotipo y los badges del
proyecto son marcas de sus mantenedores.

La licencia MIT **no concede derechos de marca**. En consecuencia:

- Puedes bifurcar y modificar el código libremente.
- No puedes presentar tu fork como la versión oficial del proyecto.
- Si eliminas los créditos visibles, debes usar otro nombre, otro logotipo y
  otra identidad visual para no generar confusión sobre el origen.

Los iconos de terceros incluidos en `frontend/assets/images/` conservan las
condiciones de sus autores originales.

---

## Componentes de terceros

El proyecto incluye bibliotecas de terceros en `inc/libs/`. Sus licencias se
aplican a esos archivos y **prevalecen** sobre la licencia MIT del proyecto:

| Componente | Versión | Licencia | Ubicación |
| --- | --- | --- | --- |
| Smarty | 5.x | LGPL-3.0-or-later | `inc/libs/Smarty` |
| PHPMailer | 5.5 | LGPL-2.1 | `inc/libs/PHPMailer` |
| jBBCode | — | MIT | `inc/libs/JBBCode` |

Si redistribuyes el proyecto, conserva los avisos de copyright de cada
biblioteca tal como vienen en sus archivos.

---

## Cómo declarar la licencia en archivos nuevos

Para que las herramientas modernas (REUSE, SPDX, GitHub, escáneres de
dependencias) identifiquen la licencia sin ambigüedad, añade el identificador
SPDX en la cabecera del archivo:

```php
<?php

/*
 * SPDX-FileCopyrightText: 2027 DevBroken and PHPost Risus LTS contributors
 * SPDX-License-Identifier: MIT
 */
```

Identificadores útiles:

- `MIT` para el código propio del proyecto.
- `LGPL-3.0-or-later` para Smarty.
- `LGPL-2.1` para PHPMailer.

---

## Aviso

Este documento describe la política del proyecto y no constituye asesoramiento
legal. Si necesitas una opinión vinculante sobre una redistribución concreta,
consulta a un profesional.
