# cyber-noir-game

Start your docker.

#### Get the stack ready:

`docker compose up -d`

gives you  

 ✔ Network cyber-noir-game_cyber-noir-network  Created  
 ✔ Container cyber-noir-game-mailpit-1         Started  
 ✔ Container cyber-noir-game-mysql-1           Started  
 ✔ Container cyber-noir-game-backend-1         Started  
 ✔ Container cyber-noir-game-redis-1           Started  
 ✔ Container cyber-noir-game-nginx-1           Started  

#### Get Laravel 
`docker exec cyber-noir-game-backend-1 composer install`

#### Install Cyber Noir 
`docker exec cyber-noir-game-backend-1 php artisan migrate --seed`

#### Install Vue
`cd frontend`  
`nvm use 20`  
`npm i`  

#### Start Cyber Noir
`npm run dev` 


#### Reset password
`http://localhost:3000/forgot-password` 

> test@example.com

#### Check your mail
`http://localhost:8025/` 

#### Login

![](https://raw.githubusercontent.com/bespired/cyber-noir-game/refs/heads/main/docker/dashboard.png)