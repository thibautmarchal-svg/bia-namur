<?php
// Wrapper deploiement mutualise (DocumentRoot != public/).
// Toutes les requetes sont reroutees vers public/index.php via .htaccess,
// mais ce fichier est requis pour les hebergeurs sans mod_rewrite.
require __DIR__.'/public/index.php';
