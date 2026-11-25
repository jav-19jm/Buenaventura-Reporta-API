# Guía de contribución · Buenaventura Reporta (Buenaventura-Reporta-API)

Esta guía explica cómo trabajamos en el equipo: qué ramas usamos, cómo llega un cambio a producción y qué revisar antes de pedir un merge. Seguirla evita conflictos y mantiene `main` siempre desplegable.

## Contenido

- [Estrategia de ramas](#estrategia-de-ramas)
- [Flujo de trabajo paso a paso](#flujo-de-trabajo-paso-a-paso)
- [Mensajes de commit](#mensajes-de-commit)
- [Pull requests](#pull-requests)
- [Antes de abrir un pull request](#antes-de-abrir-un-pull-request)
- [Integración y despliegue continuo](#integración-y-despliegue-continuo)
- [Qué nunca se sube al repositorio](#qué-nunca-se-sube-al-repositorio)
- [Problemas frecuentes](#problemas-frecuentes)

---

## Estrategia de ramas

```
develop-jose  ──┐
develop-jesus ──┼──PR──►  develop  ──PR──►  main  ──►  producción
develop-sebas ──┘        (integración)    (estable)   (despliegue automático)
```

| Rama | Propósito | Quién escribe en ella | Cómo recibe cambios |
|---|---|---|---|
| `main` | Código en **producción**. Cada merge se despliega solo en <https://buenaventura-reporta.tech> | Nadie directamente | Solo por PR desde `develop` |
| `develop` | **Integración**: reúne el trabajo de todos y debe compilar y pasar las pruebas siempre | Nadie directamente | Solo por PR desde las ramas personales |
| `develop-<nombre>` | Rama **personal** de trabajo de cada integrante | Su dueño | Commits directos del integrante |

Ramas personales actuales:

| Integrante | Rama |
|---|---|
| Jose Manuel Bonilla Payan | `develop-jose` |
| Jesus Eduardo Estupiñan Hernandez | `develop-jesus` |
| Sebastian Gomez Lerma | `develop-sebas` |

**Reglas:**

1. **Nunca** se hace push directo a `main` ni a `develop`: todo entra por pull request.
2. Cada quien trabaja **solo en su rama** `develop-<nombre>`.
3. La rama personal se **actualiza con `develop`** antes de empezar algo nuevo y antes de abrir un PR.
4. Un PR trata **un solo tema** (una funcionalidad, una corrección). Si mezcla cosas, se divide.

## Flujo de trabajo paso a paso

### 1. Primera vez: crear tu rama personal

```bash
git clone https://github.com/jav-19jm/Buenaventura-Reporta-API.git
cd Buenaventura-Reporta-API
git switch develop
git switch -c develop-<nombre>          # p. ej. develop-jose
git push -u origin develop-<nombre>
```

Si tu rama ya existe en GitHub: `git switch develop-<nombre>`.

### 2. Antes de empezar una tarea: ponerte al día con `develop`

```bash
git switch develop-<nombre>
git fetch origin
git merge origin/develop                # trae lo que integraron los demás
```

Si hay conflictos, resuélvelos en tu rama, nunca en `develop` ni en `main`.

### 3. Trabajar y hacer commits

```bash
git add <archivos>
git commit -m "feat(reportes): permitir adjuntar varias fotos"
git push
```

Haz commits pequeños y frecuentes. Cada push a tu rama ejecuta las pruebas en Jenkins.

### 4. Abrir el pull request a `develop`

1. Ponte al día con `develop` (paso 2) y comprueba que todo pasa ([lista previa](#antes-de-abrir-un-pull-request)).
2. En GitHub: **Pull requests → New pull request**, con **base: `develop`** ← **compare: `develop-<nombre>`**.
3. Llena la plantilla que aparece automáticamente.
4. Asigna como revisor a otro integrante.
5. Espera el ✅ de Jenkins y la aprobación. Luego haz **merge** (preferible *Squash and merge* si tiene muchos commits pequeños).

### 5. Publicar en producción: PR de `develop` a `main`

Cuando `develop` tiene un conjunto de cambios probado y listo para los usuarios:

1. **Pull requests → New pull request**, con **base: `main`** ← **compare: `develop`**.
2. Título con la versión o el resumen, p. ej. `Versión 1.3: chat en tiempo real y mejoras del mapa`.
3. En la descripción, la lista de cambios que incluye.
4. Con el ✅ de Jenkins y la aprobación, **merge** (*Create a merge commit*, para conservar el historial).
5. Jenkins construye y despliega solo. Verifica la aplicación en <https://buenaventura-reporta.tech>.

> **Corrección urgente en producción:** sigue el mismo camino (rama personal → `develop` → `main`), marcando el PR como urgente para revisarlo de inmediato. Así `develop` y `main` nunca se separan.

## Mensajes de commit

Usamos [Conventional Commits](https://www.conventionalcommits.org/es/), en español y en modo imperativo:

```
<tipo>(<área opcional>): <descripción corta en minúscula>
```

| Tipo | Cuándo | Ejemplo |
|---|---|---|
| `feat` | Nueva funcionalidad | `feat(chat): mostrar quién está escribiendo` |
| `fix` | Corrección de un error | `fix(auth): renovar el token al expirar` |
| `refactor` | Cambio interno sin alterar el comportamiento | `refactor(reportes): extraer el cálculo de prioridad` |
| `test` | Pruebas nuevas o corregidas | `test: cubrir el voto duplicado` |
| `docs` | Documentación | `docs: explicar las variables de entorno` |
| `style` | Formato, sin cambios de lógica | `style: aplicar el formateador` |
| `chore` / `ci` | Dependencias, configuración, pipeline | `ci: guardar el reporte de pruebas` |

## Pull requests

- **Título** con el mismo formato que los commits: `feat(mapa): agrupar reportes cercanos`.
- **La plantilla es obligatoria**: describe qué cambia, por qué y cómo probarlo. Si cambia la interfaz, agrega capturas.
- **Al menos una aprobación** de otro integrante antes del merge.
- **El check de Jenkins debe estar en verde.** Si falla, abre el enlace *Details*, revisa la etapa que falló y corrige en tu rama: el PR se actualiza solo con cada push.
- **Revisar también es parte del trabajo:** comenta con respeto y de forma concreta. Si algo no está claro, pregunta.
- Después del merge, actualiza tu rama personal con `develop` (paso 2).

## Antes de abrir un pull request

Ejecuta en tu equipo y comprueba que todo pasa:

```bash
composer install
php artisan test               # suite completa (SQLite en memoria)
./vendor/bin/pint --test       # estilo de código (sin --test, lo corrige)
```

- [ ] Las pruebas pasan.
- [ ] Si agregaste o cambiaste un endpoint, agregaste pruebas en `tests/Feature/` y actualizaste la referencia OpenAPI del repositorio de documentación.
- [ ] Las migraciones nuevas son archivos **nuevos**: nunca se modifica una migración que ya está en `main`.
- [ ] Las rutas nuevas tienen el middleware de autenticación, rol y límite de peticiones que les corresponde.
- [ ] No quedaron `dd()`, `dump()` ni registros de depuración.

## Integración y despliegue continuo

Cada push y cada pull request ejecutan el pipeline de Jenkins definido en el [`Jenkinsfile`](Jenkinsfile):

| Etapa | Ramas personales, `develop` y PR | `main` |
|---|---|---|
| Checkout | ✅ | ✅ |
| Build | ✅ | ✅ |
| Test | ✅ | ✅ |
| Docker Build | — | ✅ |
| Delivery (despliegue a producción) | — | ✅ |

Si el despliegue falla la comprobación de salud, Jenkins **restaura automáticamente la versión anterior**: producción no se queda caída por un merge.

## Qué nunca se sube al repositorio

- Archivos `.env` ni ningún otro con contraseñas, tokens o claves. Si añades una variable nueva, ponla **vacía** en `.env.example` y avisa al equipo.
- Respaldos o volcados de la base de datos (`*.sql`, `*.dump`): contienen datos personales.
- `vendor/`, `storage/logs/` ni archivos subidos por los usuarios.

> Si subiste un secreto por error, **avisa de inmediato**: hay que revocarlo y generar uno nuevo. Borrarlo en otro commit no basta, porque queda en el historial.

## Problemas frecuentes

| Situación | Solución |
|---|---|
| Mi PR tiene conflictos | En tu rama: `git fetch origin && git merge origin/develop`, resuelve, haz commit y push |
| Hice commits en `develop` por error (sin push) | `git switch -c develop-<nombre>-temp`, vuelve a `develop` con `git switch develop` y ejecuta `git reset --hard origin/develop`; luego lleva los commits a tu rama con `git cherry-pick` |
| Jenkins falla pero en mi equipo funciona | Revisa el log de la etapa en Jenkins. Suele ser una dependencia que no quedó registrada en `composer.json` o una prueba que depende de datos locales |
| Necesito el trabajo de un compañero que aún no está en `develop` | Espera su merge a `develop`; no mezcles ramas personales entre sí |

---

¿Dudas sobre el proceso? Pregunta en el grupo del equipo o abre un *issue* en el repositorio.
