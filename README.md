
### Setup Aplikasi
composer update / composer install
```
Konfigurasi file .env
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=example_app
DB_USERNAME=root
DB_PASSWORD=
```

Generate key
```
php artisan key:generate
```

Migrate database
```
php artisan migrate
```

Seeder
```
php artisan db:seed
```

php artisan serve