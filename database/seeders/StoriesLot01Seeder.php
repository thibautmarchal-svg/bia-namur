<?php

namespace Database\Seeders;

use App\Models\Story;
use Database\Seeders\Concerns\SeedsDraftContent;
use Illuminate\Database\Seeder;

/**
 * Lot 1 — stories patrimoine (brouillons, à relire avant publication).
 * Sources : docs/contenu/lot-01-patrimoine-sources.md
 *
 * Seuls les faits recoupés par plusieurs sources figurent ici ; les points
 * divergents (accès intérieur du beffroi, date d'inauguration du théâtre,
 * longueur totale des galeries) sont volontairement laissés de côté.
 */
class StoriesLot01Seeder extends Seeder
{
    use SeedsDraftContent;

    public function run(): void
    {
        $this->seedStories([
            [
                'slug' => 'les-souterrains-de-la-citadelle',
                'type' => Story::TYPE_PATRIMOINE,
                'title' => 'Sous la Citadelle, la termitière de Napoléon',
                'excerpt' => 'Sous la forteresse qui domine le confluent court un réseau de galeries dont on ne visite qu\'une infime partie. Napoléon l\'aurait qualifié de termitière.',
                'place_slug' => 'citadelle-de-namur',
                'content' => <<<'MD'
Quand on monte à la Citadelle un dimanche de beau temps, on regarde le panorama, les pelouses, les familles qui pique-niquent. Rares sont ceux qui pensent à ce qui se passe *sous* leurs pieds. Pourtant, sous la forteresse qui veille sur le confluent de la Sambre et de la Meuse, tout un réseau de galeries a été creusé au fil des siècles pour défendre la ville.

On raconte que Napoléon aurait qualifié la Citadelle de « termitière ». Qu'il l'ait dit ou non, l'image colle : des galeries creusées dans la colline, dont la longueur totale se compte en kilomètres. Les chiffres avancés varient d'ailleurs d'un document à l'autre, ce qui en dit long sur l'ampleur du labyrinthe.

Aujourd'hui, seule une petite partie est ouverte au public : environ 500 mètres, que l'on parcourt en visite guidée d'une heure et demie. Depuis 2017, les galeries sont mises en scène avec du son, de la lumière, des effets 3D et des projections. C'est une manière de découvrir la Citadelle que le panorama du dessus ne laisse pas deviner.

Pour comprendre le contexte avant de descendre, le centre du visiteur Terra Nova, aménagé dans une ancienne caserne et inauguré en 2015, retrace deux mille ans d'histoire européenne, urbaine et militaire à travers celle de Namur et de sa Citadelle. C'est une bonne manière de se mettre dans l'ambiance avant de descendre.

Avant de partir, vérifiez les horaires et les conditions de réservation sur le site officiel de la Citadelle : ils peuvent évoluer d'une saison à l'autre.
MD,
            ],
            [
                'slug' => 'le-beffroi-de-namur',
                'type' => Story::TYPE_PATRIMOINE,
                'title' => 'Le beffroi de Namur, une tour de rempart devenue symbole',
                'excerpt' => 'Construite vers 1388 pour défendre la ville, la tour Saint-Jacques est devenue beffroi en 1746 et figure aujourd\'hui au patrimoine mondial de l\'UNESCO.',
                'place_slug' => 'beffroi-de-namur',
                'content' => <<<'MD'
Le beffroi de Namur n'a pas été construit pour sonner l'heure ni pour célébrer les libertés communales, comme tant de ses cousins flamands. À l'origine, c'est une tour de défense. Elle date des environs de 1388 et fait partie de l'enceinte médiévale de la ville : on l'appelait alors la tour Saint-Jacques, la plus haute des trois tours du rempart.

Son destin de beffroi vient d'un accident de l'histoire. Lors du siège de 1745, l'église dont la tour servait de beffroi à la ville est détruite. Il faut un remplaçant. Les Namurois se tournent vers la tour Saint-Jacques, qui devient officiellement le beffroi de la ville en 1746. Une tour de guerre reconvertie en tour civique : une histoire bien de chez nous, pragmatique et sans chichi.

Le XXe siècle ne l'a pas épargnée. Les bombardements américains de 1944 ont durement touché le centre de Namur, et le beffroi en a fait les frais. Elle est pourtant toujours là, au cœur du vieux quartier, à quelques pas de la place d'Armes.

Le 1er décembre 1999, l'UNESCO a inscrit le beffroi de Namur, avec ceux de Belgique et de France, sur la liste du patrimoine mondial. Le classement réunit 56 beffrois.

Pour la visiter, le plus simple est de l'admirer de l'extérieur, en flânant dans le centre. Si vous voulez savoir si l'intérieur se visite, renseignez-vous auprès de l'Office du tourisme, installé dans la Halle al'Chair.
MD,
            ],
            [
                'slug' => 'leglise-saint-loup',
                'type' => Story::TYPE_PATRIMOINE,
                'title' => 'Saint-Loup, l\'église baroque qui a fasciné Baudelaire',
                'excerpt' => 'Ancienne église des Jésuites élevée entre 1621 et 1645, Saint-Loup est l\'un des joyaux du baroque dans les anciens Pays-Bas méridionaux.',
                'place_slug' => 'eglise-saint-loup',
                'content' => <<<'MD'
On ne s'attend pas à ça, au détour des rues du centre de Namur. Derrière une façade qui annonce déjà du caractère, l'église Saint-Loup déploie un décor baroque d'une richesse étonnante. C'est un des bijoux de l'art baroque des anciens Pays-Bas méridionaux, et elle est classée patrimoine exceptionnel de Wallonie.

Elle a pourtant commencé comme église des Jésuites. Elle est élevée entre 1621 et 1645, sur les plans du frère jésuite Pieter Huyssens, qui s'inspire de deux grands modèles : le Gesù de Rome et l'église Saint-Charles-Borromée d'Anvers.

L'histoire de l'édifice change en 1773, quand la Compagnie de Jésus est supprimée. L'église devient alors paroissiale, et prend le nom de Saint-Loup en 1777. C'est donc la mémoire des Jésuites que l'on retrouve dans la pierre, mais c'est le nom d'un autre saint que les Namurois ont retenu.

Elle a eu un visiteur célèbre : le poète Charles Baudelaire, qui l'a admirée au point d'y voir une merveille à la fois sinistre et galante, comme il n'en avait jamais vu chez les Jésuites. Un compliment un peu étrange, mais très baudelairien.

Pour la visiter, entrez simplement, levez la tête et prenez votre temps : l'église a fêté ses 400 ans en 2021, avec une exposition de la Société archéologique de Namur, et cet anniversaire a remis ce monument sous les projecteurs. Les horaires d'ouverture varient, mieux vaut s'en assurer avant de venir, surtout si vous espérez une visite guidée.
MD,
            ],
            [
                'slug' => 'la-halle-al-chair',
                'type' => Story::TYPE_PATRIMOINE,
                'title' => 'La Halle al\'Chair, du marché aux viandes à l\'Office du tourisme',
                'excerpt' => 'Bâtie à la fin du XVIe siècle pour contrôler le commerce de la viande, la Halle a été école, arsenal, hôpital, musée et théâtre avant d\'accueillir les touristes.',
                'place_slug' => 'halle-al-chair',
                'content' => <<<'MD'
Il y a des bâtiments qui ont eu mille vies. La Halle al'Chair, au bord de la Sambre, près du pont, en a eu au moins six. Son nom wallon dit tout de son origine : *al chair*, c'est la viande. Elle a été construite entre 1588 et 1590, sous Philippe II, comme halle aux bouchers, pour mieux contrôler le commerce de la viande et son hygiène.

On attribue sa construction aux architectes Bastien Sion et Conrad de Nuremberg. À l'intérieur, on trouve un escalier en pierre et en bois, probablement le plus ancien de ce type.

Au fil des siècles, la Halle change d'usage sans changer de murs : école, arsenal, hôpital, puis musée. Elle a même abrité un théâtre, puisque deux boutiques de l'avant servaient de loges aux comédiens. Un lieu où l'on a vendu de la viande, soigné des malades, stocké des armes et joué la comédie, il faut le dire, ça ne s'invente pas.

C'est en 1855 que s'y installe le musée archéologique, créé en 1849 par la Société archéologique de Namur. Il y reste jusqu'en janvier 2019, avant de déménager au pôle muséal des Bateliers, rue Saintraint, où il a rouvert.

Depuis juillet 2023, la Halle accueille les bureaux de l'Office du tourisme de Namur. C'est un joli clin d'œil de l'histoire : un lieu de passage et de commerce redevenu un lieu d'accueil. Le bâtiment est classé monument historique, et vaut le détour rien que pour sa façade, même si vous n'avez pas besoin d'un plan de la ville.
MD,
            ],
            [
                'slug' => 'le-tresor-doignies-et-le-trema',
                'type' => Story::TYPE_PATRIMOINE,
                'title' => 'Le Trésor d\'Oignies, l\'une des sept merveilles de Belgique',
                'excerpt' => 'Un ensemble d\'orfèvrerie du XIIIe siècle, attribué à frère Hugo d\'Oignies, repose dans un hôtel patricien de la rue de Fer : le TreM.a.',
                'place_slug' => 'trema-musee-des-arts-anciens',
                'content' => <<<'MD'
Rue de Fer, derrière une façade d'hôtel patricien du XVIIIe siècle dont les stucs sont classés patrimoine exceptionnel de Wallonie, se cache une des pièces les plus précieuses du pays : le Trésor d'Oignies. Le bâtiment, c'est l'hôtel de Gaiffier d'Hestroy, aujourd'hui siège du TreM.a, le Musée des Arts anciens du Namurois.

Le trésor est un ensemble d'orfèvrerie médiévale. Ses pièces sont l'œuvre du moine orfèvre Hugo d'Oignies, ou de son atelier, entre 1225 et 1250 environ, pour le prieuré d'Oignies. Depuis 1978, il est reconnu comme l'une des sept merveilles de Belgique. Ce n'est pas rien.

Le musée abrite aussi le plus vaste ensemble d'œuvres du peintre Henri Bles. Ses collections viennent de la Société archéologique de Namur, avec laquelle la Province entretient un partenariat de longue date. Avant d'arriver rue de Fer, elles ont séjourné à la Halle al'Chair, puis à l'hôtel de Groesbeeck-Croix.

En 2024, une trentaine de pièces du trésor ont voyagé jusqu'à Paris, au musée de Cluny, pour l'exposition « Merveilleux Trésor d'Oignies », du 19 mars au 20 octobre. Un bel hommage rendu à un joyau qui appartient d'abord à Namur.

Pour la visite, prenez votre temps devant les vitrines : c'est de l'orfèvrerie, qui se regarde de près. Horaires et tarifs sont à vérifier sur le site officiel du musée avant de venir.
MD,
            ],
            [
                'slug' => 'le-musee-felicien-rops',
                'type' => Story::TYPE_PATRIMOINE,
                'title' => 'Rops, le Namurois qui dessinait comme on provoque',
                'excerpt' => 'Du caricaturiste des débuts à l\'illustrateur sulfureux, le musée de la rue Fumal raconte l\'œuvre de Félicien Rops dans le vieux Namur.',
                'place_slug' => 'musee-felicien-rops',
                'content' => <<<'MD'
Dans le vieux quartier de Namur, rue Fumal, un ancien hôtel de maître abrite le musée consacré à l'un des artistes les plus audacieux que la ville ait comptés : Félicien Rops. On est là à deux pas de l'église Saint-Loup et de la maison natale de l'artiste, ce qui donne à la visite un petit parfum de promenade en famille.

Le musée appartient à la Province de Namur et a été créé en 1964. Son parcours suit la vie et l'œuvre de Rops, de ses débuts de caricaturiste à ses créations les plus sulfureuses. Rien d'étonnant à ce que l'on parle de lui comme d'un artiste qui ne laissait pas indifférent.

Au fil du temps, les collections se sont enrichies grâce au soutien de la Fédération Wallonie-Bruxelles et de mécènes privés. En novembre 2025, une bonne nouvelle a été annoncée : 517 œuvres de la collection de Victor Arwas, venues de Londres, sont déposées à long terme au musée. Une belle façon de ramener un peu de Rops à la maison.

Le musée a aussi fermé deux mois au printemps 2023, du 6 mars au 15 mai, pour des travaux d'isolation.

Pour un parcours cohérent du vieux Namur, on peut enchaîner Rops, Saint-Loup et le TreM.a en une demi-journée à pied : trois adresses à quelques minutes les unes des autres, entre art baroque, orfèvrerie médiévale et gravure sulfureuse. Pour les horaires et les tarifs, le site officiel du musée fait foi.
MD,
            ],
            [
                'slug' => 'le-pont-de-jambes-et-le-grognon',
                'type' => Story::TYPE_PATRIMOINE,
                'title' => 'Le pont de Jambes, quand la Meuse séparait deux pays',
                'excerpt' => 'Un pont de pierre de 145 mètres, classé depuis 1989, qui marquait la frontière entre le comté de Namur et la principauté de Liège.',
                'place_slug' => 'pont-de-jambes',
                'content' => <<<'MD'
Pour traverser la Meuse à pied, à Namur, il y a plusieurs ponts. Mais un seul a porté une frontière : le pont de Jambes, aussi appelé pont de Meuse. C'est un vieux pont de maçonnerie de 145 mètres, qui relie Jambes au pied de la Citadelle, et il est classé au patrimoine de Wallonie depuis 1989.

Une très ancienne tradition en fait remonter l'origine à l'époque romaine, mais rien ne le démontre : à prendre comme une légende locale. Ce qui est certain, c'est qu'il a été reconstruit en pierre calcaire pour mieux résister aux crues de la Meuse, et qu'il a connu de nombreuses transformations.

Son importance a longtemps été stratégique, militaire d'abord, mais aussi commerciale et administrative. D'une rive à l'autre, on passait du comté de Namur à la principauté de Liège. Sa tour marquait la limite entre les juridictions de Namur et de Jambes. Autrement dit, quand on le traverse aujourd'hui sans y penser, on franchit un ancien poste frontière.

Aujourd'hui, les grands axes routiers ne passent plus par là. Le pont n'est d'ailleurs plus le seul à enjamber le fleuve. Depuis mai 2020, la passerelle cyclo-piétonne *L'Enjambée* relie aussi Jambes au Grognon, dans le cadre du projet de la Confluence.

Le Grognon, c'est la pointe où la Sambre se jette dans la Meuse, au pied de la Citadelle : le cœur géographique et historique de Namur. Notre conseil : faites l'aller par le pont de Jambes, le retour par la passerelle, et vous aurez traversé quelques siècles en une promenade.
MD,
            ],
        ]);
    }
}
