# Contenus attendus du client

Ce fichier liste ce qu'il reste à recevoir ou à confirmer avant de finaliser le site.
Rien ne doit être inventé : tant qu'un élément manque, la zone correspondante reste masquée ou neutre.
Les repères `TODO (client)` dans le code (`index.html`, `chantiers-recents.html`, `tracking.js`) renvoient à cette liste.

## 1. Présentation du dirigeant (`index.html`, section `#entreprise`)

- [ ] Photo du dirigeant : carrée, 400 x 400 px minimum, visage bien visible, à déposer dans `images/`.
- [ ] Confirmer le prénom affiché (actuellement « Yann »), son rôle exact et la phrase de présentation.
- [ ] Remplacer l'initiale par la vraie photo (le modèle `<img>` est dans le commentaire `TODO (client)` du bloc).

## 2. Chantiers avant / après (`chantiers-recents.html`, section `#avant-apres`)

Pour chaque chantier :

- [ ] Photo « avant » et photo « après » (mêmes angles si possible).
- [ ] Commune, type de travaux et durée réels.
- [ ] Accord du client pour publier ses photos.
- [ ] Aucun prix ne doit apparaître.

Le modèle à copier est dans le commentaire `TODO (client)` de la page. Une fois la première série ajoutée, retirer l'attribut `hidden` de la section.

## 3. Identifiants Google

- [ ] Identifiant de suivi (Google Tag Manager `GTM-…` ou Google Analytics 4 `G-…`) : à renseigner dans `window.RTB_TRACKING` (`gtmId` / `ga4Id`), voir `tracking.js`. Sans identifiant, aucun suivi n'est chargé.
- [ ] Fiche Google de l'entreprise (identifiant de lieu ou lien direct vers les avis), pour remplacer la recherche Google Maps actuellement utilisée dans le lien « Voir les avis sur Google ».

## 4. Avis clients

- Un seul avis est affiché pour l'instant (celui déjà présent sur la fiche Google). Il n'y en a pas d'autre à ajouter pour le moment.
- [ ] Si le client en obtient d'autres : avis réels uniquement, avec l'accord de leurs auteurs (bloc `<article class="review-card">` à copier).

## 5. Assurance décennale

- [ ] Confirmer, avec l'attestation MAAF en cours de validité, les activités réellement couvertes, puis valider le texte de l'encadré « Garantie décennale MAAF Pro : ce qu'elle couvre » (`index.html`, section `#prestations`). Aucun montant ne doit être mentionné.

## 6. Contacts

- [ ] Confirmer que le 06 81 32 79 05 est joignable sur WhatsApp. Si oui, le lien reste tel quel ; sinon retirer le bouton WhatsApp (liste des contacts et lien sous le formulaire).
