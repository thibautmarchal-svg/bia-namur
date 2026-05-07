<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Place;
use App\Models\Story;
use Illuminate\Database\Seeder;

/**
 * 2 stories patrimoine main-ecrites pour Bia Namur (cf. brief §7.2).
 * Servent de few-shot examples pour le pipeline IA story_v1 en S2.
 * Ton namurois assume — ce qu'on attend d'une vraie story Bia.
 */
class StorySeeder extends Seeder
{
    public function run(): void
    {
        $namur = City::where('slug', 'namur')->firstOrFail();

        $stories = [
            [
                'slug' => 'la-rue-saintraint',
                'type' => Story::TYPE_PATRIMOINE,
                'title' => 'La rue Saintraint, qui n\'a pas toujours porté ce nom',
                'excerpt' => 'À deux pas de la place Saint-Aubain, l\'artère piétonne la plus animée du centre cache une histoire d\'hôpital, de chanoine bienfaiteur, et de portes cochères du XVIIe.',
                'place_slug' => null,
                'content' => <<<'MD'
À deux pas de la place Saint-Aubain, la rue Saintraint a longtemps porté un nom plus austère : *rue de l'Hôpital*. Au XVIIIe siècle, l'institution hospitalière qui s'y dressait soignait les indigents et accueillait les pèlerins de passage entre Liège et Charleroi. Quand l'hôpital ferma ses portes en 1854, on voulut effacer le souvenir trop sobre de la misère soignée et on rebaptisa la voie du nom du chanoine Joseph Saintraint, érudit local et bienfaiteur du quartier.

Aujourd'hui, la rue est l'une des artères piétonnes les plus animées du centre — librairies indépendantes, anciennes pharmacies converties en bistrots, façades en briques rouges et pierre bleue qu'un œil attentif sait dater au quartier. Le numéro 17 cache encore une porte cochère du XVIIe siècle, classée mais peu visible si on ne lève pas la tête.

Pour les Namurois, *Saintraint* reste avant tout une géographie du quotidien : la papeterie où on a acheté ses cahiers d'école, le glacier où l'on emmène les petits-enfants, la galerie du carillon qu'on traverse pour rejoindre le marché du samedi. À l'aise. Une rue qui ne fait pas son intéressante, mais qui a tout vu passer.
MD,
            ],
            [
                'slug' => 'lorigine-du-bia-bouquet',
                'type' => Story::TYPE_TRADITION,
                'title' => 'L\'origine du Bia Bouquet, le 3e dimanche de septembre',
                'excerpt' => 'Pourquoi *Bia* veut dire « beau » en wallon namurois, et comment un bouquet floral est devenu la cérémonie la plus tendre des Fêtes de Wallonie.',
                'place_slug' => 'le-bia-bouquet',
                'content' => <<<'MD'
*Bia* veut dire « beau » en wallon namurois — pas « joli », pas « charmant », *beau* avec un soupçon de fierté. Le mot revient dans les jurons doux, les compliments aux enfants, les conclusions de marché : « c'est bia, ça ». Au cœur des Fêtes de Wallonie, le 3e dimanche de septembre, le mot s'incarne dans une cérémonie tendre : la remise du *Bia Bouquet*.

L'origine remonte aux corporations de métiers de l'ancien régime, qui défilaient dans la ville en offrant des bouquets de fleurs au peuple. Au XIXe siècle, la tradition s'est concentrée sur un seul bouquet annuel, remis à une jeune Namuroise par les *Molons* — la confrérie folklorique de la ville, en uniforme bleu et écharpe ambrée. Le *Bia Bouquet* est passé d'un signe de prospérité à un symbole de fierté locale, à une heure où le wallon se parlait encore dans les cours d'école.

Aujourd'hui, la cérémonie se tient sur la place d'Armes le dimanche matin, devant quelques milliers de Namurois venus en famille. Le bouquet circule dans la foule, on chante *Li Bia Bouquet* (l'hymne local de Nicolas Bosret, écrit en 1856), on s'embrasse, on rentre déjeuner avec des photos plein le téléphone. C'est un dimanche que les expats namurois rentrent fêter chaque année, et c'est là, plus qu'ailleurs, qu'on comprend pourquoi le mot *bia* tient bon.
MD,
            ],
        ];

        foreach ($stories as $data) {
            $place = $data['place_slug']
                ? Place::where('city_id', $namur->id)->where('slug', $data['place_slug'])->first()
                : null;

            Story::updateOrCreate(
                ['city_id' => $namur->id, 'slug' => $data['slug']],
                [
                    'type' => $data['type'],
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'content' => $data['content'],
                    'place_id' => $place?->id,
                    'ai_generated' => false,
                    'reviewed_by' => null,
                    'reviewed_at' => now(),
                    'status' => Story::STATUS_PUBLISHED,
                ],
            );
        }

        // Si la story du Bia Bouquet est creee et qu'on a le lieu, on lie le lieu a la story
        $biaBouquetStory = Story::where('city_id', $namur->id)->where('slug', 'lorigine-du-bia-bouquet')->first();
        $biaBouquetPlace = Place::where('city_id', $namur->id)->where('slug', 'le-bia-bouquet')->first();
        if ($biaBouquetStory && $biaBouquetPlace) {
            $biaBouquetPlace->update(['story_id' => $biaBouquetStory->id]);
        }
    }
}
