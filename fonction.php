<?php
function createGobeblin(PDO $db, string $name, int $age, int $strength, int $tribeId, int $soulId): int {
    $stmt = $db->prepare("
        INSERT INTO gobeblins (name, age, strength, tribe_id, soul_id)
        VALUES (:name, :age, :strength, :tribe_id, :soul_id)
        RETURNING id
    ");

    $stmt->execute([
        ':name' => $name,
        ':age' => $age,
        ':strength' => $strength,
        ':tribe_id' => $tribeId,
        ':soul_id' => $soulId,
    ]);

    // PostgreSQL retourne le résultat dans fetch
    $id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

    return (int) $id;
}

function createZone(PDO $db, string $name, string $type, int $levelMin = 1, int $levelMax = 100, ?string $description = null, ?int $parentZoneId = null): int {
    $stmt = $db->prepare("
        INSERT INTO zones (name, type, level_min, level_max, description, parent_zone_id)
        VALUES (:name, :type, :level_min, :level_max, :description, :parent_zone_id)
        RETURNING id
    ");

    $stmt->execute([
        ':name' => $name,
        ':type' => $type,
        ':level_min' => $levelMin,
        ':level_max' => $levelMax,
        ':description' => $description,
        ':parent_zone_id' => $parentZoneId,
    ]);

    return (int) $stmt->fetch(PDO::FETCH_ASSOC)['id'];
}

function createActionPoints(PDO $db, int $playerId, int $maxPoints = 6): int {
    $stmt = $db->prepare("
        INSERT INTO action_points (player_id, current_points, max_points)
        VALUES (:player_id, :current_points, :max_points)
        RETURNING id
    ");

    $stmt->execute([
        ':player_id' => $playerId,
        ':current_points' => $maxPoints,
        ':max_points' => $maxPoints,
    ]);

    return (int) $stmt->fetch(PDO::FETCH_ASSOC)['id'];
}

function useActionPoints(PDO $db, int $playerId, int $pointsToUse): bool {
    $db->beginTransaction();

    // Lock la ligne pour éviter les conflits concurrents
    $stmt = $db->prepare("
        SELECT current_points
        FROM action_points
        WHERE player_id = :player_id
        FOR UPDATE
    ");
    $stmt->execute([':player_id' => $playerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['current_points'] < $pointsToUse) {
        $db->rollBack();
        return false; // Pas assez de PA
    }

    $stmt = $db->prepare("
        UPDATE action_points
        SET current_points = current_points - :points, updated_at = NOW()
        WHERE player_id = :player_id
    ");
    $stmt->execute([
        ':points' => $pointsToUse,
        ':player_id' => $playerId
    ]);

    $db->commit();
    return true;
}

?>


