

# Cyber Noir (v1)

⚠️ This version is archived.

New rewrite:
👉 https://github.com/bespired/nexus-noir



# cyber-noir-game

Start your docker.

`cd cyber-noir-game`

#### Get the stack ready:

create a .env.docker with

`./install.sh`

then:

`docker compose up -d`

gives you

	 ✔ Network cyber-noir-game_cyber-noir-network  Created
	 ✔ Container cyber-noir-game-mailpit-1         Started
	 ✔ Container cyber-noir-game-mysql-1           Started
	 ✔ Container cyber-noir-game-backend-1         Started
	 ✔ Container cyber-noir-game-redis-1           Started
	 ✔ Container cyber-noir-game-nginx-1           Started

#### Get Laravel
`docker compose exec backend composer install`

#### Install Cyber Noir
`docker compose exec backend php artisan migrate:refresh --seed`


#### Install Data and Artwork
`docker compose exec backend php artisan app:artwork-install`
(or use the button in the about page)


#### Install Vue
`cd frontend;`  
`nvm use 20;`  
`npm i;`  

#### Start Cyber Noir
`npm run dev`


#### Reset password ( not really needed )
`http://localhost:3000/forgot-password`

> test@example.com
(has given password testL33uw, unless you change it)


#### Check your mail
`http://localhost:8025/`

#### Login

![](https://raw.githubusercontent.com/bespired/cyber-noir-game/refs/heads/main/docker/about.png)


#### Backup your data and artwork

This is a bit tricky...
If you have google drive credentials you can add them
in the .env.docker file and then artisan app:artwork-backup will work.
Or you could zip the cyber-noir-game/backend/storage/app/public/artwork folder.

`docker compose exec backend php artisan game:artwork-backup`


#### Game Running
`cd electron;`  
`nvm use 20;`  
`npm i;` 

for game testing:  
`npm run dev`  
for game in electron:  
`npm run start`  

