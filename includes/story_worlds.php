<?php
/**
 * Curated story-world data for the child-facing portal.
 *
 * Content lives outside the web root while it is being generated. Only the
 * explicitly listed, finished episodes can be streamed by the local portal.
 *
 * The arrays below are the English source of truth. Every getter hands its
 * result through the localize* helpers as the last step, so translations land
 * only on presentation fields and never on the slugs, posters, file names or R2
 * keys that the getters derive from the English titles further up.
 */

/**
 * Translation table for the current locale, empty on the source language.
 *
 * @return array<string, mixed>
 */
function storyContentTranslations(): array
{
    static $translations = null;
    if ($translations !== null) {
        return $translations;
    }

    $locale = function_exists('currentLocale') ? currentLocale() : DEFAULT_LOCALE;
    if ($locale === DEFAULT_LOCALE) {
        return $translations = [];
    }

    $file = BASE_PATH . '/includes/lang/content_' . $locale . '.php';

    return $translations = is_file($file) ? (array) require $file : [];
}

/**
 * Overlay translated fields onto records keyed by slug.
 *
 * Untranslated records keep their English text rather than disappearing, so a
 * newly added episode is readable the moment it ships, translated or not.
 *
 * @param array<int, array<string, mixed>> $records
 * @param array<string, array<string, string>> $overrides
 * @return array<int, array<string, mixed>>
 */
function applyTranslationsBySlug(array $records, array $overrides): array
{
    if (empty($overrides)) {
        return $records;
    }

    foreach ($records as &$record) {
        foreach ($overrides[(string) ($record['slug'] ?? '')] ?? [] as $field => $value) {
            $record[$field] = $value;
        }
    }
    unset($record);

    return $records;
}

/**
 * @param array<int, array<string, mixed>> $episodes
 * @return array<int, array<string, mixed>>
 */
function localizeEpisodes(array $episodes, string $worldSlug): array
{
    $translations = storyContentTranslations();
    if (empty($translations)) {
        return $episodes;
    }

    $episodes = applyTranslationsBySlug($episodes, (array) ($translations['episodes'] ?? []));

    $category = $translations['categories'][$worldSlug] ?? null;
    $language = $translations['language_label'] ?? null;
    if ($category === null && $language === null) {
        return $episodes;
    }

    foreach ($episodes as &$episode) {
        if ($category !== null) {
            $episode['category'] = $category;
        }
        if ($language !== null) {
            $episode['language'] = $language;
        }
    }
    unset($episode);

    return $episodes;
}

/**
 * @param array<string, array<string, mixed>> $worlds
 * @return array<string, array<string, mixed>>
 */
function localizeWorlds(array $worlds): array
{
    $overrides = (array) (storyContentTranslations()['worlds'] ?? []);
    if (empty($overrides)) {
        return $worlds;
    }

    foreach ($worlds as $slug => &$world) {
        foreach ($overrides[$slug] ?? [] as $field => $value) {
            $world[$field] = $value;
        }
    }
    unset($world);

    return $worlds;
}

/**
 * @param array<int, array<string, mixed>> $seasons
 * @return array<int, array<string, mixed>>
 */
function localizeSeasons(array $seasons, string $worldSlug): array
{
    $overrides = (array) (storyContentTranslations()['seasons'][$worldSlug] ?? []);
    if (empty($overrides)) {
        return $seasons;
    }

    foreach ($seasons as $number => &$season) {
        foreach ($overrides[$number] ?? [] as $field => $value) {
            $season[$field] = $value;
        }
    }
    unset($season);

    return $seasons;
}

/**
 * @param array<int, array<string, mixed>> $characters
 * @return array<int, array<string, mixed>>
 */
function localizeCharacters(array $characters): array
{
    return applyTranslationsBySlug($characters, (array) (storyContentTranslations()['characters'] ?? []));
}

/**
 * @return array<string, array<string, mixed>>
 */
function getStoryWorlds(): array
{
    static $worlds = null;
    if ($worlds !== null) {
        return $worlds;
    }

    $worlds = [
        'oasis' => [
            'slug' => 'oasis',
            'eyebrow' => 'Two seasons ready',
            'icon' => 'palm',
            'title' => 'Barik\'s Big Adventures',
            'short_title' => 'Barik',
            'description' => 'Run from the Golden Oasis to Coral Bay with Barik and a growing crew of wonderfully curious friends.',
            'ritual' => 'Two storybook seasons',
            'status' => 'available',
            'image' => '/assets/images/episodes/camel-laughter-scene-03-2.webp',
            'theme' => 'sun',
            'stops' => ['Oasis Gate', 'Date Garden', 'Lizard Cafe', 'Star Dunes'],
        ],
        'star-workshop' => [
            'slug' => 'star-workshop',
            'eyebrow' => 'Twenty episodes streaming',
            'icon' => 'star',
            'title' => 'Mira and the Star Workshop',
            'short_title' => 'Mira',
            'description' => 'Mira and her curious robot helper Bip race from their flying workshop to fix wonderfully strange problems across the stars.',
            'ritual' => 'Twenty starry repair missions',
            'status' => 'available',
            'image' => '/assets/images/episodes/mira-star-workshop/episode-01-the-planet-that-sneezes.jpg',
            'hero_image' => '/assets/images/episodes/mira-star-workshop/episode-01-the-planet-that-sneezes.jpg',
            'theme' => 'sky',
            'stops' => ['Sneezing Planet', 'Pushinka', 'Bubbleia', 'Prilipala', 'Kachelia', 'Vervinka', 'Arkadia'],
            'planned_episodes' => 20,
            'audio_label' => 'Arabic audio',
            'audio_code' => 'AR',
            'availability_copy' => 'Twenty episodes available',
        ],
        'push-questions' => [
            'slug' => 'push-questions',
            'eyebrow' => 'Twenty episodes streaming',
            'icon' => 'cat',
            'title' => 'Push and the Big Questions',
            'short_title' => 'Push',
            'description' => 'A grey kitten meets the ordinary world for the first time, invents a perfectly logical theory for all of it, and a five-year-old boy named Tim gently shows him how it really works.',
            'ritual' => 'Twenty everyday mysteries',
            'status' => 'available',
            'image' => '/assets/images/episodes/push-big-questions/episode-05-where-does-the-sun-go.jpg',
            'hero_image' => '/assets/images/episodes/push-big-questions/episode-05-where-does-the-sun-go.jpg',
            'theme' => 'meadow',
            'stops' => ['The White Door', 'The Cold Cave', 'The Backyard', 'The Sunset Fence'],
            'planned_episodes' => 20,
            'audio_label' => 'Arabic audio',
            'audio_code' => 'AR',
            'availability_copy' => 'Twenty episodes available',
        ],
        'city-on-wheels' => [
            'slug' => 'city-on-wheels',
            'eyebrow' => 'Twenty episodes streaming',
            'icon' => 'truck',
            'title' => 'City on Wheels',
            'short_title' => 'Wheels',
            'description' => 'In a sunny seaside town every neighbour is a vehicle, and each day brings a delivery, a rescue or a puzzle that takes the whole crew to solve.',
            'ritual' => 'Twenty seaside call-outs',
            'status' => 'available',
            'image' => '/assets/images/episodes/city-on-wheels/episode-12-the-ship-full-of-rubber-ducks.jpg',
            'hero_image' => '/assets/images/episodes/city-on-wheels/episode-12-the-ship-full-of-rubber-ducks.jpg',
            'theme' => 'ocean',
            'stops' => ['Bakery Corner', 'Market Street', 'The Old Tunnel', 'The Harbour'],
            'planned_episodes' => 20,
            'audio_label' => 'Arabic audio',
            'audio_code' => 'AR',
            'availability_copy' => 'Twenty episodes available',
        ],
        'robot-family' => [
            'slug' => 'robot-family',
            'eyebrow' => 'Twenty episodes streaming',
            'icon' => 'robot',
            'title' => 'The Robot Family',
            'short_title' => 'Robot Family',
            'description' => 'Robik, Mama, Papa, the grandparents, baby Knopik and robot dog Bolt turn one small household puzzle into a very big family investigation.',
            'ritual' => 'Twenty bright family mysteries',
            'status' => 'available',
            'image' => '/assets/images/episodes/robot-family/episode-01-lost-smartphone.jpg',
            'hero_image' => '/assets/images/episodes/robot-family/episode-20-grandmas-day.jpg',
            'theme' => 'lavender',
            'stops' => ['The Living Room', 'The Service Bench', 'The Hallway', 'The Window Corner'],
            'planned_episodes' => 20,
            'audio_label' => 'Arabic audio',
            'audio_code' => 'AR',
            'availability_copy' => 'Twenty episodes available',
        ],
        'farm' => [
            'slug' => 'farm',
            'eyebrow' => 'Growing soon',
            'icon' => 'tractor',
            'title' => 'Tumbleleaf Farm',
            'short_title' => 'Farm',
            'description' => 'Peek down on patchwork fields where every seed seems to grow a surprise.',
            'ritual' => 'A bird\'s-eye farm',
            'status' => 'upcoming',
            'theme' => 'meadow',
            'stops' => ['The Red Barn', 'Carrot Patch', 'Windmill Hill', 'Berry Lane'],
        ],
        'railway' => [
            'slug' => 'railway',
            'eyebrow' => 'All aboard soon',
            'icon' => 'train',
            'title' => 'Whistlewood Railway',
            'short_title' => 'Railway',
            'description' => 'Choose a station, ring the bell, and ride through a world connected by stories.',
            'ritual' => 'A route of story stations',
            'status' => 'upcoming',
            'theme' => 'berry',
            'stops' => ['Acorn Station', 'Cloud Bridge', 'Moon Tunnel', 'Picnic End'],
        ],
        'city' => [
            'slug' => 'city',
            'eyebrow' => 'Opening soon',
            'icon' => 'city',
            'title' => 'Brightbell City',
            'short_title' => 'City',
            'description' => 'Turn down colorful streets and discover a different little tale behind every door.',
            'ritual' => 'Streets and story buildings',
            'status' => 'upcoming',
            'theme' => 'sky',
            'stops' => ['Market Square', 'Music Street', 'Rooftop Garden', 'Lantern Lane'],
        ],
        'castle' => [
            'slug' => 'castle',
            'eyebrow' => 'Unlocking soon',
            'icon' => 'castle',
            'title' => 'Cloudberry Castle',
            'short_title' => 'Castle',
            'description' => 'Open grand doors, secret cupboards, and rooms where the portraits like to gossip.',
            'ritual' => 'Rooms inside the castle',
            'status' => 'upcoming',
            'theme' => 'lavender',
            'stops' => ['Great Hall', 'Royal Kitchen', 'Whispering Library', 'Highest Tower'],
        ],
        'sea' => [
            'slug' => 'sea',
            'eyebrow' => 'Coming soon',
            'icon' => 'boat',
            'title' => 'Whisperwave Islands',
            'short_title' => 'Sea Adventures',
            'description' => 'Hop across turquoise islands with Inky, Hani, and a pocketful of sea riddles.',
            'ritual' => 'An archipelago of islands',
            'status' => 'upcoming',
            'image' => '/assets/images/worlds/sea.webp',
            'theme' => 'ocean',
            'stops' => ['Coral Bay', 'Blue Cave', 'Eight Shells', 'Sunset Island'],
        ],
        'dinosaurs' => [
            'slug' => 'dinosaurs',
            'eyebrow' => 'Hatching soon',
            'icon' => 'dino',
            'title' => 'Roarsome Valley',
            'short_title' => 'Dinosaurs',
            'description' => 'Trek through leafy, rocky, and volcanic biomes with some surprisingly gentle giants.',
            'ritual' => 'A valley of changing biomes',
            'status' => 'upcoming',
            'theme' => 'fern',
            'stops' => ['Fern Forest', 'Amber Cliffs', 'Warm Springs', 'Volcano Lookout'],
        ],
    ];

    return $worlds = localizeWorlds($worlds);
}

