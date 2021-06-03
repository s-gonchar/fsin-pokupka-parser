Развернуть сервис
````
1) git clone https://github.com/sgonchar67-git/fsin-pokupka-parser.git
2) cd docker
3) docker-compose up -d --build
4) docker-compose exec php bash
5) composer install
6) vendor/bin/doctrine orm:schema-tool:create
````
Запустить крон:

````
docker-compose exec php bash
cd cron
php parse.php
````