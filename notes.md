## MTG Viewer - Nicka Ratovobodo
### 0. Complétion de l'import 
- ajout de l'option limit dans la commande d'importation des cartes :
docker compose run --rm php php bin/console import:card --limit=10000
- j'ai augmenté la mémoire à 2Go dans ApiCardController.php pour pouvoir afficher toutes les cartes

### 1. Ajout des logs
a. J'ai ajouté un listener de log en php ApiRequestListener.php, puis je l'ai ajouté au service.yaml. Les logs sont stockés dans le dossier var/logs/app.dev.log