/**
 * Group a flat run of episodes into seasons of a fixed length.
 *
 * An episode's position in the whole series stays in 'number', and every stable
 * identifier (R2 object key, poster filename) is built from that. 'season' and
 * 'season_episode' are presentation only. Keeping the two apart means a series
 * can be re-grouped into seasons without moving a single stored object.
 *
 * @param array<int, array<string, mixed>> $episodes
 */
function applySeasonSplit(array &$episodes, int $perSeason = 10): void
{
    $counts = [];
    foreach ($episodes as $episode) {
        $season = (int) ceil((int) $episode['number'] / $perSeason);
        $counts[$season] = ($counts[$season] ?? 0) + 1;
    }

    foreach ($episodes as &$episode) {
        $number = (int) $episode['number'];
        $season = (int) ceil($number / $perSeason);
        $episode['season'] = $season;
        $episode['season_episode'] = $number - ($season - 1) * $perSeason;
        $episode['season_total'] = $counts[$season];
    }
    unset($episode);
}

/**
 * Build the two-season list for a series that runs in blocks of ten.
 *
 * @param array<int, array<string, mixed>> $episodes
 * @param array<int, array<string, mixed>> $meta season number => presentation fields
 * @return array<int, array<string, mixed>>
 */
function buildSeasonsFromEpisodes(array $episodes, array $meta, string $worldSlug): array
{
    $seasons = [];
    foreach ($meta as $number => $fields) {
        $seasonEpisodes = array_values(array_filter(
            $episodes,
            static fn (array $episode): bool => (int) $episode['season'] === (int) $number
        ));

        $seasons[$number] = $fields + [
            'number' => (int) $number,
            'episodes' => $seasonEpisodes,
            'episode_count' => count($seasonEpisodes),
            'start_url' => $seasonEpisodes[0]['watch_url'] ?? '/world/' . $worldSlug,
        ];
    }

    return $seasons;
}

/**
 * @return array<int, array<string, mixed>>
 */
function getOasisEpisodes(): array
{
    static $episodes = null;
    if ($episodes !== null) {
        return $episodes;
    }

    $episodes = [
        [
            'number' => 1,
            'season' => 1,
            'season_episode' => 1,
            'slug' => 'camel-who-collected-laughter',
            'title' => 'The Camel Who Collected Laughter',
            'short_title' => 'Collected Laughter',
            'description' => 'Barik tries to save every giggle he hears and discovers that laughter is happiest when it is shared.',
            'place' => 'Laughing Lagoon',
            'poster' => '/assets/images/episodes/camel-laughter-scene-03-2.webp',
            'file' => 'Season 1/Episode 1 - The Camel Who Collected Laughter.mp4',
            'character' => 'Barik & Noura',
            'duration' => 291,
        ],
        [
            'number' => 2,
            'season' => 1,
            'season_episode' => 2,
            'slug' => 'mysterious-gift',
            'title' => 'Barik and the Mysterious Gift',
            'short_title' => 'The Mysterious Gift',
            'description' => 'A curious parcel sends Barik racing across the oasis in search of its owner.',
            'place' => 'Oasis Gate',
            'poster' => '/assets/images/episodes/delivery/s1-e02-mysterious-gift.jpg',
            'file' => 'Season 1/Episode 2 - Barik and the Mysterious Gift.mp4',
            'character' => 'Barik',
            'duration' => 335,
        ],
        [
            'number' => 3,
            'season' => 1,
            'season_episode' => 3,
            'slug' => 'operation-tickle',
            'title' => 'Operation Tickle',
            'short_title' => 'Operation Tickle',
            'description' => 'The oasis needs a smile, and Barik launches a wonderfully wobbly rescue plan.',
            'place' => 'Falcon Lookout',
            'poster' => '/assets/images/episodes/delivery/s1-e03-operation-tickle.jpg',
            'file' => 'Season 1/Episode 3 - Operation Tickle.mp4',
            'character' => 'Barik & Faris',
            'duration' => 352,
        ],
        [
            'number' => 4,
            'season' => 1,
            'season_episode' => 4,
            'slug' => 'bitterest-date',
            'title' => 'The Bitterest Date',
            'short_title' => 'The Bitterest Date',
            'description' => 'One terrible-tasting date becomes the beginning of a surprisingly sweet mystery.',
            'place' => 'Date Garden',
            'poster' => '/assets/images/episodes/delivery/s1-e04-bitterest-date.jpg',
            'file' => 'Season 1/Episode 4 - The Bitterest Date.mp4',
            'character' => 'Barik & Noura',
            'duration' => 311,
        ],
        [
            'number' => 5,
            'season' => 1,
            'season_episode' => 5,
            'slug' => 'everyone-swapped-places',
            'title' => 'The Day Everyone Swapped Places',
            'short_title' => 'Everyone Swapped Places',
            'description' => 'A topsy-turvy day proves that another friend\'s job is never as easy as it looks.',
            'place' => 'Topsy-Turvy Trail',
            'poster' => '/assets/images/episodes/delivery/s1-e05-swapped-places.jpg',
            'file' => 'Season 1/Episode 5 - The Day Everyone Swapped Places.mp4',
            'character' => 'The whole oasis crew',
            'duration' => 393,
        ],
        [
            'number' => 6,
            'season' => 1,
            'season_episode' => 6,
            'slug' => 'cafe-for-lizards',
            'title' => 'The Cafe for Lizards',
            'short_title' => 'The Cafe for Lizards',
            'description' => 'Barik helps a tiny chef find the one thing every good cafe truly needs.',
            'place' => 'Lizard Cafe',
            'poster' => '/assets/images/episodes/delivery/s1-e06-cafe-for-lizards.jpg',
            'file' => 'Season 1/Episode 6 - The Cafe for Lizards.mp4',
            'character' => 'Barik & Giko',
            'duration' => 370,
        ],
        [
            'number' => 7,
            'season' => 1,
            'season_episode' => 7,
            'slug' => 'great-wind-hunt',
            'title' => 'The Great Wind Hunt',
            'short_title' => 'The Great Wind Hunt',
            'description' => 'Barik sets out to catch the wind and discovers it has been helping everyone all along.',
            'place' => 'Whispering Palms',
            'poster' => '/assets/images/episodes/delivery/s1-e07-great-wind-hunt.jpg',
            'file' => 'Season 1/Episode 7 - The Great Wind Hunt.mp4',
            'character' => 'Barik & Faris',
            'duration' => 346,
        ],
        [
            'number' => 8,
            'season' => 1,
            'season_episode' => 8,
            'slug' => 'sea-went-for-a-walk',
            'title' => 'The Day the Sea Went for a Walk',
            'short_title' => 'The Sea Went for a Walk',
            'description' => 'When the water wanders away, the friends follow its shimmering footprints.',
            'place' => 'Silver Shore',
            'poster' => '/assets/images/episodes/delivery/s1-e08-sea-went-for-a-walk.jpg',
            'file' => 'Season 1/Episode 8 - The Day the Sea Went for a Walk.mp4',
            'character' => 'Barik & friends',
            'duration' => 335,
        ],
        [
            'number' => 9,
            'season' => 1,
            'season_episode' => 9,
            'slug' => 'night-of-a-thousand-stars',
            'title' => 'The Night of a Thousand Stars',
            'short_title' => 'A Thousand Stars',
            'description' => 'The friends climb beyond the palms to find the best seat beneath the whole night sky.',
            'place' => 'Star Dunes',
            'poster' => '/assets/images/episodes/delivery/s1-e09-thousand-stars.jpg',
            'file' => 'Season 1/Episode 9 - The Night of a Thousand Stars.mp4',
            'character' => 'The whole oasis crew',
            'duration' => 430,
        ],
        [
            'number' => 10,
            'season' => 1,
            'season_episode' => 10,
            'slug' => 'the-giants-footprints',
            'title' => 'The Giant\'s Footprints',
            'short_title' => 'The Giant\'s Footprints',
            'description' => 'Enormous footprints appear across the smooth morning dunes, and the friends prepare to meet a giant who turns out to be a neighbour.',
            'place' => 'The Oasis Lake',
            'poster' => '/assets/images/episodes/delivery/s1-e10-giants-footprints.webp',
            'file' => 'Season 1/Episode 10 - The Giant\'s Footprints.mp4',
            'character' => 'Barik & Tuta',
            'duration' => 161,
        ],
        [
            'number' => 11,
            'season' => 2,
            'season_episode' => 1,
            'slug' => 'coral-bay',
            'title' => 'Coral Bay',
            'short_title' => 'Coral Bay',
            'description' => 'A new trail carries Barik to turquoise water, friendly fins, and a bay full of surprises.',
            'place' => 'Coral Bay',
            'poster' => '/assets/images/episodes/coral-bay-dolphin-leap.jpg',
            'file' => 'Season 2/Episode 1 - Coral Bay.mp4',
            'character' => 'Barik & the bay crew',
            'duration' => 263,
        ],
        [
            'number' => 12,
            'season' => 2,
            'season_episode' => 2,
            'slug' => 'why-corals-must-not-be-hugged',
            'title' => 'Why Corals Must Not Be Hugged',
            'short_title' => 'Do Not Hug the Corals',
            'description' => 'Barik learns that caring for a beautiful place sometimes means giving it plenty of space.',
            'place' => 'Coral Garden',
            'poster' => '/assets/images/episodes/delivery/s2-e02-corals-not-hugged.jpg',
            'file' => 'Season 2/Episode 2 - Why Corals Must Not Be Hugged.mp4',
            'character' => 'Barik & Inky',
            'duration' => 263,
        ],
        [
            'number' => 13,
            'season' => 2,
            'season_episode' => 3,
            'slug' => 'riddle-of-the-eight-shells',
            'title' => 'The Riddle of the Eight Shells',
            'short_title' => 'The Eight Shells',
            'description' => 'Eight curious shells lead the friends through a bright seaside puzzle.',
            'place' => 'Shell Beach',
            'poster' => '/assets/images/episodes/delivery/s2-e03-eight-shells.jpg',
            'file' => 'Season 2/Episode 3 - The Riddle of the Eight Shells.mp4',
            'character' => 'Barik & Inky',
            'duration' => 263,
        ],
        [
            'number' => 14,
            'season' => 2,
            'season_episode' => 4,
            'slug' => 'everyone-must-get-home',
            'title' => 'Everyone Must Get Home',
            'short_title' => 'Everyone Must Get Home',
            'description' => 'The bay crew turns a difficult journey home into a lesson in helping one another.',
            'place' => 'Island Crossing',
            'poster' => '/assets/images/episodes/delivery/s2-e04-everyone-home.jpg',
            'file' => 'Season 2/Episode 4 - Everyone Must Get Home.mp4',
            'character' => 'Barik & Hani',
            'duration' => 263,
        ],
        [
            'number' => 15,
            'season' => 2,
            'season_episode' => 5,
            'slug' => 'when-the-sun-comes-to-visit',
            'title' => 'When the Sun Comes to Visit',
            'short_title' => 'When the Sun Visits',
            'description' => 'A very bright visitor turns an ordinary day at the bay into one last glowing adventure.',
            'place' => 'Sunlit Cove',
            'poster' => '/assets/images/episodes/delivery/s2-e05-sun-comes-to-visit.jpg',
            'file' => 'Season 2/Episode 5 - When the Sun Comes to Visit.mp4',
            'character' => 'Barik & the bay crew',
            'duration' => 263,
        ],
        [
            'number' => 16,
            'season' => 2,
            'season_episode' => 6,
            'slug' => 'big-moving-day',
            'title' => 'The Big Moving Day',
            'short_title' => 'The Big Moving Day',
            'description' => 'When the bay\'s smallest neighbors need new homes, Barik turns moving day into a team adventure.',
            'place' => 'Shell Village',
            'poster' => '/assets/images/episodes/delivery/s2-e06-big-moving-day-v2.webp',
            'file' => 'Season 2/Episode 6 - The Big Moving Day.mp4',
            'character' => 'Barik & Hani',
            'duration' => 312,
        ],
        [
            'number' => 17,
            'season' => 2,
            'season_episode' => 7,
            'slug' => 'following-the-sea',
            'title' => 'Following the Sea',
            'short_title' => 'Following the Sea',
            'description' => 'A trail left by the tide leads Barik and his friends across Coral Bay—and back to one another.',
            'place' => 'Tide Trail',
            'poster' => '/assets/images/episodes/delivery/s2-e07-following-the-sea.webp',
            'file' => 'Season 2/Episode 7 - Following the Sea.mp4',
            'character' => 'Barik & the bay crew',
            'duration' => 324,
        ],
        [
            'number' => 18,
            'season' => 2,
            'season_episode' => 8,
            'slug' => 'when-the-wind-hid-the-sun',
            'title' => 'When the Wind Hid the Sun',
            'short_title' => 'The Wind Hid the Sun',
            'description' => 'A roaring sea wind covers the bright bay, and the friends work together to bring the sunshine back.',
            'place' => 'Windy Cove',
            'poster' => '/assets/images/episodes/delivery/s2-e08-wind-hid-the-sun.webp',
            'file' => 'Season 2/Episode 8 - When the Wind Hid the Sun.mp4',
            'character' => 'Barik & Tuta',
            'duration' => 339,
        ],
        [
            'number' => 19,
            'season' => 2,
            'season_episode' => 9,
            'slug' => 'big-crab-races',
            'title' => 'The Big Crab Races',
            'short_title' => 'The Big Crab Races',
            'description' => 'The crabs line up for Coral Bay\'s biggest race, where helping a friend matters more than finishing first.',
            'place' => 'Crab Race Beach',
            'poster' => '/assets/images/episodes/delivery/s2-e09-big-crab-races.webp',
            'file' => 'Season 2/Episode 9 - The Big Crab Races.mp4',
            'character' => 'Barik & Krabu',
            'duration' => 316,
        ],
        [
            'number' => 20,
            'season' => 2,
            'season_episode' => 10,
            'slug' => 'when-i-knew-you-were-my-friend',
            'title' => 'When I Knew You Were My Friend',
            'short_title' => 'I Knew You Were My Friend',
            'description' => 'As the sun sets on Coral Bay, the whole crew discovers the small moment that turns a companion into a true friend.',
            'place' => 'Friendship Cove',
            'poster' => '/assets/images/episodes/delivery/s2-e10-knew-you-were-my-friend.webp',
            'file' => 'Season 2/Episode 10 - When I Knew You Were My Friend.mp4',
            'character' => 'The whole bay crew',
            'duration' => 357,
        ],
    ];

    $seasonTotals = array_count_values(array_map(
        static fn(array $episode): int => (int) $episode['season'],
        $episodes
    ));

    foreach ($episodes as &$episode) {
        $episode['watch_url'] = '/story/' . $episode['slug'];
        $episode['media_url'] = '/media/story/' . $episode['slug'];
        $episode['world_slug'] = 'oasis';
        $episode['r2_key'] = sprintf(
            'stories/oasis/season-%d/episode-%02d-%s.mp4',
            (int) $episode['season'],
            (int) $episode['season_episode'],
            (string) $episode['slug']
        );
        $episode['language'] = 'Arabic audio';
        $episode['category'] = 'Barik\'s Big Adventures';
        $episode['season_total'] = $seasonTotals[(int) $episode['season']] ?? 0;
        $episode['is_free'] = 1;
        $episode['aspect_ratio'] = '16:9';
        $episode['duration'] = (int) ($episode['duration'] ?? 0);
    }
    unset($episode);

    return $episodes = localizeEpisodes($episodes, 'oasis');
}

