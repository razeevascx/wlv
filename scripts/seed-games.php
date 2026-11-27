<?php

// Run with: php scripts/seed-games.php. Ratings are sample catalogue data.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../config/database.php';

$games = [
    ['Kingdom Come: Deliverance II', 'A story-driven role-playing adventure through medieval Bohemia, following Henry on a journey of conflict and discovery.', '2025-02-04', 9],
    ["Sid Meier's Civilization VII", 'Build an empire across historical ages through exploration, diplomacy, scientific progress and turn-based strategy.', '2025-02-11', 7],
    ['Avowed', 'Explore the Living Lands in a first-person fantasy role-playing adventure with magic, melee combat and branching choices.', '2025-02-18', 8],
    ['Monster Hunter Wilds', 'Track powerful monsters through changing environments and craft equipment in a cooperative action role-playing adventure.', '2025-02-28', 8],
    ['Split Fiction', 'A cooperative adventure following two writers through intertwined science-fiction and fantasy worlds.', '2025-03-06', 9],
    ['South of Midnight', 'A Southern Gothic action adventure following Hazel as she explores folklore and discovers her weaving powers.', '2025-04-08', 8],
    ['Blue Prince', 'Explore a mysterious mansion by choosing rooms, solving puzzles and uncovering the secrets of its changing floor plan.', '2025-04-10', 9],
    ['Clair Obscur: Expedition 33', 'Lead an expedition through a surreal fantasy world in a role-playing game combining turn-based battles with real-time actions.', '2025-04-24', 9],
    ['DOOM: The Dark Ages', 'Battle demonic armies as the Doom Slayer in a medieval-inspired first-person shooter with a shield and heavy weaponry.', '2025-05-15', 8],
    ['Elden Ring Nightreign', 'Team up in a cooperative action-survival adventure, gathering strength before confronting powerful night bosses.', '2025-05-30', 8],
    ['Mario Kart World', 'Race across an interconnected world with familiar Nintendo characters, varied courses and competitive multiplayer.', '2025-06-05', 9],
    ['Death Stranding 2: On the Beach', 'Continue Sam Porter Bridges’ journey to reconnect isolated communities in a cinematic science-fiction adventure.', '2025-06-26', 9],
    ['Donkey Kong Bananza', 'Smash through destructible environments with Donkey Kong and Pauline in a three-dimensional platforming adventure.', '2025-07-17', 9],
    ['Hollow Knight: Silksong', 'Guide Hornet through a mysterious kingdom with acrobatic combat, exploration and challenging encounters.', '2025-09-04', 9],
    ['Hades II', 'Play as Melinoe in a roguelike action adventure inspired by Greek mythology, combining witchcraft with repeatable combat runs.', '2025-09-25', 9],
    ['Ghost of Yōtei', 'Follow Atsu on a quest for vengeance through northern Japan in an open-world samurai action adventure.', '2025-10-02', 9],
    ['Nioh 3', 'Face supernatural enemies in an action role-playing adventure featuring samurai and ninja combat styles.', '2026-02-06', 8],
    ['Resident Evil Requiem', 'A survival-horror adventure combining tense exploration, dangerous encounters and the mysteries of the Resident Evil universe.', '2026-02-27', 9],
    ['Monster Hunter Stories 3: Twisted Reflection', 'Raise monster companions and explore a fantasy world in a turn-based role-playing adventure.', '2026-03-13', 8],
    ['Crimson Desert', 'Explore the continent of Pywel in an open-world action adventure featuring combat, exploration and the story of Kliff.', '2026-03-19', 8],
];

$mysqli->begin_transaction();
try {
    $statement = $mysqli->prepare(
        'INSERT INTO videogames (game_name, game_description, released_date, rating)
         SELECT ?, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM videogames WHERE game_name = ?)'
    );
    $inserted = 0;
    foreach ($games as [$name, $description, $date, $rating]) {
        $statement->bind_param('sssis', $name, $description, $date, $rating, $name);
        $statement->execute();
        $inserted += $statement->affected_rows;
    }
    $mysqli->commit();
    echo "Added {$inserted} games; existing entries were preserved. Ratings are sample data.\n";
} catch (Throwable $exception) {
    $mysqli->rollback();
    throw $exception;
}
