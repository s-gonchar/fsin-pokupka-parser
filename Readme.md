Развернуть сервис
````
1) git clone https://github.com/sgonchar67-git/fsin-pokupka-parser.git
2) cd docker
3) docker-compose up -d --build
Чуть подождать, должна появиться папка vendor
4) docker-compose exec php bash -c "vendor/bin/doctrine-migrations migrations:migrate"
````
Запустить крон:

````
docker-compose exec php bash
cd cron
php parse.php
````