/**
 * @return array<int, array<string, mixed>>
 */
function getMiraEpisodes(): array
{
    static $episodes = null;
    if ($episodes !== null) {
        return $episodes;
    }

    $episodes = [
        [
            'number' => 1,
            'season' => 1,
            'season_episode' => 1,
            'slug' => 'the-planet-that-sneezes',
            'title' => 'The Planet That Sneezes',
            'short_title' => 'The Planet That Sneezes',
            'description' => 'A planet-wide sneeze sends Mira and Bip searching through giant flowers for the tiny problem behind the tremors.',
            'place' => 'The Sneezing Planet',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-01-the-planet-that-sneezes.jpg',
            'file' => 'Mira and the Star Workshop/Episode 1 - The Planet That Sneezes.mp4',
            'character' => 'Mira, Bip & Moh',
            'duration' => 211,
        ],
        [
            'number' => 2,
            'season' => 1,
            'season_episode' => 2,
            'slug' => 'the-clouds-went-home',
            'title' => 'The Clouds Went Home',
            'short_title' => 'The Clouds Went Home',
            'description' => 'When cloud houses drift away from home, Mira and Bip follow the wind to discover what the clouds have been missing.',
            'place' => 'Pushinka',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-02-the-clouds-went-home.jpg',
            'file' => 'Mira and the Star Workshop/Episode 2 - The Clouds Went Home.mp4',
            'character' => 'Mira, Bip & Pukh',
            'duration' => 211,
        ],
        [
            'number' => 3,
            'season' => 1,
            'season_episode' => 3,
            'slug' => 'a-city-inside-a-bubble',
            'title' => 'A City Inside a Bubble',
            'short_title' => 'A City Inside a Bubble',
            'description' => 'A crack threatens a city sealed inside a giant bubble, and Mira must build a repair that can bend without breaking.',
            'place' => 'Bubbleia',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-03-a-city-inside-a-bubble.jpg',
            'file' => 'Mira and the Star Workshop/Episode 3 - A City Inside a Bubble.mp4',
            'character' => 'Mira, Bip & Plyukh',
            'duration' => 211,
        ],
        [
            'number' => 4,
            'season' => 1,
            'season_episode' => 4,
            'slug' => 'when-reflections-stopped-obeying',
            'title' => 'When Reflections Stopped Obeying',
            'short_title' => 'Reflections Stopped Obeying',
            'description' => 'On a world of mirrors, runaway reflections stop copying their owners and lead Mira to a machine that has fallen out of sync.',
            'place' => 'Planet of Reflections',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-04-when-reflections-stopped-obeying.jpg',
            'file' => 'Mira and the Star Workshop/Episode 4 - When Reflections Stopped Obeying.mp4',
            'character' => 'Mira, Bip & Blik',
            'duration' => 211,
        ],
        [
            'number' => 5,
            'season' => 1,
            'season_episode' => 5,
            'slug' => 'the-music-machine-played-everything-at-once',
            'title' => 'The Music Machine Played Everything at Once',
            'short_title' => 'Everything at Once',
            'description' => 'A music machine plays every tune at once, so Mira quiets an entire city to hear the small fault hidden beneath the noise.',
            'place' => 'Zvon',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-05-the-music-machine-played-everything-at-once.jpg',
            'file' => 'Mira and the Star Workshop/Episode 5 - The Music Machine Played Everything at Once.mp4',
            'character' => 'Mira, Bip & Nota',
            'duration' => 211,
        ],
        [
            'number' => 6,
            'season' => 1,
            'season_episode' => 6,
            'slug' => 'bip-and-the-enormous-magnet',
            'title' => 'Bip and the Enormous Magnet',
            'short_title' => 'The Enormous Magnet',
            'description' => 'A magnetic tower has pulled an entire square into one great metal tangle, and Mira must find what a magnet cannot hold in order to take it apart.',
            'place' => 'Prilipala',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-06-bip-and-the-enormous-magnet.jpg',
            'file' => 'Mira and the Star Workshop/Episode 6 - Bip and the Enormous Magnet.mp4',
            'character' => 'Mira, Bip & Sherstinka',
            'duration' => 211,
        ],
        [
            'number' => 7,
            'season' => 1,
            'season_episode' => 7,
            'slug' => 'bip-and-the-lost-sunbeam',
            'title' => 'Bip and the Lost Sunbeam',
            'short_title' => 'The Lost Sunbeam',
            'description' => 'Half of Zerkalia has fallen into shadow, and Mira discovers that the missing sunlight is not gone at all: it only needs a chain of mirrors to guide it home.',
            'place' => 'Zerkalia',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-07-bip-and-the-lost-sunbeam.jpg',
            'file' => 'Mira and the Star Workshop/Episode 7 - Bip and the Lost Sunbeam.mp4',
            'character' => 'Mira, Bip & Blik',
            'duration' => 211,
        ],
        [
            'number' => 8,
            'season' => 1,
            'season_episode' => 8,
            'slug' => 'bip-and-the-too-smooth-planet',
            'title' => 'Bip and the Too-Smooth Planet',
            'short_title' => 'The Too-Smooth Planet',
            'description' => 'Everything slides away on a planet polished far too smooth, so Mira goes hunting for the roughness that lets carts and feet hold on again.',
            'place' => 'Skolzyanka',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-08-bip-and-the-too-smooth-planet.jpg',
            'file' => 'Mira and the Star Workshop/Episode 8 - Bip and the Too-Smooth Planet.mp4',
            'character' => 'Mira, Bip & Tsepka',
            'duration' => 211,
        ],
        [
            'number' => 9,
            'season' => 1,
            'season_episode' => 9,
            'slug' => 'bip-and-the-city-on-a-balance',
            'title' => 'Bip and the City on a Balance',
            'short_title' => 'The City on a Balance',
            'description' => 'A market city balanced on a single pivot keeps tipping, and Mira discovers that where a weight sits matters far more than how heavy it is.',
            'place' => 'Kachelia',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-09-bip-and-the-city-on-a-balance.jpg',
            'file' => 'Mira and the Star Workshop/Episode 9 - Bip and the City on a Balance.mp4',
            'character' => 'Mira, Bip & Rovnya',
            'duration' => 211,
        ],
        [
            'number' => 10,
            'season' => 1,
            'season_episode' => 10,
            'slug' => 'bip-and-the-heat-that-kept-escaping',
            'title' => 'Bip and the Heat That Kept Escaping',
            'short_title' => 'The Escaping Heat',
            'description' => 'The greenhouses stay cold however much fuel goes on the fire, so Mira stops feeding the flames and starts stopping the warmth from slipping away.',
            'place' => 'Merzlyanka',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-10-bip-and-the-heat-that-kept-escaping.jpg',
            'file' => 'Mira and the Star Workshop/Episode 10 - Bip and the Heat That Kept Escaping.mp4',
            'character' => 'Mira, Bip & Tyopa',
            'duration' => 211,
        ],
        [
            'number' => 11,
            'season' => 1,
            'season_episode' => 11,
            'slug' => 'bip-and-the-emptiness-that-holds',
            'title' => 'Bip and the Emptiness That Holds',
            'short_title' => 'The Emptiness That Holds',
            'description' => 'A floating market platform is slowly sinking, and Mira shows that what keeps it up is not weight at all but the emptiness sealed inside.',
            'place' => 'Plavunya',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-11-bip-and-the-emptiness-that-holds.jpg',
            'file' => 'Mira and the Star Workshop/Episode 11 - Bip and the Emptiness That Holds.mp4',
            'character' => 'Mira, Bip & Probka',
            'duration' => 211,
        ],
        [
            'number' => 12,
            'season' => 1,
            'season_episode' => 12,
            'slug' => 'bip-and-the-sail-that-caught-no-wind',
            'title' => 'Bip and the Sail That Caught No Wind',
            'short_title' => 'The Sail and the Wind',
            'description' => 'The windmills stand still on the windiest planet of all, until Mira works out that a slack sail catches nothing and a taut one catches everything.',
            'place' => 'Vetryana',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-12-bip-and-the-sail-that-caught-no-wind.jpg',
            'file' => 'Mira and the Star Workshop/Episode 12 - Bip and the Sail That Caught No Wind.mp4',
            'character' => 'Mira, Bip & Veyka',
            'duration' => 211,
        ],
        [
            'number' => 13,
            'season' => 1,
            'season_episode' => 13,
            'slug' => 'bip-and-the-spring-that-could-not-uncoil',
            'title' => 'Bip and the Spring That Could Not Uncoil',
            'short_title' => 'The Trapped Spring',
            'description' => 'A whole bouncing path has stopped bouncing, and Mira finds the heavy stone blocks that have been quietly pinning every single spring flat.',
            'place' => 'Pruzhinka',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-13-bip-and-the-spring-that-could-not-uncoil.jpg',
            'file' => 'Mira and the Star Workshop/Episode 13 - Bip and the Spring That Could Not Uncoil.mp4',
            'character' => 'Mira, Bip & Skoka',
            'duration' => 211,
        ],
        [
            'number' => 14,
            'season' => 1,
            'season_episode' => 14,
            'slug' => 'bip-and-the-water-that-held-on',
            'title' => 'Bip and the Water That Held On',
            'short_title' => 'The Water That Held On',
            'description' => 'Water refuses to climb the ridge pipe to the dry fields, so Mira mends the broken chain of water that has to hold on to itself all the way up.',
            'place' => 'Perevalka',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-14-bip-and-the-water-that-held-on.jpg',
            'file' => 'Mira and the Star Workshop/Episode 14 - Bip and the Water That Held On.mp4',
            'character' => 'Mira, Bip & Kapa',
            'duration' => 211,
        ],
        [
            'number' => 15,
            'season' => 1,
            'season_episode' => 15,
            'slug' => 'bip-and-the-seeds-that-needed-time',
            'title' => 'Bip and the Seeds That Needed Time',
            'short_title' => 'The Seeds That Needed Time',
            'description' => 'Seeds on a farming planet never sprout because they are dug up and checked every single day, and Mira teaches the hardest repair of all: leaving them be.',
            'place' => 'Rostyana',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-15-bip-and-the-seeds-that-needed-time.jpg',
            'file' => 'Mira and the Star Workshop/Episode 15 - Bip and the Seeds That Needed Time.mp4',
            'character' => 'Mira, Bip & Seva',
            'duration' => 211,
        ],
        [
            'number' => 16,
            'season' => 1,
            'season_episode' => 16,
            'slug' => 'bip-and-the-thread-that-broke-alone',
            'title' => 'Bip and the Thread That Broke Alone',
            'short_title' => 'The Thread That Broke',
            'description' => 'A rope bridge keeps snapping thread by thread, and Mira shows that fibres too weak alone can carry a whole canyon crossing once they are twisted together.',
            'place' => 'Vervinka',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-16-bip-and-the-thread-that-broke-alone.jpg',
            'file' => 'Mira and the Star Workshop/Episode 16 - Bip and the Thread That Broke Alone.mp4',
            'character' => 'Mira, Bip & Pryadka',
            'duration' => 211,
        ],
        [
            'number' => 17,
            'season' => 1,
            'season_episode' => 17,
            'slug' => 'bip-and-the-long-road-upward',
            'title' => 'Bip and the Long Road Upward',
            'short_title' => 'The Long Road Upward',
            'description' => 'A heavy press cannot be shoved straight up onto the terrace, so Mira trades the short steep way for a long gentle ramp that anyone can climb.',
            'place' => 'Podyomka',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-17-bip-and-the-long-road-upward.jpg',
            'file' => 'Mira and the Star Workshop/Episode 17 - Bip and the Long Road Upward.mp4',
            'character' => 'Mira, Bip & Katok',
            'duration' => 211,
        ],
        [
            'number' => 18,
            'season' => 1,
            'season_episode' => 18,
            'slug' => 'bip-and-the-sieve-that-chose-for-itself',
            'title' => 'Bip and the Sieve That Chose for Itself',
            'short_title' => 'The Sieve That Chose',
            'description' => 'A yard of mixed grain needs sorting by hand, so Mira builds a sieve with exactly the right holes and lets it do all the choosing on its own.',
            'place' => 'Seyanka',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-18-bip-and-the-sieve-that-chose-for-itself.jpg',
            'file' => 'Mira and the Star Workshop/Episode 18 - Bip and the Sieve That Chose for Itself.mp4',
            'character' => 'Mira, Bip & Krupka',
            'duration' => 211,
        ],
        [
            'number' => 19,
            'season' => 1,
            'season_episode' => 19,
            'slug' => 'bip-and-the-shadow-they-agreed-on',
            'title' => 'Bip and the Shadow They Agreed On',
            'short_title' => 'The Agreed Shadow',
            'description' => 'A festival cannot begin because nobody agrees when noon has arrived, so Mira raises a single post and lets its shadow settle the argument.',
            'place' => 'Chasovnya',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-19-bip-and-the-shadow-they-agreed-on.jpg',
            'file' => 'Mira and the Star Workshop/Episode 19 - Bip and the Shadow They Agreed On.mp4',
            'character' => 'Mira, Bip & Polden',
            'duration' => 211,
        ],
        [
            'number' => 20,
            'season' => 1,
            'season_episode' => 20,
            'slug' => 'bip-and-the-stones-that-held-each-other',
            'title' => 'Bip and the Stones That Held Each Other',
            'short_title' => 'Stones That Held Each Other',
            'description' => 'A flat bridge keeps collapsing under the weight of its own stones, so Mira builds an arch in which every stone holds its neighbour up.',
            'place' => 'Arkadia',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-20-bip-and-the-stones-that-held-each-other.jpg',
            'file' => 'Mira and the Star Workshop/Episode 20 - Bip and the Stones That Held Each Other.mp4',
            'character' => 'Mira, Bip & Klinok',
            'duration' => 211,
        ],
    ];

    applySeasonSplit($episodes);

    foreach ($episodes as &$episode) {
        $episode['watch_url'] = '/story/' . $episode['slug'];
        $episode['media_url'] = '/media/story/' . $episode['slug'];
        $episode['world_slug'] = 'star-workshop';
        // Built from the series-wide number, never the per-season one, so the
        // stored objects stay put when the series is re-grouped into seasons.
        $episode['r2_key'] = sprintf(
            'stories/mira-star-workshop/season-1/episode-%02d-%s.mp4',
            (int) $episode['number'],
            (string) $episode['slug']
        );
        $episode['language'] = 'Arabic audio';
        $episode['category'] = 'Mira and the Star Workshop';
        $episode['is_free'] = 1;
        $episode['aspect_ratio'] = '16:9';
        $episode['duration'] = 211;
    }
    unset($episode);

    return $episodes = localizeEpisodes($episodes, 'star-workshop');
}

