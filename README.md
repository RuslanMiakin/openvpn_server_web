# Веб-интерфейс для OpenVPN

Простой PHP-интерфейс для управления OpenVPN сервером, установленным с помощью [скрипта angristan/openvpn-install](https://github.com/angristan/openvpn-install).

## Особенности

- **Управление пользователями**
  - Добавление новых пользователей с возможностью защиты конфигурации паролем
  - Удаление существующих пользователей
  - Просмотр списка всех пользователей
  - Скачивание .ovpn файлов конфигурации

- **Мониторинг подключений**
  - Отображение активных подключений
  - Информация о подключенных пользователях (IP, время подключения)

- **Статистика**
  - Общее количество пользователей и активных соединений
  - Статус работы сервера
  - Нагрузка на сервер
  - Статистика использования трафика

## Структура файлов

- `index.php` - Основная страница управления
- `functions.php` - Функции для взаимодействия с OpenVPN
- `login.php` - Страница входа в панель управления
- `download.php` - Скрипт для скачивания .ovpn файлов
- `stats.php` - Детальная статистика использования VPN
- `style.css` - Стили для всех страниц
- `.htaccess` - Настройки безопасности веб-сервера

## Установка на Ubuntu с опенVPN в папке root

1. Установите веб-сервер и PHP:
```bash
sudo apt update
sudo apt install apache2 php php-cli apache2-utils
```

2. Скопируйте файлы веб-интерфейса в директорию веб-сервера:
```bash
sudo mkdir -p /var/www/html/openvpn
sudo cp -r web/* /var/www/html/openvpn/
```

3. Настройте права доступа:
```bash
# Создайте группу для доступа
sudo groupadd openvpn-web

# Добавьте пользователя веб-сервера в группу
sudo usermod -a -G openvpn-web www-data

# Настройте доступ к файлам OpenVPN
sudo chgrp -R openvpn-web /etc/openvpn/
sudo chmod -R g+r /etc/openvpn/

# Создайте директорию логов, если она не существует
sudo mkdir -p /var/log/openvpn/
sudo touch /var/log/openvpn/status.log
sudo chgrp openvpn-web /var/log/openvpn/status.log
sudo chmod g+r /var/log/openvpn/status.log

# Настройте права на выполнение скрипта
echo "www-data ALL=(ALL) NOPASSWD: /root/openvpn-install.sh" | sudo tee /etc/sudoers.d/openvpn-web
sudo chmod 440 /etc/sudoers.d/openvpn-web
```

4. Настройте защиту директории с помощью .htaccess:
```bash
# Создайте файл с паролем
sudo htpasswd -c /etc/apache2/.htpasswd admin
```

5. Включите поддержку .htaccess в Apache:
```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```
Добавьте внутри секции `<VirtualHost *:80>`:
```
<Directory /var/www/html/openvpn>
    AllowOverride All
</Directory>
```

6. Включите необходимые модули Apache и перезапустите:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

7. Проверьте работоспособность:
Откройте в браузере http://ваш_сервер/openvpn

## Безопасность

Этот веб-интерфейс должен использоваться с осторожностью, так как он предоставляет доступ к управлению VPN-сервером. Рекомендуется:

1. Всегда размещать его за HTTPS
2. Использовать строгие пароли для аутентификации
3. Ограничить доступ по IP-адресам, если возможно
4. Рассмотреть возможность использования двухфакторной аутентификации

## Настройка для продакшена

Для использования в продакшене рекомендуется:

1. Настроить HTTPS с помощью Let's Encrypt:
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache
```

2. Улучшить обработку ошибок и логирование
3. Использовать базу данных для хранения логов и статистики
4. Настроить регулярное резервное копирование

## Ограничения

- Текущая реализация не имеет расширенных функций мониторинга
- Ограниченные возможности конфигурирования параметров сервера
- Необходимы права sudo для выполнения скрипта OpenVPN 