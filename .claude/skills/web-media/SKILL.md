---
name: web-media
description: Choisit le format, la compression et le chargement de chaque image, police ou vidéo selon son rôle, et convertit les fichiers pour le web. À utiliser pour ajouter un visuel, un logo, une photo, une police, une vidéo, ou alléger une page.
---

# Médias pour le web

Source : `spec-prepare-web-media.md` et `politique-assets.md` de CapsuleOS. Principe : une politique par rôle, pas une conversion aveugle.

## Profils par rôle

| Rôle | Sortie | Réglage | Garder la source |
|------|--------|---------|------------------|
| Photo | WebP (ou AVIF) | qualité ~82 | non, après mise à jour des liens |
| Visuel de marque, hero | WebP | qualité ~90 | non |
| Icône raster | WebP sans perte | lossless | si la sortie dépasse 105 % de la source |
| Logo, pictogramme, illustration vectorielle | SVG | optimisation prudente (SVGO sans fusion de tracés) | non concerné |
| Capture servant de preuve ou de comparaison | PNG | inchangé | oui |
| Police | WOFF2 sous-ensemble | `font-display: swap` | oui (source TTF hors du site publié) |
| Vidéo | MP4 H.264 + WebM, poster WebP | `preload="none"` | oui |

- Jamais en sortie : JPEG XL, TIFF, BMP. AVIF seulement si le gain dépasse ~20 % face au WebP.
- Toujours la taille affichée : `srcset`/`sizes` ou composant d'image du framework (Astro : `astro:assets`, `<Image>`/`<Picture>`).
- Une image est fournie au double de sa taille d'affichage maximale (`srcset` 1× et 2×), jamais agrandie au-delà de sa source, et contrôlée en capture à taille réelle.
- Logo de partenaire petit ou de mauvaise qualité : chercher une version officielle nette sur internet (SVG de préférence : Wikimedia Commons, site du partenaire), vérifier qu'elle ne contient aucun script, noter sa source ; rogner le blanc autour pour égaliser le poids visuel.
- Le logo n'a qu'un emplacement principal : l'en-tête (plus le favicon). Pas de logo répété dans le héros ni le pied de page.
- Ne jamais remplacer du texte par une image de texte.

## Rangement

- Médias du site (logo, icônes, visuels de marque) et contenus éditoriaux (photos d'articles, produits) dans deux dossiers distincts, chacun à un seul endroit.
- Jamais d'asset inventé, généré pour combler un manque ou emprunté à un autre projet : l'emplacement reste vide et la pièce manquante est signalée.
- Captures d'écran de travail, maquettes et preuves de recette hors du dossier publié.
- Crédits et licences des images, polices et icônes notés à côté du fichier ou dans les mentions légales.

## Commandes

```bash
cwebp -q 82 photo.jpg -o photo.webp
cwebp -lossless icone.png -o icone.webp
avifenc --min 20 --max 30 photo.jpg photo.avif
npx svgo --multipass logo.svg   # svgo verrouillé dans le package.json du projet
pyftsubset Police.ttf --flavor=woff2 --layout-features='*' --unicodes='U+0000-00FF,U+0131,U+0152-0153,U+2018-201E,U+2026,U+20AC' --output-file=police.woff2
ffmpeg -i video.mov -c:v libx264 -crf 26 -preset slow -an -movflags +faststart video.mp4
ffmpeg -i video.mp4 -vf "scale=-2:720" -c:v libx264 -crf 28 -preset slow -c:a aac -b:a 96k -movflags +faststart video-720.mp4   # vidéo lourde (plusieurs centaines de Mo) ramenée à quelques Mo par minute
ffmpeg -i video.mp4 -ss 3 -frames:v 1 -q:v 3 poster.jpg   # image d'attente
node .claude/skills/web-media/scripts/logos-recadres.mjs <logos> <sortie>   # rangée de logos : marges vides retirées, même surface visuelle (logos.json)
```

- ffmpeg vient de `make deps` (paquet système). Celui que fournit Playwright n'encode pas en H.264 : il ne sert qu'à filmer les tests.
- Rangée de logos (partenaires, financeurs, labels) : toujours passée par `logos-recadres.mjs` ; chaque logo s'affiche à la hauteur de `logos.json` (un logo large est moins haut), jamais tous à la même hauteur brute. Un logo dont le fichier garde du blanc autour paraît plus petit que les autres.
- Une vidéo ou une galerie prévue par la maquette mais pas encore prête : la page ne se présente pas (skill `web-contenu`) ; `check-mise-en-page.mjs` bloque une vidéo sans source, une image cassée ou un bloc média vide.

Après conversion : mettre à jour les références (HTML, CSS, données), puis relancer le build et `check-dist.mjs` pour détecter un lien cassé ou une image sans dimensions.

## Image de partage et icônes

Fichiers générés depuis une source, jamais retouchés à la main (rule `web-method`) :

- `src/partage/og.html` : page de 1200 × 630 avec les textes et visuels réels du site (nom, accroche de `site.json`, photo fournie), styles du site. Sortie : `public/og.jpg` (JPEG qualité 85 : le WebP n'est pas lu par tous les réseaux).
- `src/partage/logo.svg` : logo carré sur fond plein. Sorties : `favicon.svg`, `apple-touch-icon.png` (180), `icon-192.png`, `icon-512.png`.
- Commande : `make images` (ou `node .claude/skills/web-media/scripts/images-partage.mjs --og … --logo …`), puis build et `check-dist.mjs` (dimensions de `og:image` contrôlées). Le gabarit `Base.php` du modèle pose les balises d'icônes dès que les fichiers existent.
- Sans logo ni visuel fourni : pas d'image de partage plutôt qu'une image inventée.

## Chargement

- Image LCP : `loading="eager"`, `fetchpriority="high"`, pas de squelette par-dessus, au plus une précharge.
- Autres : `loading="lazy"`, `decoding="async"`, `width`/`height` ou `aspect-ratio`.
- Emplacement prévu sans média réel : cadre aux dimensions finales, vide et sans animation, jamais un visuel factice.
