# Informe de Desarrollo

## Autor
**Bastian Tobar Mori**

## Asignatura
**PHP**

## Fecha
**31/12/2024**

---

## Resumen del Trabajo

### Ajustes Iniciales
Para arrancar la aplicación fue necesario realizar un pequeño ajuste en la entidad `User`, específicamente en el atributo `roles`, ya que la especificación inicial presentaba problemas al momento de crear un usuario (`User`). Además, debido a restricciones en mi ordenador, todas las peticiones se ejecutaron utilizando HTTP en lugar de HTTPS.

### Curva de Aprendizaje
Como era la primera vez que trabajaba con Symfony, enfrenté una curva de aprendizaje adicional. Durante el desarrollo, utilicé los siguientes comandos para solucionar problemas y configurar el entorno de trabajo:

- `php bin/console cache:clear` – Para limpiar la caché.
- `php bin/console debug:router` – Para depurar las rutas definidas.
- `symfony server:stop` – Para detener el servidor Symfony.
- `symfony server:start` – Para iniciar nuevamente el servidor Symfony.

---

## Desarrollo de Servicios REST
Una vez resueltos los ajustes iniciales y configurado correctamente el entorno, procedí a desarrollar los servicios REST para la entidad `Result`. Estos servicios están documentados y especificados en el cliente Swagger.
[Documentación Swagger](http://localhost:8000/api-docs/index.html)