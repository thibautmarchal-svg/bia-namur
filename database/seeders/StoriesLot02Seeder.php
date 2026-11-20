<?php

namespace Database\Seeders;

use App\Models\Story;
use Database\Seeders\Concerns\SeedsDraftContent;
use Illuminate\Database\Seeder;

/**
 * Lot 2 — stories bonnes adresses (brouillons, à relire avant publication).
 * Sources : docs/contenu/lot-02-bonnes-adresses-sources.md
 */
class StoriesLot02Seeder extends Seeder
{
    use SeedsDraftContent;

    public function run(): void
    {
        $this->seedStories([
            [
                'slug' => 'le-parc-louise-marie',
                'type' => Story::TYPE_PLACE,
                'title' => 'Le parc Louise-Marie, un fossé devenu jardin',
                'excerpt' => 'À deux pas de la gare, un parc de trois hectares occupe l\'ancien fossé de l\'enceinte médiévale, longtemps transformé en refuge à bateaux.',
                'place_slug' => 'parc-louise-marie',
                'content' => <<<'MD'
Quand on sort de la gare de Namur, on file souvent droit vers le centre sans regarder à gauche ou à droite. C'est dommage : à deux pas, le parc Louise-Marie offre une pause verte facile à glisser dans un trajet.

Son histoire est celle d'une reconversion. Le parc a été aménagé en 1879-1880 à l'emplacement du fossé de l'ancienne enceinte du XIVe siècle, qui avait fini par servir de refuge à bateaux. Le démantèlement des remparts a donné l'occasion d'en faire un lieu de promenade. Le résultat : un parc d'environ trois hectares, réparti sur deux niveaux.

On y trouve des allées et un étang aux formes courbes, de larges pelouses, des grottes artificielles et un pont hollandais, vestige des fortifications de 1815-1820. Autrement dit, le décor du XIXe siècle se mêle à des traces beaucoup plus anciennes de la défense de la ville.

Le nom rend hommage à Louise-Marie d'Orléans, première reine des Belges et épouse de Léopold Ier.

C'est un endroit simple, qui ne demande pas de programme : une promenade entre deux trains, ou un tour d'étang avant de rejoindre le centre. Une adresse qui n'a rien à prouver, et qui résume assez bien l'esprit de ces lieux qu'on aime à Namur sans en faire des attractions.
MD,
            ],
            [
                'slug' => 'le-marche-du-samedi-au-bord-de-la-sambre',
                'type' => Story::TYPE_PLACE,
                'title' => 'Le marché du samedi a pris ses quartiers au bord de la Sambre',
                'excerpt' => 'Depuis mars 2024, le marché hebdomadaire de Namur s\'installe le long de la Sambre et du boulevard Frère Orban, et les habitudes du samedi matin ont changé.',
                'place_slug' => 'marche-du-bord-de-leau',
                'content' => <<<'MD'
Pendant longtemps, aller au marché à Namur, c'était descendre dans le centre le samedi matin. Depuis mars 2024, il faut changer d'itinéraire : le marché hebdomadaire s'est installé le long de la Sambre, sur le boulevard Frère Orban. Sa première édition à cet emplacement a eu lieu un samedi de ce mois-là, et il s'appelle désormais le marché du bord de l'eau.

Ce déménagement a fait parler. Installer un marché sur un nouvel emplacement n'a rien d'une formalité : la presse locale a parlé d'un Tetris à ciel ouvert pour décrire le casse-tête de l'organisation. Quant aux commerçants du centre-ville, ils se sont retrouvés partagés sur les retombées : moins de monde dans certaines rues, comme la rue de Fer, le samedi matin.

Les repères ont changé aussi pour ceux qui cherchent des informations en ligne. Si vous préparez votre venue, mieux vaut vérifier l'emplacement sur le site de l'Office du tourisme, qui publie les dates des éditions du marché du bord de l'eau, le samedi.

Le décor, lui, a de quoi plaire : un marché installé au bord de l'eau, en plein cœur de la ville. Pour en profiter, venez le samedi matin avec un sac : on achète toujours plus qu'on ne l'avait prévu.
MD,
            ],
            [
                'slug' => 'les-librairies-independantes-de-namur',
                'type' => Story::TYPE_PLACE,
                'title' => 'Trois librairies indépendantes du centre de Namur',
                'excerpt' => 'Tulitu, MaBaLiYo et Ad Hoc : trois adresses du centre pour qui préfère un libraire à un algorithme, entre santé mentale et bande dessinée.',
                'place_slug' => null,
                'content' => <<<'MD'
Namur ne manque pas de librairies, et le centre-ville en compte plusieurs où l'on prend le temps de fouiner. En voici trois, très différentes, pour qui préfère les conseils d'un libraire à ceux d'un algorithme.

**Tulitu**, rue Bas de la Place, au numéro 16, est installée dans une rue calme du centre. Pour une pause hors du flux des rues commerçantes, c'est une adresse tranquille où fouiner.

**Ad Hoc**, rue Saint-Jean, est la librairie des amateurs de bande dessinée. Elle a fêté ses trente ans en 2024, ce qui en fait une adresse bien installée du centre de Namur pour les lecteurs de BD.

**MaBaLiYo**, au 13 de la rue des Brasseurs, est la plus récente : ce café-librairie a ouvert fin 2025. Son projet tourne autour de la santé mentale et de la sexologie. Une adresse qui change des rayons habituels.

Trois adresses, trois ambiances, et un même trajet à pied dans le centre : on peut les enchaîner en une matinée, avec un détour par un café entre deux. Avant de vous déplacer, vérifiez les horaires d'ouverture directement auprès de chaque librairie.

À noter : cette liste ne prétend pas être exhaustive. Si vous connaissez une librairie qui mérite d'y figurer, la page « Contribuer » du site est faite pour ça.
MD,
            ],
        ]);
    }
}
