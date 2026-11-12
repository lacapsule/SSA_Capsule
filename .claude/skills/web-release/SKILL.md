---
name: web-release
description: Prépare et vérifie une mise en production de site (FTP/SFTP mutualisé, rsync, plateforme) - paquet construit depuis un commit propre de main, liste des fichiers publiés, contrôle de dérive, envoi validé par l'humain, vérifications et retour arrière. À utiliser avant tout déploiement ou pour savoir si la production est à jour.
---

# Mise en production

Source : `procedure-mise-en-production.md` de CapsuleOS (Hostinger). Principe : automatiser la détection, pas la publication.

## 1. Règles

- Le paquet publié est la photo d'un commit précis de `main`, arbre propre, gates vertes. Jamais construit depuis une branche de travail ou un arbre modifié : il aurait l'air à jour sans correspondre à aucune version.
- Le paquet est une copie figée (pas de liens symboliques ni de liens durs vers le dépôt).
- Liste blanche de ce qui part en ligne (le dossier de build, `.htaccess`, `robots.txt`…) ; tout le reste est exclu par défaut.
- L'envoi vers le serveur est une action irréversible vers l'extérieur : validation humaine explicite à chaque fois.
- Pas de déploiement déclenché par l'horloge. Un contrôle quotidien peut seulement signaler « il y a du neuf » ou « la prod est en retard ».

## 2. Construire depuis un commit propre

```bash
git fetch origin
REF=origin/main
DIR="$(mktemp -d)/release"
git worktree add --detach "$DIR" "$REF"
( cd "$DIR" && make verify )        # socle Astro : npm ci && npm run build
git -C "$DIR" rev-parse --short HEAD > "$DIR/dist/.release"   # à exclure de l'envoi si non voulu en ligne
```

Puis la recette (skill `web-audit`) sur `"$DIR/dist"`. Le manifeste de version (commit, date, auteur) reste à côté du paquet, pas forcément en ligne.

## 3. Envoyer (après accord)

```bash
# SFTP/rsync : miroir exact du dossier construit
rsync -avz --delete --exclude='.release' "$DIR/dist/" user@hote:/chemin/public_html/
```

Hébergeur sans SSH (Hostinger, OVH mutualisé) : miroir `lftp`, d'abord à blanc, liste relue par l'humain, puis envoi réel après son accord :

```bash
set -a; . ~/.config/kouzia/<depot>/deploiement.env; set +a     # FTP_HOTE, FTP_UTILISATEUR, FTP_MOT_DE_PASSE, FTP_DOSSIER
ESSAI=--dry-run   # vide après accord
lftp -u "$FTP_UTILISATEUR","$FTP_MOT_DE_PASSE" "sftp://$FTP_HOTE" -e "set ftp:ssl-force true; mirror --reverse --delete --verbose $ESSAI --exclude-glob .release \"$DIR/dist/\" \"$FTP_DOSSIER\"; quit"
```

`sftp://` si l'offre le permet, sinon `ftp://` avec `ftp:ssl-force` (jamais de FTP en clair). Sans `lftp` : FileZilla en mode miroir, même relecture. Ne jamais envoyer `node_modules`, sources, `.env`, `.git`.

### Accès

- Rangés hors du dépôt : `~/.config/kouzia/<depot>/deploiement.env` (dossier 700, fichier 600) ou gestionnaire de mots de passe de l'humain. Le mode d'emploi dit où, jamais la valeur.
- L'agent ne lit pas ce fichier (lecture refusée par `settings.json`) : il le charge dans la commande d'envoi, que l'humain valide.
- Un accès donné dans le chat est rangé là puis signalé comme à changer (il est passé par la conversation).

## 4. Après l'envoi

1. Application avec base de données : lancer ses migrations (`php scripts/migrate.php`, ou le bouton Scripts du hub), lire la liste affichée et le code de sortie 0, avant tout autre contrôle. Puis vérifier que les pages publiques répondent 200 : accueil, boutique ou page principale, une page légale, feuille générée s'il y en a une.
2. `bash .claude/skills/web-audit/scripts/check-headers.sh https://domaine`
3. Parcours principal à la main sur mobile et ordinateur, console sans erreur.
4. Formulaire : un envoi réel de test reçu.
5. Search Console et Bing Webmaster Tools : sitemap soumis, pas de nouvelle erreur de couverture (skill `web-seo-structured-data` § 9).
6. `git tag release-AAAA-MM-JJ <commit>` (après accord), pour savoir ce qui est en ligne.

## 5. Retour arrière

Garder le paquet précédent (ou son tag). En cas de régression : renvoyer le paquet précédent, puis corriger sur une branche. Ne jamais corriger directement sur le serveur.

## 6. Contrôle de dérive

Question unique : la production correspond-elle au dernier commit de `main` ?

```bash
curl -s https://domaine/.release   # si publié
git rev-parse --short origin/main
```

Égal : rien à faire. Différent : préparer une publication (sections 2 à 4).