/**
 * @return array<int, array<string, mixed>>
 */
function getPushEpisodes(): array
{
    static $episodes = null;
    if ($episodes !== null) {
        return $episodes;
    }

    $episodes = [
        [
            'number' => 1,
            'season' => 1,
            'season_episode' => 1,
            'slug' => 'where-do-people-go-in-the-morning',
            'title' => 'Where Do People Go in the Morning?',
            'short_title' => 'Where People Go',
            'description' => 'Tim disappears behind the white door every morning, so Push works out that a rainstorm must live in there and packs a rescue kit for his friend.',
            'place' => 'The Bathroom Door',
            'poster' => '/assets/images/episodes/push-big-questions/episode-01-where-do-people-go-in-the-morning.jpg',
            'file' => 'Push and the Big Questions/Episode 1 - Where Do People Go in the Morning.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 2,
            'season' => 1,
            'season_episode' => 2,
            'slug' => 'why-people-hide-food-in-a-cold-cave',
            'title' => 'Why Do People Hide Food in a Cold Cave?',
            'short_title' => 'The Cold Cave',
            'description' => 'Push finds the cold cave where the family keeps its food and decides one very large piece of cheese has been imprisoned long enough.',
            'place' => 'The Kitchen',
            'poster' => '/assets/images/episodes/push-big-questions/episode-02-why-people-hide-food-in-a-cold-cave.jpg',
            'file' => 'Push and the Big Questions/Episode 2 - Why Do People Hide Food in a Cold Cave.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 3,
            'season' => 1,
            'season_episode' => 3,
            'slug' => 'who-steals-my-toys-every-night',
            'title' => 'Who Steals My Toys Every Night?',
            'short_title' => 'Who Steals My Toys',
            'description' => 'Toys keep vanishing overnight, so Push stakes out the living room in the dark to catch the round grey thief red-handed.',
            'place' => 'The Living Room',
            'poster' => '/assets/images/episodes/push-big-questions/episode-03-who-steals-my-toys-every-night.jpg',
            'file' => 'Push and the Big Questions/Episode 3 - Who Steals My Toys Every Night.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 4,
            'season' => 1,
            'season_episode' => 4,
            'slug' => 'why-do-windows-start-to-cry',
            'title' => 'Why Do Windows Start to Cry?',
            'short_title' => 'When Windows Cry',
            'description' => 'Rain runs down the glass and Push is certain the house has become sad, so he sets out to comfort a window that only needs the weather to pass.',
            'place' => 'The Window Seat',
            'poster' => '/assets/images/episodes/push-big-questions/episode-04-why-do-windows-start-to-cry.jpg',
            'file' => 'Push and the Big Questions/Episode 4 - Why Do Windows Start to Cry.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 5,
            'season' => 1,
            'season_episode' => 5,
            'slug' => 'where-does-the-sun-go',
            'title' => 'Where Does the Sun Go?',
            'short_title' => 'Where the Sun Goes',
            'description' => 'Push\'s favourite warm patch keeps shrinking, so he follows the last of the sunlight across the yard to find out where it goes at night.',
            'place' => 'The Sunset Yard',
            'poster' => '/assets/images/episodes/push-big-questions/episode-05-where-does-the-sun-go.jpg',
            'file' => 'Push and the Big Questions/Episode 5 - Where Does the Sun Go.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 6,
            'season' => 1,
            'season_episode' => 6,
            'slug' => 'why-does-the-tree-wave-its-paws',
            'title' => 'Why Does the Tree Wave Its Paws?',
            'short_title' => 'The Waving Tree',
            'description' => 'The old oak keeps waving its branches and Push wants to know who it is calling, until Tim introduces him to something you can feel but never see.',
            'place' => 'The Backyard Oak',
            'poster' => '/assets/images/episodes/push-big-questions/episode-06-why-does-the-tree-wave-its-paws.jpg',
            'file' => 'Push and the Big Questions/Episode 6 - Why Does the Tree Wave Its Paws.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 7,
            'season' => 1,
            'season_episode' => 7,
            'slug' => 'why-do-people-close-their-eyes-at-night',
            'title' => 'Why Do People Close Their Eyes at Night?',
            'short_title' => 'Closed Eyes',
            'description' => 'Tim shuts his eyes and stops answering, so Push keeps watch on the pillow all night to work out where his friend has quietly gone.',
            'place' => 'The Bedroom',
            'poster' => '/assets/images/episodes/push-big-questions/episode-07-why-do-people-close-their-eyes-at-night.jpg',
            'file' => 'Push and the Big Questions/Episode 7 - Why Do People Close Their Eyes at Night.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 8,
            'season' => 1,
            'season_episode' => 8,
            'slug' => 'why-tim-wears-different-fur-every-day',
            'title' => 'Why Does Tim Wear Different Fur Every Day?',
            'short_title' => 'A Different Fur',
            'description' => 'Tim turns up in a new coat every single morning, and Push goes looking for the place a boy must keep all his spare fur.',
            'place' => 'The Wardrobe',
            'poster' => '/assets/images/episodes/push-big-questions/episode-08-why-tim-wears-different-fur-every-day.jpg',
            'file' => 'Push and the Big Questions/Episode 8 - Why Does Tim Wear Different Fur Every Day.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 9,
            'season' => 1,
            'season_episode' => 9,
            'slug' => 'why-big-boxes-come-by-themselves',
            'title' => 'Why Do Big Boxes Come by Themselves?',
            'short_title' => 'The Walking Boxes',
            'description' => 'Large boxes keep arriving at the front door on their own, so Push investigates how they walk and what they might be hiding inside.',
            'place' => 'The Front Hall',
            'poster' => '/assets/images/episodes/push-big-questions/episode-09-why-big-boxes-come-by-themselves.jpg',
            'file' => 'Push and the Big Questions/Episode 9 - Why Do Big Boxes Come by Themselves.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 10,
            'season' => 1,
            'season_episode' => 10,
            'slug' => 'why-does-the-door-talk-by-itself',
            'title' => 'Why Does the Door Talk by Itself?',
            'short_title' => 'The Talking Door',
            'description' => 'A voice comes out of the wall beside the door, and Push answers it very politely before Tim shows him who is really on the other end.',
            'place' => 'The Front Door',
            'poster' => '/assets/images/episodes/push-big-questions/episode-10-why-does-the-door-talk-by-itself.jpg',
            'file' => 'Push and the Big Questions/Episode 10 - Why Does the Door Talk by Itself.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 11,
            'season' => 1,
            'season_episode' => 11,
            'slug' => 'why-the-box-gives-back-the-wrong-bread',
            'title' => 'Why Does the Box Give Back the Wrong Bread?',
            'short_title' => 'The Wrong Bread',
            'description' => 'Bread goes into the box soft and pale and comes back dark and hard, so Push mounts a rescue operation to save the loaf from whatever is swapping it.',
            'place' => 'The Kitchen Counter',
            'poster' => '/assets/images/episodes/push-big-questions/episode-11-why-the-box-gives-back-the-wrong-bread.jpg',
            'file' => 'Push and the Big Questions/Episode 11 - Why Does the Box Give Back the Wrong Bread.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 12,
            'season' => 1,
            'season_episode' => 12,
            'slug' => 'why-clothes-spin-in-a-round-window',
            'title' => 'Why Do Clothes Spin in a Round Window?',
            'short_title' => 'The Round Window',
            'description' => 'A shirt is trapped behind a round window going round and round, so Push sets out to free it and hides the rest of the laundry somewhere safe.',
            'place' => 'The Round Window',
            'poster' => '/assets/images/episodes/push-big-questions/episode-12-why-clothes-spin-in-a-round-window.jpg',
            'file' => 'Push and the Big Questions/Episode 12 - Why Do Clothes Spin in a Round Window.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 13,
            'season' => 1,
            'season_episode' => 13,
            'slug' => 'where-tim-hides-the-little-sun',
            'title' => 'Where Does Tim Hide the Little Sun?',
            'short_title' => 'The Little Sun',
            'description' => 'A small sun lives in the wall and shines only when Tim allows it, so Push leaves out a bowl for it and investigates who keeps sending it away.',
            'place' => 'The Living Room Lamp',
            'poster' => '/assets/images/episodes/push-big-questions/episode-13-where-tim-hides-the-little-sun.jpg',
            'file' => 'Push and the Big Questions/Episode 13 - Where Does Tim Hide the Little Sun.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 14,
            'season' => 1,
            'season_episode' => 14,
            'slug' => 'why-the-flower-drinks-but-never-eats',
            'title' => 'Why Does the Flower Drink but Never Eat?',
            'short_title' => 'The Flower That Drinks',
            'description' => 'The flower on the windowsill is given water but never a single crumb, so Push brings it dinner and a blanket before Tim explains what plants live on.',
            'place' => 'The Windowsill',
            'poster' => '/assets/images/episodes/push-big-questions/episode-14-why-the-flower-drinks-but-never-eats.jpg',
            'file' => 'Push and the Big Questions/Episode 14 - Why Does the Flower Drink but Never Eat.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 15,
            'season' => 1,
            'season_episode' => 15,
            'slug' => 'why-only-the-ball-bounces',
            'title' => 'Why Does the Ball Bounce When Nothing Else Does?',
            'short_title' => 'Only the Ball Bounces',
            'description' => 'Everything in the park lies still except the ball, so Push decides it must be alive and tries to teach it manners before finding out what is inside.',
            'place' => 'The Park',
            'poster' => '/assets/images/episodes/push-big-questions/episode-15-why-only-the-ball-bounces.jpg',
            'file' => 'Push and the Big Questions/Episode 15 - Why Does the Ball Bounce When Nothing Else Does.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 16,
            'season' => 1,
            'season_episode' => 16,
            'slug' => 'why-people-change-their-paws-at-the-door',
            'title' => 'Why Do People Change Their Paws at the Door?',
            'short_title' => 'Changing Paws',
            'description' => 'Tim swaps his paws every time he crosses the doorway, so Push tries on a pair himself and works out why his own never come off.',
            'place' => 'The Front Door',
            'poster' => '/assets/images/episodes/push-big-questions/episode-16-why-people-change-their-paws-at-the-door.jpg',
            'file' => 'Push and the Big Questions/Episode 16 - Why Do People Change Their Paws at the Door.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 17,
            'season' => 1,
            'season_episode' => 17,
            'slug' => 'why-does-the-kettle-breathe',
            'title' => 'Why Does the Kettle Breathe?',
            'short_title' => 'The Kettle Breathes',
            'description' => 'The kettle breathes white clouds out of its spout, so Push tries to catch one and to stop the breathing, then discovers where the water went.',
            'place' => 'The Kitchen Stove',
            'poster' => '/assets/images/episodes/push-big-questions/episode-17-why-does-the-kettle-breathe.jpg',
            'file' => 'Push and the Big Questions/Episode 17 - Why Does the Kettle Breathe.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 18,
            'season' => 1,
            'season_episode' => 18,
            'slug' => 'why-a-black-cat-always-follows-me',
            'title' => 'Why Does a Black Cat Always Follow Me?',
            'short_title' => 'The Black Cat',
            'description' => 'A silent black cat copies Push everywhere and never says hello, so Push tries everything to make friends before Tim shows him what a shadow is.',
            'place' => 'The Garden Path',
            'poster' => '/assets/images/episodes/push-big-questions/episode-18-why-a-black-cat-always-follows-me.jpg',
            'file' => 'Push and the Big Questions/Episode 18 - Why Does a Black Cat Always Follow Me.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 19,
            'season' => 1,
            'season_episode' => 19,
            'slug' => 'why-does-the-box-make-wind',
            'title' => 'Why Does the Box Make Wind?',
            'short_title' => 'The Box That Makes Wind',
            'description' => 'A box in the hot living room blows wind in only one direction, so Push decides to show it the rest of the room and turns the place upside down.',
            'place' => 'The Hot Living Room',
            'poster' => '/assets/images/episodes/push-big-questions/episode-19-why-does-the-box-make-wind.jpg',
            'file' => 'Push and the Big Questions/Episode 19 - Why Does the Box Make Wind.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
        [
            'number' => 20,
            'season' => 1,
            'season_episode' => 20,
            'slug' => 'why-bury-food-nobody-eats',
            'title' => 'Why Bury Food Nobody Eats?',
            'short_title' => 'Food That Grows',
            'description' => 'Tim buries perfectly good seeds in the garden, so Push digs them up to rescue them, then waits many days to learn what burying was for.',
            'place' => 'The Garden Bed',
            'poster' => '/assets/images/episodes/push-big-questions/episode-20-why-bury-food-nobody-eats.jpg',
            'file' => 'Push and the Big Questions/Episode 20 - Why Bury Food Nobody Eats.mp4',
            'character' => 'Push & Tim',
            'duration' => 220,
        ],
    ];

    applySeasonSplit($episodes);

    foreach ($episodes as &$episode) {
        $episode['watch_url'] = '/story/' . $episode['slug'];
        $episode['media_url'] = '/media/story/' . $episode['slug'];
        $episode['world_slug'] = 'push-questions';
        // Built from the series-wide number, never the per-season one, so the
        // stored objects stay put when the series is re-grouped into seasons.
        $episode['r2_key'] = sprintf(
            'stories/push-big-questions/season-1/episode-%02d-%s.mp4',
            (int) $episode['number'],
            (string) $episode['slug']
        );
        $episode['language'] = 'Arabic audio';
        $episode['category'] = 'Push and the Big Questions';
        $episode['is_free'] = 1;
        $episode['aspect_ratio'] = '16:9';
        $episode['duration'] = 220;
    }
    unset($episode);

    return $episodes = localizeEpisodes($episodes, 'push-questions');
}

/**
 * @return array<int, array<string, mixed>>
 */
function getCityEpisodes(): array
{
    static $episodes = null;
    if ($episodes !== null) {
        return $episodes;
    }

    $crew = 'Bobo, Zizi, Tiko, Pixi, Max & Ruby';

    $episodes = [
        [
            'number' => 1,
            'season_episode' => 1,
            'slug' => 'the-cake-that-would-not-ride-straight',
            'title' => 'The Cake That Would Not Ride Straight',
            'short_title' => 'The Tilting Cake',
            'description' => 'Bobo must carry a three-tier cake to the lighthouse party, but every roundabout tips it, until Pixi notices how a keyring hangs level in Zizi\'s taxi.',
            'place' => 'The Lighthouse Party',
            'duration' => 215,
            'character' => 'Bobo, Zizi, Tiko & Pixi',
        ],
        [
            'number' => 2,
            'season_episode' => 2,
            'slug' => 'the-day-without-a-siren',
            'title' => 'The Day Without a Siren',
            'short_title' => 'A Day Without a Siren',
            'description' => 'Ruby promises to spend one whole day without her siren, and has to find quieter ways to clear a road, rescue a cart and warn her friends.',
            'place' => 'Market Street',
            'duration' => 217,
            'character' => 'Ruby, Pixi, Bobo & Max',
        ],
        [
            'number' => 3,
            'season_episode' => 3,
            'slug' => 'the-biggest-bubble-in-town',
            'title' => 'The Biggest Bubble in Town',
            'short_title' => 'The Biggest Bubble',
            'description' => 'A giant soap bubble drifts out of the fountain with Tiko inside it, and the crew must bring it down gently without popping it.',
            'place' => 'The Fountain Square',
            'duration' => 217,
            'character' => 'Pixi, Tiko & Max',
        ],
        [
            'number' => 4,
            'season_episode' => 4,
            'slug' => 'where-did-the-road-markings-go',
            'title' => 'Where Did the Road Markings Go?',
            'short_title' => 'The Missing Markings',
            'description' => 'An over-eager wash has scrubbed every line off the streets, so the crew invent temporary markings until proper ones can be laid.',
            'place' => 'The Unmarked Streets',
            'duration' => 215,
            'character' => 'Zizi, Pixi, Bobo & Max',
        ],
        [
            'number' => 5,
            'season_episode' => 5,
            'slug' => 'pixi-and-the-too-narrow-tunnel',
            'title' => 'Pixi and the Too-Narrow Tunnel',
            'short_title' => 'The Narrow Tunnel',
            'description' => 'Bobo cannot fit through the old tunnel, so tiny Pixi slips inside to find out what has quietly been making it narrower.',
            'place' => 'The Old Tunnel',
            'duration' => 217,
            'character' => 'Bobo, Pixi & Tiko',
        ],
        [
            'number' => 6,
            'season_episode' => 6,
            'slug' => 'the-digger-who-feared-a-butterfly',
            'title' => 'The Digger Who Was Afraid of a Butterfly',
            'short_title' => 'Afraid of a Butterfly',
            'description' => 'Max freezes solid whenever a butterfly lands on him, so the crew build the butterflies a flowerbed of their own and the work can finally go on.',
            'place' => 'The Building Site',
            'duration' => 217,
            'character' => 'Max, Tiko, Ruby & Pixi',
        ],
        [
            'number' => 7,
            'season_episode' => 7,
            'slug' => 'a-hundred-cushions-for-one-sofa',
            'title' => 'A Hundred Cushions for One Sofa',
            'short_title' => 'A Hundred Cushions',
            'description' => 'A tower of a hundred cushions keeps blowing across the square, so the crew turn the whole delivery into a sofa that rings the fountain.',
            'place' => 'The Fountain Ring',
            'duration' => 217,
            'character' => $crew,
        ],
        [
            'number' => 8,
            'season_episode' => 8,
            'slug' => 'the-night-car-wash',
            'title' => 'The Night Car Wash',
            'short_title' => 'The Night Wash',
            'description' => 'The car wash keeps running long after closing time, so Pixi and Ruby stay up to find the sensor that has been looking the wrong way.',
            'place' => 'The Night Wash',
            'duration' => 215,
            'character' => 'Pixi, Ruby & Tiko',
        ],
        [
            'number' => 9,
            'season_episode' => 9,
            'slug' => 'the-party-everyone-came-to-early',
            'title' => 'The Party Everyone Came to Too Early',
            'short_title' => 'Far Too Early',
            'description' => 'The whole town arrives at an empty square hours ahead of time, so the crew rehearse the entire celebration until the real one can begin.',
            'place' => 'The Evening Square',
            'duration' => 215,
            'character' => 'Zizi, Ruby, Pixi, Max & Bobo',
        ],
        [
            'number' => 10,
            'season_episode' => 10,
            'slug' => 'cinema-on-the-garage-wall',
            'title' => 'Cinema on the Garage Wall',
            'short_title' => 'Cinema on the Wall',
            'description' => 'An outdoor film needs a screen, and the crew work their way through wind, too much light and too little distance before the picture finally sits still.',
            'place' => 'The Garage Yard',
            'duration' => 217,
            'character' => $crew,
        ],
        [
            'number' => 11,
            'season_episode' => 11,
            'slug' => 'a-watermelon-across-the-road',
            'title' => 'A Watermelon Across the Road',
            'short_title' => 'The Giant Watermelon',
            'description' => 'The biggest watermelon of the harvest blocks the road and is far too heavy to lift, so the crew build it a path of rollers instead.',
            'place' => 'The Melon Field',
            'duration' => 216,
            'character' => $crew,
        ],
        [
            'number' => 12,
            'season_episode' => 12,
            'slug' => 'the-ship-full-of-rubber-ducks',
            'title' => 'The Ship Full of Rubber Ducks',
            'short_title' => 'The Rubber Ducks',
            'description' => 'Twenty-eight rubber ducks escape towards the beach, and counting them proves hopeless until the crew guide the whole yellow fleet into the dry dock.',
            'place' => 'The Dry Dock',
            'duration' => 216,
            'character' => $crew,
        ],
        [
            'number' => 13,
            'season_episode' => 13,
            'slug' => 'the-bridge-that-creaked-a-tune',
            'title' => 'The Bridge That Creaked a Tune',
            'short_title' => 'The Creaking Bridge',
            'description' => 'A bridge squeaks out a little song whenever anyone crosses it, and the crew discover that a tin of oil is not the answer at all.',
            'place' => 'The Creaky Bridge',
            'duration' => 216,
            'character' => $crew,
        ],
        [
            'number' => 14,
            'season_episode' => 14,
            'slug' => 'the-market-rolled-to-the-sea',
            'title' => 'The Market Rolled Down to the Sea',
            'short_title' => 'The Rolling Market',
            'description' => 'Market stalls begin rolling downhill on their own, and the crew learn that one brake between all of them is never going to be enough.',
            'place' => 'The Rolling Market',
            'duration' => 216,
            'character' => $crew,
        ],
        [
            'number' => 15,
            'season_episode' => 15,
            'slug' => 'the-box-that-sneezed-by-itself',
            'title' => 'The Box That Sneezed by Itself',
            'short_title' => 'The Sneezing Box',
            'description' => 'A delivery box sneezes at every bump in the road, and the crew follow the sound all the way to the folding bellows hidden inside it.',
            'place' => 'The Greenhouse',
            'duration' => 216,
            'character' => 'Tiko, Bobo, Pixi & Ruby',
        ],
        [
            'number' => 16,
            'season_episode' => 16,
            'slug' => 'the-longest-ice-cream-queue',
            'title' => 'The Longest Ice Cream Queue',
            'short_title' => 'The Longest Queue',
            'description' => 'On the hottest day of the year a single ice cream van cannot cope, so the crew mend three carts and turn one long queue into three short ones.',
            'place' => 'The Hottest Square',
            'duration' => 216,
            'character' => $crew,
        ],
        [
            'number' => 17,
            'season_episode' => 17,
            'slug' => 'a-sandcastle-with-a-real-car-park',
            'title' => 'A Sandcastle with a Real Car Park',
            'short_title' => 'The Sandcastle',
            'description' => 'Building a sandcastle on the beach teaches the crew the difference between wet sand and dry, one wobbly test tower at a time.',
            'place' => 'The Beach',
            'duration' => 216,
            'character' => $crew,
        ],
        [
            'number' => 18,
            'season_episode' => 18,
            'slug' => 'the-fountain-that-started-dancing',
            'title' => 'The Fountain That Started Dancing',
            'short_title' => 'The Dancing Fountain',
            'description' => 'The fountain answers every engine with a jet of water, and the crew teach it to take turns until it is ready for an evening performance.',
            'place' => 'The Dancing Fountain',
            'duration' => 216,
            'character' => $crew,
        ],
        [
            'number' => 19,
            'season_episode' => 19,
            'slug' => 'the-secret-of-the-station-bell',
            'title' => 'The Secret of the Old Station Bell',
            'short_title' => 'The Station Bell',
            'description' => 'The old station bell rings three times every morning with nobody in sight, so the crew follow its rope to find out who has been pulling it.',
            'place' => 'The Old Station',
            'duration' => 216,
            'character' => $crew,
        ],
        [
            'number' => 20,
            'season_episode' => 20,
            'slug' => 'the-boat-that-drove-through-town',
            'title' => 'The Boat That Drove Through Town',
            'short_title' => 'The Boat in Town',
            'description' => 'A boat far too wide for the streets has to reach the water, and every single corner between the yard and the harbour is a tight one.',
            'place' => 'The Harbour Slipway',
            'duration' => 216,
            'character' => $crew,
        ],
    ];

    applySeasonSplit($episodes);

    foreach ($episodes as &$episode) {
        $episode['watch_url'] = '/story/' . $episode['slug'];
        $episode['media_url'] = '/media/story/' . $episode['slug'];
        $episode['world_slug'] = 'city-on-wheels';
        // All three built from the series-wide number, never the per-season one,
        // so stored objects and image files stay put when seasons are re-grouped.
        $episode['poster'] = sprintf(
            '/assets/images/episodes/city-on-wheels/episode-%02d-%s.jpg',
            (int) $episode['number'],
            (string) $episode['slug']
        );
        $episode['file'] = sprintf(
            'City on Wheels/Episode %d - %s.mp4',
            (int) $episode['number'],
            str_replace(['?', ':'], '', (string) $episode['title'])
        );
        $episode['r2_key'] = sprintf(
            'stories/city-on-wheels/season-1/episode-%02d-%s.mp4',
            (int) $episode['number'],
            (string) $episode['slug']
        );
        $episode['language'] = 'Arabic audio';
        $episode['category'] = 'City on Wheels';
        $episode['is_free'] = 1;
        $episode['aspect_ratio'] = '16:9';
    }
    unset($episode);

    return $episodes = localizeEpisodes($episodes, 'city-on-wheels');
}

/**
 * @return array<int, array<string, mixed>>
 */
function getCitySeasons(): array
{
    return localizeSeasons(buildSeasonsFromEpisodes(getCityEpisodes(), [
        1 => [
            'title' => 'The Seaside Town',
            'eyebrow' => 'Every neighbour has wheels',
            'description' => 'Ten Arabic-audio call-outs around one sunny harbour town, from a cake that will not ride straight to a film shown on a garage wall.',
            'poster' => '/assets/images/episodes/city-on-wheels/episode-09-the-party-everyone-came-to-early.jpg',
            'theme' => 'ocean',
        ],
        2 => [
            'title' => 'Harbour Summer',
            'eyebrow' => 'The busiest ten days yet',
            'description' => 'Ten more call-outs as summer arrives: runaway rubber ducks, a rolling market, a dancing fountain and a boat that has to drive through town.',
            'poster' => '/assets/images/episodes/city-on-wheels/episode-12-the-ship-full-of-rubber-ducks.jpg',
            'theme' => 'ocean',
        ],
    ], 'city-on-wheels'), 'city-on-wheels');
}

/**
 * @return array<int, array<string, mixed>>
 */
function getRobotEpisodes(): array
{
    static $episodes = null;
    if ($episodes !== null) {
        return $episodes;
    }

    $episodes = [
        ['number' => 1, 'slug' => 'lost-smartphone', 'title' => 'The Lost Smartphone', 'short_title' => 'The Lost Smartphone', 'description' => 'Grandpa\'s missing smartphone sends the whole family searching the house, until Grandma notices it has been in his hand all along.', 'place' => 'The Living Room', 'character' => 'Grandpa, Robik, Papa, Mama & Knopik'],
        ['number' => 2, 'slug' => 'swapped-memory-sticks', 'title' => 'The Swapped Memory Sticks', 'short_title' => 'Swapped Memory Sticks', 'description' => 'A service-day mix-up swaps Papa and Robik\'s memory sticks, and only Knopik notices why they have been behaving like each other all day.', 'place' => 'The Service Bench', 'character' => 'Robik, Papa, Mama & Knopik'],
        ['number' => 3, 'slug' => 'robik-is-tired', 'title' => 'Robik Is Tired', 'short_title' => 'Robik Is Tired', 'description' => 'Robik can barely move, and while Grandpa diagnoses a cold and Papa plans a firmware reset, Mama follows the charger cable to one simple answer.', 'place' => 'The Charging Corner', 'character' => 'Robik, Mama, Papa & Knopik'],
        ['number' => 4, 'slug' => 'cleanup-before-the-neighbors-arrive', 'title' => 'Cleanup Before the Neighbors Arrive', 'short_title' => 'Cleanup Before the Neighbors', 'description' => 'Four automated cleaners spread the mess faster than the family can gather it, until Grandma remembers the one button built for the job.', 'place' => 'The Living Room', 'character' => 'Mama, Papa, Robik & Grandma'],
        ['number' => 5, 'slug' => 'bolt-chooses-a-bed', 'title' => 'Bolt Chooses a Bed', 'short_title' => 'Bolt Chooses a Bed', 'description' => 'Everyone designs a better bed for Bolt, but the robot dog keeps returning to one familiar cushion by the quiet window.', 'place' => 'The Quiet Window', 'character' => 'Bolt, Robik, Mama & Papa'],
        ['number' => 6, 'slug' => 'the-fastest-helper', 'title' => 'The Fastest Helper', 'short_title' => 'The Fastest Helper', 'description' => 'Robik races through three tidying jobs and loses Papa\'s wrench, then learns that a good helper checks the work before announcing victory.', 'place' => 'The Tidy Living Room', 'character' => 'Robik, Mama, Papa & Knopik'],
        ['number' => 7, 'slug' => 'the-spot-on-the-floor', 'title' => 'The Spot on the Floor', 'short_title' => 'The Spot on the Floor', 'description' => 'A warm yellow spot will not scrub away and even moves across the floor, because it was sunshine from the window all along.', 'place' => 'The Sunny Floor', 'character' => 'Robik, Mama, Papa & Knopik'],
        ['number' => 8, 'slug' => 'the-box-nobody-ordered', 'title' => 'The Box Nobody Ordered', 'short_title' => 'The Mystery Box', 'description' => 'A huge soft parcel appears without an owner, and its lilac ribbon finally reveals the rug Grandma quietly ordered weeks ago.', 'place' => 'The Front Door', 'character' => 'Robik, Grandma, Mama & Papa'],
        ['number' => 9, 'slug' => 'the-highest-shelf', 'title' => 'The Highest Shelf', 'short_title' => 'The Highest Shelf', 'description' => 'Robik tries jumping, cushions and a pulley rig to recover his favourite drawing before Knopik points out that Papa is already tall enough.', 'place' => 'The Highest Shelf', 'character' => 'Robik, Papa, Mama & Knopik'],
        ['number' => 10, 'slug' => 'bolts-new-trick', 'title' => 'Bolt\'s New Trick', 'short_title' => 'Bolt\'s New Trick', 'description' => 'Bolt fetches everything except the ball until the family discovers that he follows Knopik\'s bright beep instead of spoken commands.', 'place' => 'The Training Rug', 'character' => 'Bolt, Robik, Knopik & Papa'],
        ['number' => 11, 'slug' => 'counting-everybody', 'title' => 'Counting Everybody', 'short_title' => 'Counting Everybody', 'description' => 'Every count finds only five family members because each counter forgets to include themselves, while Knopik keeps beeping the correct answer: six.', 'place' => 'The Family Line', 'character' => 'The Whole Robot Family'],
        ['number' => 12, 'slug' => 'the-robot-in-the-mirror', 'title' => 'The Robot in the Mirror', 'short_title' => 'The Mirror Robot', 'description' => 'Robik greets the orange robot who copies every move, and Grandma helps him see that he has been waving to his own reflection.', 'place' => 'The Hallway Mirror', 'character' => 'Robik, Grandma, Mama & Papa'],
        ['number' => 13, 'slug' => 'there-is-no-green', 'title' => 'There Is No Green', 'short_title' => 'There Is No Green', 'description' => 'Robik cannot paint grass without green until Knopik mixes blue and yellow on his hands and makes the missing colour himself.', 'place' => 'The Painting Table', 'character' => 'Robik, Knopik, Mama & Papa'],
        ['number' => 14, 'slug' => 'the-window-is-crying', 'title' => 'The Window Is Crying', 'short_title' => 'The Crying Window', 'description' => 'Robik tries to comfort a crying window, while one falling drop shows him that the rain comes from the clouds, not the glass.', 'place' => 'The Rainy Window', 'character' => 'Robik, Mama, Papa & Knopik'],
        ['number' => 15, 'slug' => 'everybody-is-looking-for-something', 'title' => 'Everybody Is Looking for Something', 'short_title' => 'Looking for Something', 'description' => 'Four family members search everywhere for missing belongings while unknowingly carrying and returning one another\'s things.', 'place' => 'Every Cupboard', 'character' => 'Robik, Mama, Papa & Grandpa'],
        ['number' => 16, 'slug' => 'the-knot', 'title' => 'The Knot', 'short_title' => 'The Knot', 'description' => 'Every pull makes the knot in Bolt\'s lead tighter, until Knopik pushes the slack inward and shows the family the opposite approach.', 'place' => 'The Hallway Mat', 'character' => 'Bolt, Robik, Knopik & Papa'],
        ['number' => 17, 'slug' => 'the-wobbly-table', 'title' => 'The Wobbly Table', 'short_title' => 'The Wobbly Table', 'description' => 'Papa measures four perfectly equal legs again and again before Knopik moves the table away from a hidden dip in the rug.', 'place' => 'The Living Room Rug', 'character' => 'Robik, Papa, Mama & Knopik'],
        ['number' => 18, 'slug' => 'the-tallest-tower', 'title' => 'The Tallest Tower', 'short_title' => 'The Tallest Tower', 'description' => 'Robik\'s thin towers keep falling until Knopik\'s flat pile reveals the rule: a tall tower needs a wide base.', 'place' => 'The Building Rug', 'character' => 'Robik, Knopik, Mama & Papa'],
        ['number' => 19, 'slug' => 'the-heavy-chest', 'title' => 'The Heavy Chest', 'short_title' => 'The Heavy Chest', 'description' => 'Levers and pulleys cannot move Robik\'s toy chest, but Knopik makes it lighter by carrying the toys out one at a time.', 'place' => 'The Window Corner', 'character' => 'Robik, Knopik, Mama & Papa'],
        ['number' => 20, 'slug' => 'grandmas-day', 'title' => 'Grandma\'s Day', 'short_title' => 'Grandma\'s Day', 'description' => 'Everyone spends the day making elaborate gifts for Grandma, while Knopik gives her what she wanted most by simply sitting beside her.', 'place' => 'Grandma\'s Chair', 'character' => 'The Whole Robot Family'],
    ];

    applySeasonSplit($episodes);
    $robotDurations = [
        1 => 243,
        2 => 285,
        3 => 316,
        4 => 318,
        5 => 296,
        6 => 291,
        7 => 226,
        8 => 230,
        9 => 214,
        10 => 218,
        11 => 234,
        12 => 200,
        13 => 214,
        14 => 216,
        15 => 219,
        16 => 226,
        17 => 220,
        18 => 212,
        19 => 213,
        20 => 219,
    ];
    foreach ($episodes as &$episode) {
        $episode['watch_url'] = '/story/' . $episode['slug'];
        $episode['media_url'] = '/media/story/' . $episode['slug'];
        $episode['world_slug'] = 'robot-family';
        $episode['poster'] = sprintf(
            '/assets/images/episodes/robot-family/episode-%02d-%s.jpg',
            (int) $episode['number'],
            (string) $episode['slug']
        );
        $episode['r2_key'] = sprintf(
            'stories/robot-family/season-1/episode-%02d-%s.mp4',
            (int) $episode['number'],
            (string) $episode['slug']
        );
        $episode['language'] = 'Arabic audio';
        $episode['category'] = 'The Robot Family';
        $episode['is_free'] = 1;
        $episode['aspect_ratio'] = '16:9';
        $episode['duration'] = $robotDurations[(int) $episode['number']];
    }
    unset($episode);

    return $episodes = localizeEpisodes($episodes, 'robot-family');
}

/**
 * @return array<int, array<string, mixed>>
 */
function getRobotSeasons(): array
{
    return localizeSeasons(buildSeasonsFromEpisodes(getRobotEpisodes(), [
        1 => [
            'title' => 'Little Mysteries at Home',
            'eyebrow' => 'Episodes 1–10',
            'description' => 'Ten Arabic-audio family puzzles, from Grandpa\'s lost smartphone to the beep that finally teaches Bolt a new trick.',
            'poster' => '/assets/images/episodes/robot-family/episode-01-lost-smartphone.jpg',
            'theme' => 'lavender',
        ],
        2 => [
            'title' => 'The Family Thinks Again',
            'eyebrow' => 'Episodes 11–20',
            'description' => 'Ten more warm household mysteries where careful looking beats complicated machinery every time.',
            'poster' => '/assets/images/episodes/robot-family/episode-20-grandmas-day.jpg',
            'theme' => 'lavender',
        ],
    ], 'robot-family'), 'robot-family');
}

/**
 * @return array<int, array<string, mixed>>
 */
function getOasisSeasons(): array
{
    $seasons = [
        1 => [
            'number' => 1,
            'title' => 'The Golden Oasis',
            'eyebrow' => 'The adventure begins',
            'description' => 'Nine warm, funny stories across palm gardens, sandy trails, and one very lively oasis.',
            'poster' => '/assets/images/episodes/delivery/s1-e09-thousand-stars.jpg',
            'theme' => 'sun',
            'episodes' => [],
        ],
        2 => [
            'number' => 2,
            'title' => 'Coral Bay',
            'eyebrow' => 'A splashy new chapter',
            'description' => 'Ten seaside stories with turquoise coves, coral puzzles, and new friends beneath the sun.',
            'poster' => '/assets/images/episodes/season-2-full-cast.webp',
            'theme' => 'ocean',
            'episodes' => [],
        ],
    ];

    foreach (getOasisEpisodes() as $episode) {
        $seasons[(int) $episode['season']]['episodes'][] = $episode;
    }

    foreach ($seasons as &$season) {
        $season['episode_count'] = count($season['episodes']);
        $season['start_url'] = $season['episodes'][0]['watch_url'] ?? '/world/oasis';
    }
    unset($season);

    return localizeSeasons($seasons, 'oasis');
}

/**
 * @return array<int, array<string, mixed>>
 */
function getMiraSeasons(): array
{
    return localizeSeasons(buildSeasonsFromEpisodes(getMiraEpisodes(), [
        1 => [
            'title' => 'First Flights',
            'eyebrow' => 'The workshop is open',
            'description' => 'Ten Arabic-audio repair missions, from a planet that will not stop sneezing to greenhouses that keep losing their warmth.',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-01-the-planet-that-sneezes.jpg',
            'theme' => 'sky',
        ],
        2 => [
            'title' => 'Further Orbits',
            'eyebrow' => 'Ten harder repairs',
            'description' => 'Ten more missions where the fix is never the obvious one, ending with a bridge of stones that hold each other up.',
            'poster' => '/assets/images/episodes/mira-star-workshop/episode-20-bip-and-the-stones-that-held-each-other.jpg',
            'theme' => 'sky',
        ],
    ], 'star-workshop'), 'star-workshop');
}

/**
 * @return array<int, array<string, mixed>>
 */
function getPushSeasons(): array
{
    return localizeSeasons(buildSeasonsFromEpisodes(getPushEpisodes(), [
        1 => [
            'title' => 'Everyday Mysteries',
            'eyebrow' => 'One question per episode',
            'description' => 'Ten Arabic-audio stories in which the flat, the yard and the front door all turn out to be worth asking about.',
            'poster' => '/assets/images/episodes/push-big-questions/episode-05-where-does-the-sun-go.jpg',
            'theme' => 'meadow',
        ],
        2 => [
            'title' => 'More Big Questions',
            'eyebrow' => 'Ten new theories',
            'description' => 'Ten more questions and ten more perfectly logical wrong answers, from a box that swaps bread to seeds buried on purpose.',
            'poster' => '/assets/images/episodes/push-big-questions/episode-20-why-bury-food-nobody-eats.jpg',
            'theme' => 'meadow',
        ],
    ], 'push-questions'), 'push-questions');
}

/**
 * @return array<string, mixed>|null
 */
function findOasisEpisode(string $slug): ?array
{
    foreach (getOasisEpisodes() as $episode) {
        if ($episode['slug'] === $slug) {
            return $episode;
        }
    }

    return null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function getStoryWorldEpisodes(string $worldSlug): array
{
    return match ($worldSlug) {
        'oasis' => getOasisEpisodes(),
        'star-workshop' => getMiraEpisodes(),
        'push-questions' => getPushEpisodes(),
        'city-on-wheels' => getCityEpisodes(),
        'robot-family' => getRobotEpisodes(),
        default => [],
    };
}

/**
 * @return array<int, array<string, mixed>>
 */
function getStoryWorldSeasons(string $worldSlug): array
{
    return match ($worldSlug) {
        'oasis' => getOasisSeasons(),
        'star-workshop' => getMiraSeasons(),
        'push-questions' => getPushSeasons(),
        'city-on-wheels' => getCitySeasons(),
        'robot-family' => getRobotSeasons(),
        default => [],
    };
}

/**
 * @return array<string, mixed>|null
 */
function findStoryEpisode(string $slug): ?array
{
    foreach (['oasis', 'star-workshop', 'push-questions', 'city-on-wheels', 'robot-family'] as $worldSlug) {
        foreach (getStoryWorldEpisodes($worldSlug) as $episode) {
            if ($episode['slug'] === $slug) {
                return $episode;
            }
        }
    }

    return null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function getCharacterCollection(): array
{
    return localizeCharacters([
        [
            'slug' => 'barik',
            'name' => 'Barik',
            'series' => "Barik's Big Adventures",
            'species' => 'Oasis camel',
            'image' => '/assets/images/characters/barik.webp',
            'unlock_episodes' => range(1, 19),
            'likes' => 'Treasure hunts',
            'fears' => 'Missing a good joke',
            'food' => 'Date biscuits',
            'fact' => 'Camels can close their nostrils when sand starts to blow.',
        ],
        [
            'slug' => 'lulu',
            'name' => 'Lulu',
            'series' => "Barik's Big Adventures",
            'species' => 'Oasis flamingo',
            'image' => '/assets/images/characters/lulu.webp',
            'unlock_episodes' => range(1, 19),
            'likes' => 'Dancing in perfect rhythm',
            'fears' => 'A song ending too soon',
            'food' => 'Watermelon slices',
            'fact' => 'Flamingos can stand on one leg for long periods.',
        ],
        [
            'slug' => 'krabu',
            'name' => 'Krabu',
            'series' => "Barik's Big Adventures",
            'species' => 'Oasis crab',
            'image' => '/assets/images/characters/krabu.webp',
            'unlock_episodes' => range(1, 19),
            'likes' => 'Collecting meaningful gifts',
            'fears' => 'Walking in a perfectly straight line',
            'food' => 'Crunchy seaweed',
            'fact' => 'Crabs usually move sideways because of how their legs bend.',
        ],
        [
            'slug' => 'faris',
            'name' => 'Faris',
            'series' => "Barik's Big Adventures",
            'species' => 'Desert falcon',
            'image' => '/assets/images/characters/faris.webp',
            'unlock_episodes' => range(1, 19),
            'likes' => 'Maps and high places',
            'fears' => 'Arriving too late',
            'food' => 'Sun-warmed figs',
            'fact' => 'Falcons can spot small movement from very far away.',
        ],
        [
            'slug' => 'giko',
            'name' => 'Giko',
            'series' => "Barik's Big Adventures",
            'species' => 'Cafe gecko',
            'image' => '/assets/images/characters/giko.webp',
            'unlock_episodes' => range(2, 19),
            'likes' => 'Inventing tiny recipes',
            'fears' => 'An empty cafe',
            'food' => 'Crunchy beetle bites',
            'fact' => 'Geckos can climb smooth walls using millions of tiny hairs on their toes.',
        ],
        [
            'slug' => 'tuta',
            'name' => 'Tuta',
            'series' => "Barik's Big Adventures",
            'species' => 'Wise sea turtle',
            'image' => '/assets/images/characters/tuta.webp',
            'unlock_episodes' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 14, 17, 19],
            'likes' => 'Reading tides and maps',
            'fears' => 'Friends ignoring the current',
            'food' => 'Sea grass',
            'fact' => 'Sea turtles use Earth\'s magnetic field to navigate long journeys.',
        ],
        [
            'slug' => 'nuri',
            'name' => 'Nuri',
            'series' => "Barik's Big Adventures",
            'species' => 'Coral Bay dolphin',
            'image' => '/assets/images/characters/nuri.webp',
            'unlock_episodes' => [10, 11, 12, 13, 14, 16, 19],
            'likes' => 'Leaping through sparkling waves',
            'fears' => 'Being stranded in the shallows',
            'food' => 'Silver fish',
            'fact' => 'Dolphins use clicks and echoes to understand what is around them.',
        ],
        [
            'slug' => 'inky',
            'name' => 'Inky',
            'series' => "Barik's Big Adventures",
            'species' => 'Puzzle octopus',
            'image' => '/assets/images/characters/inky.webp',
            'unlock_episodes' => [11, 12, 13, 14, 16, 19],
            'likes' => 'Riddles',
            'fears' => 'Tangling his own tentacles',
            'food' => 'Shrimp',
            'fact' => 'Octopuses are excellent problem solvers.',
        ],
        [
            'slug' => 'hani',
            'name' => 'Hani',
            'series' => "Barik's Big Adventures",
            'species' => 'Hermit crab',
            'image' => '/assets/images/characters/hani.webp',
            'unlock_episodes' => [13, 14, 15, 19],
            'likes' => 'Shiny shells',
            'fears' => 'Outgrowing a cozy home',
            'food' => 'Seaweed salad',
            'fact' => 'Hermit crabs sometimes form a queue to swap shells.',
        ],

        // ── City on Wheels ────────────────────────────────────────────
        [
            'slug' => 'bobo',
            'name' => 'Bobo',
            'series' => 'City on Wheels',
            'species' => 'Delivery truck',
            'image' => '/assets/images/characters/bobo.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'A load roped down properly',
            'fears' => 'Tipping a cake on a roundabout',
            'food' => 'A long drink at the pump',
            'fact' => 'A wide, low load is far harder to tip over than a tall one.',
        ],
        [
            'slug' => 'zizi',
            'name' => 'Zizi',
            'series' => 'City on Wheels',
            'species' => 'Town taxi',
            'image' => '/assets/images/characters/zizi.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Knowing every shortcut',
            'fears' => 'A road closed with no sign',
            'food' => 'Fresh air through an open window',
            'fact' => 'The shortest route and the quickest route are often not the same one.',
        ],
        [
            'slug' => 'tiko',
            'name' => 'Tiko',
            'series' => 'City on Wheels',
            'species' => 'Little tractor',
            'image' => '/assets/images/characters/tiko.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Stacking things square and neat',
            'fears' => 'A wobbly foundation',
            'food' => 'Sunflower seeds from the field',
            'fact' => 'Tractor tyres have deep treads so they grip soft ground.',
        ],
        [
            'slug' => 'pixi',
            'name' => 'Pixi',
            'series' => 'City on Wheels',
            'species' => 'Scooter',
            'image' => '/assets/images/characters/pixi.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Gaps nobody else fits through',
            'fears' => 'Being told to wait outside',
            'food' => 'One scoop of ice cream',
            'fact' => 'Being small is an advantage exactly when the space is tight.',
        ],

        // ── Mira and the Star Workshop ────────────────────────────────
        [
            'slug' => 'mira',
            'name' => 'Mira',
            'series' => 'Mira and the Star Workshop',
            'species' => 'Planet mechanic',
            'image' => '/assets/images/characters/mira.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Looking closely before touching anything',
            'fears' => 'Fixing the wrong thing',
            'food' => 'Toast eaten over the workbench',
            'fact' => 'Most repairs begin with working out what is actually broken.',
        ],
        [
            'slug' => 'bip',
            'name' => 'Bip',
            'series' => 'Mira and the Star Workshop',
            'species' => 'Workshop robot',
            'image' => '/assets/images/characters/bip.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Holding the lamp steady',
            'fears' => 'Dropping the small screw',
            'food' => 'A short charge, often',
            'fact' => 'Two beeps from Bip means yes; one means he is thinking.',
        ],
        [
            'slug' => 'iskorka',
            'name' => 'Iskorka',
            'series' => 'Mira and the Star Workshop',
            'species' => 'Workshop rocket',
            'image' => '/assets/images/characters/iskorka.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'A tidy landing on all four legs',
            'fears' => 'Loose tools rolling about in flight',
            'food' => 'A full tank before a long hop',
            'fact' => 'The whole workshop travels inside her, so nothing is ever left behind.',
        ],

        // ── Push and the Big Questions ────────────────────────────────
        [
            'slug' => 'push',
            'name' => 'Push',
            'series' => 'Push and the Big Questions',
            'species' => 'Grey kitten',
            'image' => '/assets/images/characters/push.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Asking why, then asking again',
            'fears' => 'A question nobody will answer',
            'food' => 'Whatever Tim is having',
            'fact' => 'A cat tilts its head to move its ears, not its eyes.',
        ],
        [
            'slug' => 'tim',
            'name' => 'Tim',
            'series' => 'Push and the Big Questions',
            'species' => 'Push\'s boy',
            'image' => '/assets/images/characters/tim.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Explaining things patiently',
            'fears' => 'Running late in the morning',
            'food' => 'Toast with honey',
            'fact' => 'The lamp Tim switches on is the little sun Push keeps looking for.',
        ],

        // ── The Robot Family ──────────────────────────────────────────
        [
            'slug' => 'robik',
            'name' => 'Robik',
            'series' => 'The Robot Family',
            'species' => 'Robot child',
            'image' => '/assets/images/characters/robik.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Trying it himself first',
            'fears' => 'A tower that will not stay up',
            'food' => 'A biscuit, held in one hand while working',
            'fact' => 'A tall tower needs a wide bottom, which Robik learned the hard way.',
        ],
        [
            'slug' => 'papa',
            'name' => 'Papa',
            'series' => 'The Robot Family',
            'species' => 'Robot father',
            'image' => '/assets/images/characters/papa.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Measuring to a tenth of a millimetre',
            'fears' => 'An answer he cannot calculate',
            'food' => 'Something efficient',
            'fact' => 'Papa builds a device for everything, including problems that need no device.',
        ],
        [
            'slug' => 'mama',
            'name' => 'Mama',
            'series' => 'The Robot Family',
            'species' => 'Robot mother',
            'image' => '/assets/images/characters/mama.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'The practical solution',
            'fears' => 'Nothing much, honestly',
            'food' => 'Whatever is already made',
            'fact' => 'Mama is usually right first, and says so only once.',
        ],
        [
            'slug' => 'grandpa',
            'name' => 'Grandpa',
            'series' => 'The Robot Family',
            'species' => 'Robot grandfather',
            'image' => '/assets/images/characters/grandpa.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Remembering how it was done before',
            'fears' => 'Being asked which house that was',
            'food' => 'Something from the old recipe',
            'fact' => 'Grandpa\'s stories are always true and almost never useful.',
        ],
        [
            'slug' => 'grandma',
            'name' => 'Grandma',
            'series' => 'The Robot Family',
            'species' => 'Robot grandmother',
            'image' => '/assets/images/characters/grandma.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Sitting quietly until she is needed',
            'fears' => 'A house where everyone is busy elsewhere',
            'food' => 'Tea, unhurried',
            'fact' => 'Grandma says one word at the end of an episode, and it is always the right one.',
        ],
        [
            'slug' => 'knopik',
            'name' => 'Knopik',
            'series' => 'The Robot Family',
            'species' => 'Baby robot',
            'image' => '/assets/images/characters/knopik.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Poking one finger into things',
            'fears' => 'Being carried away from the interesting bit',
            'food' => 'A very small charge',
            'fact' => 'Knopik cannot talk yet and solves the problem anyway, usually first.',
        ],
        [
            'slug' => 'bolt',
            'name' => 'Bolt',
            'series' => 'The Robot Family',
            'species' => 'Robot dog',
            'image' => '/assets/images/characters/bolt.webp',
            'unlock_episodes' => range(1, 20),
            'likes' => 'Waiting by the door with the lead',
            'fears' => 'A walk that keeps being postponed',
            'food' => 'A bolt-shaped treat',
            'fact' => 'Bolt fetches whatever he thinks you meant, which is rarely the ball.',
        ],
    ]);
}


/**
 * Resolve an episode file without allowing arbitrary paths from the request.
 */
function getOasisEpisodeFile(array $episode): string
{
    $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $episode['file']);

    return rtrim(CARTOON_LIBRARY_PATH, '/\\') . DIRECTORY_SEPARATOR . $relativePath;
}

/**
 * Resolve the stable R2 object key used by the production story player.
 */
function getOasisEpisodeR2Key(array $episode): string
{
    return (string) ($episode['r2_key'] ?? '');
}

/**
 * Resolve a curated episode file without allowing arbitrary request paths.
 */
function getStoryEpisodeFile(array $episode): string
{
    return getOasisEpisodeFile($episode);
}

/**
 * Resolve the stable R2 object key used by any curated story player.
 */
function getStoryEpisodeR2Key(array $episode): string
{
    return (string) ($episode['r2_key'] ?? '');
}
