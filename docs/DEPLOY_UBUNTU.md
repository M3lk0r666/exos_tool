# Despliegue en producción — Ubuntu 22.04/24.04 + Apache + MySQL

## 0. Publicar el proyecto en GitHub (desde tu equipo de desarrollo)

El repositorio local aún apunta al template original. Crea el repositorio
**exos_tool** en GitHub (vacío, sin README) y apunta el remote:

```bash
cd C:\xampp\htdocs\laravel\exos_tool
git remote set-url origin git@github.com:TU_USUARIO/exos_tool.git
git add -A
git commit -m "EXOS-Tool: fases 1-8 completas"
git branch -M main
git push -u origin main
```

> **⚠️ Repositorio PRIVADO obligatorio:** `ejemplos-tech/` y `tests/Fixtures/`
> contienen tech-supports **reales de clientes** (hostnames, seriales, MACs,
> logs). Son necesarios para los tests golden-file, así que mantén el repo
> privado o reemplázalos por versiones anonimizadas antes de publicar.
> `.env`, `vendor/`, `node_modules/` y `storage/` ya están excluidos por
> `.gitignore` — nunca los fuerces al repo.

Para despliegues sin escribir credenciales en el servidor, crea una
**Deploy Key** de solo lectura:

```bash
# En el servidor Ubuntu:
sudo -u www-data ssh-keygen -t ed25519 -f /var/www/.ssh/exos_deploy -N ""
cat /var/www/.ssh/exos_deploy.pub
```

Copia esa llave pública en GitHub → repo exos_tool → **Settings → Deploy keys →
Add deploy key** (sin marcar "write access"), y configura ssh:

```bash
sudo -u www-data tee /var/www/.ssh/config <<'EOF'
Host github.com
    IdentityFile /var/www/.ssh/exos_deploy
    StrictHostKeyChecking accept-new
EOF
```

(Alternativa simple para repos privados sin SSH: clonar por HTTPS con un
Personal Access Token de solo lectura: `https://TOKEN@github.com/TU_USUARIO/exos_tool.git`.)

## 1. Paquetes del sistema

```bash
sudo apt update
sudo apt install -y apache2 mysql-server git unzip \
    php8.3 php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl libapache2-mod-php8.3
sudo a2enmod rewrite headers
# Composer y Node 20+
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install -y nodejs
```

> `php-gd` es necesario para DomPDF (logos en el PDF); `php-zip` para maatwebsite/excel.

## 2. Base de datos

```bash
sudo mysql
CREATE DATABASE exos_tool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'exos'@'localhost' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE';
GRANT ALL PRIVILEGES ON exos_tool.* TO 'exos'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Código y dependencias

```bash
sudo mkdir -p /var/www/exos_tool && sudo chown $USER /var/www/exos_tool
git clone git@github.com:TU_USUARIO/exos_tool.git /var/www/exos_tool
cd /var/www/exos_tool
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

## 4. Configuración

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` (mínimo):

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://exos.tudominio.com
DB_DATABASE=exos_tool
DB_USERNAME=exos
DB_PASSWORD=CAMBIA_ESTA_CLAVE
QUEUE_CONNECTION=database
MAIL_MAILER=smtp        # + credenciales SMTP para notificaciones
SESSION_SECURE_COOKIE=true
```

```bash
php artisan storage:link
php artisan migrate --seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Importante:** elimina o cambia la contraseña de los usuarios demo
(`admin@exostool.local`, etc.) inmediatamente después del primer login.

## 5. Permisos

```bash
sudo chown -R www-data:www-data /var/www/exos_tool/storage /var/www/exos_tool/bootstrap/cache
sudo chmod -R 775 /var/www/exos_tool/storage /var/www/exos_tool/bootstrap/cache
```

Los tech-support originales quedan en `storage/app/private/captures/` y los PDFs en
`storage/app/private/reports/` — inclúyelos en el respaldo junto con la BD.

## 6. Apache

Copiar `deploy/apache-exos-tool.conf` a `/etc/apache2/sites-available/`, ajustar
`ServerName`, y:

```bash
sudo a2ensite apache-exos-tool.conf && sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

Ajustar en `/etc/php/8.3/apache2/php.ini` (subida de archivos grandes):

```
upload_max_filesize = 60M
post_max_size = 64M
memory_limit = 512M
```

Para HTTPS usa certbot: `sudo apt install certbot python3-certbot-apache && sudo certbot --apache`.

## 7. Queue worker (procesamiento de análisis)

Copiar `deploy/exos-tool-queue.service` a `/etc/systemd/system/` y:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now exos-tool-queue
sudo systemctl status exos-tool-queue
```

> Tras cada despliegue de código ejecuta `sudo systemctl restart exos-tool-queue`
> (el worker mantiene el código en memoria).

## 8. Actualizaciones

```bash
cd /var/www/exos_tool
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl restart exos-tool-queue
php artisan up
```

## 9. Verificación post-despliegue

1. Login con un usuario real; verificar dashboard.
2. Subir un tech-support de prueba → estado "Completado" (confirma que el worker corre).
3. Generar y emitir un reporte → descargar el PDF (confirma DomPDF y logos).
4. Probar la API: `curl -H "Authorization: Bearer <token>" https://.../api/v1/clients`.
5. Revisar `storage/logs/laravel.log` sin errores.
