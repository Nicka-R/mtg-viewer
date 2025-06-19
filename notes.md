## MTG Viewer - Nicka Ratovobodo
### 0. Complétion de l'import 
- ajout de l'option limit dans la commande d'importation des cartes :
docker compose run --rm php php bin/console import:card --limit=10000
- j'ai augmenté la mémoire à 2Go dans ApiCardController.php pour pouvoir afficher toutes les cartes

### 1. Ajout des logs
a. J'ai ajouté un listener de log en php ApiRequestListener.php, puis je l'ai ajouté au service.yaml. Les logs sont stockés dans le dossier var/logs/app.dev.log
b. Pour l'import, j'ai ajouté des logs dans ImportCardCommand.php pour le début, la fin, la durée et les erreurs de l'import.

### 2. Ajouter la recherche de carte
- creation d'une nouvelle route d'API dans ApiCardController.php qui se limite à 20 résultats : `api/card/search` avec la documentation associée
- développement de la page de recherche `SearchPage.vue` en ajoutant la barre de recherche et en affichant les résultat

## 3. Ajouter des filtres
- j'ai modifié une route d'API qui en plus du nom de la carte, filtre aussi sur son setCode, toujours limité à 20 résultats dans ApiCardController.php : `api/card/search`
- pour afficher les setCode, j'ai créé une route d'API qui récupère tous les setCode : `api/setcodes` que j'utilise dans SearchPage.vue pour créer le select
- Modification du front pour intégrer le select et mettre à jour l'URL si besoin
- exemple : "black lotus" avec le setCode 2ED

