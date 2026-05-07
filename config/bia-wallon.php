<?php

/*
|--------------------------------------------------------------------------
| Wallon namurois — vocabulaire de base (cf. brief annexe C)
|--------------------------------------------------------------------------
|
| Mots de base utilises dans le ton editorial Bia + dans les contributions
| utilisateurs. Chaque mot a :
|   - definition courte (FR neutre)
|   - exemple d'usage en contexte
|   - famille semantique (quotidien / tradition / expression)
|
| L'idee n'est PAS de faire un dictionnaire exhaustif (Wikipedia / atilf
| le font deja), mais de capturer les 10-20 mots qui reviennent
| naturellement dans la voix Bia Namur. Pour les puristes, lien externe
| vers SLLW (Societe de Langue et Litterature Wallonnes).
*/

return [

    'words' => [
        [
            'word' => 'bia',
            'definition' => 'beau',
            'example' => 'C\'est bia, ça.',
            'family' => 'expression',
            'note' => 'Mot-titre du carnet. Plus chaleureux que "joli", plus modeste que "magnifique".',
        ],
        [
            'word' => 'biesse',
            'definition' => 'bête, idiot',
            'example' => 'Fais pas le biesse.',
            'family' => 'expression',
            'note' => 'Reproche tendre, sans méchanceté. Souvent dit aux enfants ou en blague entre copains.',
        ],
        [
            'word' => 'à l\'aise',
            'definition' => 'tranquille, sans souci',
            'example' => 'À l\'aise, ça va aller.',
            'family' => 'expression',
            'note' => 'Formule rassurante, signature culturelle namuroise. Un Namurois qui dit "à l\'aise" te dit que ça ira.',
        ],
        [
            'word' => 'tchafyî',
            'definition' => 'bavarder, papoter',
            'example' => 'On a tchafyî une heure devant la maison.',
            'family' => 'quotidien',
            'note' => null,
        ],
        [
            'word' => 'cougnou',
            'definition' => 'pain de Noël en forme d\'enfant Jésus',
            'example' => 'Le cougnou de la boulangerie de Bouge, c\'est le meilleur.',
            'family' => 'tradition',
            'note' => 'Brioche traditionnelle de fin d\'année, dégustée le matin de Noël avec un café fort.',
        ],
        [
            'word' => 'djin',
            'definition' => 'gens',
            'example' => 'Beaucoup de djin sur la place ce samedi.',
            'family' => 'quotidien',
            'note' => null,
        ],
        [
            'word' => 'on côp',
            'definition' => 'un coup, une fois',
            'example' => 'On côp, j\'ai vu un héron sur le pont des Ardennes.',
            'family' => 'expression',
            'note' => 'Marque le récit anecdotique. "On côp…" lance une histoire de famille.',
        ],
        [
            'word' => 'spotchî',
            'definition' => 'écraser, aplatir',
            'example' => 'Attention, tu vas spotchî les fraises.',
            'family' => 'quotidien',
            'note' => null,
        ],
        [
            'word' => 'nin',
            'definition' => 'pas (négation)',
            'example' => 'Y a nin moyen.',
            'family' => 'quotidien',
            'note' => 'Forme courte de "ne… pas". Très courante à l\'oral.',
        ],
        [
            'word' => 'on ptchot',
            'definition' => 'un petit, un enfant',
            'example' => 'Le ptchot dort encore.',
            'family' => 'quotidien',
            'note' => null,
        ],
        [
            'word' => 'pèkèt',
            'definition' => 'eau-de-vie de genièvre',
            'example' => 'Un pèkèt nature pour finir.',
            'family' => 'tradition',
            'note' => 'L\'alcool emblématique de Namur. Servie en petit verre, parfois aromatisée (citron, café, thé glacé).',
        ],
        [
            'word' => 'molons',
            'definition' => 'membres de la confrérie folklorique de Moncrabeau',
            'example' => 'Les Molons accompagnent le Bia Bouquet chaque 3e dimanche de septembre.',
            'family' => 'tradition',
            'note' => '40 hommes en uniforme bleu et écharpe ambrée, fondés en 1843. Chantent et défilent aux Wallos.',
        ],
        [
            'word' => 'wallos',
            'definition' => 'Fêtes de Wallonie',
            'example' => 'On se voit aux Wallos cette année ?',
            'family' => 'tradition',
            'note' => 'Diminutif courant pour les Fêtes de Wallonie, week-end namurois du 3e weekend de septembre.',
        ],
        [
            'word' => 'bia bouquet',
            'definition' => 'beau bouquet — cérémonie traditionnelle',
            'example' => 'Le Bia Bouquet, c\'est dimanche matin sur la place d\'Armes.',
            'family' => 'tradition',
            'note' => 'Cérémonie phare des Wallos : remise d\'un bouquet floral à une jeune Namuroise par les Molons. Origine corporations XIXe.',
        ],
    ],

    'families' => [
        'expression' => 'Expressions du quotidien',
        'quotidien' => 'Mots du quotidien',
        'tradition' => 'Tradition et folklore',
    ],

    'external_links' => [
        [
            'label' => 'SLLW — Société de Langue et Littérature Wallonnes',
            'url' => 'https://www.lalanguewallonne.be',
            'description' => 'Pour aller plus loin : étymologie, grammaire, archives sonores.',
        ],
        [
            'label' => 'Aljas walons — atlas linguistique',
            'url' => 'http://aljas.users.sourceforge.net',
            'description' => 'Cartographie dialectale du wallon, dont la zone namuroise.',
        ],
    ],

];
