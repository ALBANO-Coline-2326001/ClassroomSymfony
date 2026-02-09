# 🛠 Configuration de l'interpréteur PHP (`php.ini`)

Ce guide explique comment modifier la configuration de ton PHP local pour lever les restrictions sur l'upload de fichiers (PDF, Vidéos) et activer les fonctionnalités de détection de fichiers.

## 1. Trouver le bon fichier `php.ini`
L'interpréteur PHP peut utiliser plusieurs fichiers de configuration. Pour trouver celui que tu dois modifier :
1. Ouvre ton terminal.
2. Tape la commande :
   ```bash
   php --ini


## 2. Activer l'extension Fileinfo

Cette extension est **obligatoire** pour que Symfony puisse deviner le type de fichier (MIME type) que tu télécharges. Sans elle, tu auras une erreur `LogicException`.

1. Cherche la ligne suivante dans ton fichier `php.ini` :
   ```ini
   ;extension=fileinfo
    ```
2. Supprime le point-virgule (`;`) au début de la ligne pour activer l'extension

3. Chercher la ligne "upload_max_filesize" et augmenter la valeur pour permettre l'upload de fichiers plus volumineux, par exemple :
   ```ini
   upload_max_filesize = 100M
   ```

4. Enregistre le fichier et redémarre ton serveur PHP pour que les changements prennent effet.