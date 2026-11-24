#!/bin/bash
set -e

# 1. Setup folders, storage symlink, and full permissions
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/app/private/id_photos \
         /var/www/html/storage/app/public/id_photos \
         /var/www/html/storage/app/public/profile_photos
touch /var/www/html/storage/logs/laravel.log

rm -rf /var/www/html/public/storage
php artisan storage:link --force || ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/storage
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# 2. Initialize and start MySQL (MariaDB)
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB data directory..."
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

echo "Starting MariaDB service..."
service mariadb start

# Wait for MySQL to be ready
until mariadb-admin ping --silent; do
    echo "Waiting for MariaDB..."
    sleep 1
done

# 3. Create database and root user with empty password (matches local Laragon)
echo "Configuring database..."
mariadb -e "CREATE DATABASE IF NOT EXISTS \`techyknowloadeia1\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mariadb -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('') WITH GRANT OPTION;"
mariadb -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED VIA mysql_native_password USING PASSWORD('') WITH GRANT OPTION;"
mariadb -e "FLUSH PRIVILEGES;"

# 4. Run Laravel migrations to set up schema
echo "Running migrations..."
php artisan migrate --force

# 5. Seed default accounts and 42 official inventory medicines
echo "Seeding initial accounts and medicine inventory..."
php artisan db:seed --class=MedicineInventorySeeder --force || true
mariadb techyknowloadeia1 -e "
INSERT IGNORE INTO users (id, first_name, last_name, phone_number, email, role, status, password, created_at, updated_at) VALUES
(1, 'Resident', 'User', '09635254960', NULL, 'resident', 'active', '\$2y\$12\$yVeokU7cfLE4Ap8KPDYSiulbZPpBmnsKBXp58PSgyyww2lTDynFEG', NOW(), NOW()),
(2, 'BHW', 'User', '09635707383', NULL, 'bhw', 'active', '\$2y\$12\$yVeokU7cfLE4Ap8KPDYSiulbZPpBmnsKBXp58PSgyyww2lTDynFEG', NOW(), NOW()),
(3, 'Nurse', 'User', '09640935908', NULL, 'nurse', 'active', '\$2y\$12\$yVeokU7cfLE4Ap8KPDYSiulbZPpBmnsKBXp58PSgyyww2lTDynFEG', NOW(), NOW());
UPDATE users SET phone_number = '09635254960', updated_at = NOW() WHERE id = 1 AND phone_number = '09111111111';
UPDATE users SET phone_number = '09635707383', updated_at = NOW() WHERE id = 2 AND phone_number = '09222222222';
UPDATE users SET phone_number = '09640935908', updated_at = NOW() WHERE id = 3 AND phone_number = '09333333333';
" || true

# 6. Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground
