<?php
function createGobelin(PDO $pdo, array $data): void {
    try {
        $pdo->beginTransaction();

        // 1. Création de la tribu (si nécessaire)
        if (!isset($data['tribe_id'])) {
            $stmt = $pdo->prepare("
                INSERT INTO tribe (id, name, datecreation, description, blazon)
                VALUES (gen_random_uuid(), :name, NOW(), :description, :blazon)
                RETURNING id
            ");
            $stmt->execute([
                'name' => $data['tribe_name'],
                'description' => $data['tribe_description'] ?? null,
                'blazon' => $data['tribe_blazon'],
            ]);
            $tribeId = $stmt->fetchColumn();
        } else {
            $tribeId = $data['tribe_id'];
        }

        // 2. Insertion du gobelin (player)
        $stmt = $pdo->prepare("
            INSERT INTO player (
                id, name, gender, description, imageavatar, datecreation, datelastdeath,
                turnduration, nextturn, actionpoint, hitpoints, hitpointsmax, hunger,
                kills, pvpkills, turnattckdomage, deathcounts, dogde, attack, damagephy, damagemag,
                regeneration, perception, active, psychicresist, psychicmast,
                physicalresist, physicalmast, magicresist, magicmast,
                obscureresist, obscuremast, socialresist, socialmast,
                technologyresist, technologymast, x, y, n, pvp,
                nbapmove, nbapattack, nbattack, update_, saveuniquemob,
                rangex, rangey, size, armphy, armmag, status
            )
            VALUES (
                gen_random_uuid(), :name, :gender, :description, :imageavatar, NOW(), :datelastdeath,
                :turnduration, :nextturn, :actionpoint, :hitpoints, :hitpointsmax, :hunger,
                :kills, :pvpkills, :turnattckdomage, :deathcounts, :dogde, :attack, :damagephy, :damagemag,
                :regeneration, :perception, :active, :psychicresist, :psychicmast,
                :physicalresist, :physicalmast, :magicresist, :magicmast,
                :obscureresist, :obscuremast, :socialresist, :socialmast,
                :technologyresist, :technologymast, :x, :y, :n, :pvp,
                :nbapmove, :nbapattack, :nbattack, NOW(), :saveuniquemob,
                :rangex, :rangey, :size, :armphy, :armmag, :status
            )
            RETURNING id
        ");
        $stmt->execute($data);
        $playerId = $stmt->fetchColumn();

        // 3. Insertion de l'âme (soul)
        $stmt = $pdo->prepare("
            INSERT INTO soul (
                id, xp, ip, iptotal, level, trollcanines,
                nextlevelcount, startingbonusstat, player
            )
            VALUES (
                gen_random_uuid(), :xp, :ip, :iptotal, :level, :trollcanines,
                :nextlevelcount, :startingbonusstat::statstype, :player
            )
        ");
        $stmt->execute([
            'xp' => $data['xp'],
            'ip' => $data['ip'],
            'iptotal' => $data['iptotal'],
            'level' => $data['level'],
            'trollcanines' => $data['trollcanines'],
            'nextlevelcount' => $data['nextlevelcount'],
            'startingbonusstat' => $data['startingbonusstat'],
            'player' => $playerId,
        ]);

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        throw new RuntimeException("Erreur lors de la création du gobelin : " . $e->getMessage());
    }
}


function createGameWorld(PDO $pdo, array $data): ?string {
    // Ici on ne fournit pas l'id en PHP, on demande à Postgres de générer via gen_random_uuid()
    $sql = "INSERT INTO gameworld (
        id, name, xsuperior, xinferior, ysuperior, yinferior, nsuperior, ninferior,
        architecture, script, hitpoints, description, physicalresist, psychicresist,
        obscureresist, technologyresist, magicresist, sociaresist, arm, taxes,
        update_, instancetype, proprietaryentity, proprietaryclan, proprietarytribe
    ) VALUES (
        gen_random_uuid(), :name, :xsuperior, :xinferior, :ysuperior, :yinferior, :nsuperior, :ninferior,
        :architecture, :script, :hitpoints, :description, :physicalresist, :psychicresist,
        :obscureresist, :technologyresist, :magicresist, :sociaresist, :arm, :taxes,
        :update_, :instancetype, :proprietaryentity, :proprietaryclan, :proprietarytribe
    ) RETURNING id";

    $stmt = $pdo->prepare($sql);

    $params = [
        ':name' => $data['name'],
        ':xsuperior' => $data['xsuperior'],
        ':xinferior' => $data['xinferior'],
        ':ysuperior' => $data['ysuperior'],
        ':yinferior' => $data['yinferior'],
        ':nsuperior' => $data['nsuperior'],
        ':ninferior' => $data['ninferior'],
        ':architecture' => $data['architecture'],
        ':script' => $data['script'],
        ':hitpoints' => $data['hitpoints'],
        ':description' => $data['description'],
        ':physicalresist' => $data['physicalresist'],
        ':psychicresist' => $data['psychicresist'],
        ':obscureresist' => $data['obscureresist'],
        ':technologyresist' => $data['technologyresist'],
        ':magicresist' => $data['magicresist'],
        ':sociaresist' => $data['sociaresist'],
        ':arm' => $data['arm'],
        ':taxes' => $data['taxes'] ?? null,
        ':update_' => $data['update_'],  // format 'Y-m-d H:i:s'
        ':instancetype' => $data['instancetype'],
        ':proprietaryentity' => $data['proprietaryentity'] ?? null,
        ':proprietaryclan' => $data['proprietaryclan'] ?? null,
        ':proprietarytribe' => $data['proprietarytribe'] ?? null,
    ];

    if ($stmt->execute($params)) {
        // Récupérer l'id généré par Postgres
        return $stmt->fetchColumn();
    }

    return null;
}

function createGameWorldType(PDO $pdo, array $data): ?string {
    $sql = "INSERT INTO gameworldtype (
        id, name, xsuperiormin, xsuperiormax, xinferiormin, xinferiormax,
        ysuperiormin, ysuperiormax, yinferiormax, yinferiormin,
        nsuperiormax, nsuperiormin, ninferiormax, ninferiormin,
        matter, architecture, description, script,
        hitpoints,
        psychicresistmin, psychicresistmax,
        physicalresistmin, physicalresistmax,
        magicresistmin, magicresistmax,
        obscureresistmax, obscureresistmin,
        technologyresistmin, technologyresistmax,
        socialresistmax, socialresistmin,
        arm,
        gender,
        taxesorigine,
        instanceupgrade
    ) VALUES (
        gen_random_uuid(), :name, :xsuperiormin, :xsuperiormax, :xinferiormin, :xinferiormax,
        :ysuperiormin, :ysuperiormax, :yinferiormax, :yinferiormin,
        :nsuperiormax, :nsuperiormin, :ninferiormax, :ninferiormin,
        :matter, :architecture, :description, :script,
        :hitpoints,
        :psychicresistmin, :psychicresistmax,
        :physicalresistmin, :physicalresistmax,
        :magicresistmin, :magicresistmax,
        :obscureresistmax, :obscureresistmin,
        :technologyresistmin, :technologyresistmax,
        :socialresistmax, :socialresistmin,
        :arm,
        :gender,
        :taxesorigine,
        :instanceupgrade
    ) RETURNING id";

    $stmt = $pdo->prepare($sql);

    $params = [
        ':name' => $data['name'],
        ':xsuperiormin' => $data['xsuperiormin'],
        ':xsuperiormax' => $data['xsuperiormax'],
        ':xinferiormin' => $data['xinferiormin'],
        ':xinferiormax' => $data['xinferiormax'],
        ':ysuperiormin' => $data['ysuperiormin'],
        ':ysuperiormax' => $data['ysuperiormax'],
        ':yinferiormax' => $data['yinferiormax'],
        ':yinferiormin' => $data['yinferiormin'],
        ':nsuperiormax' => $data['nsuperiormax'],
        ':nsuperiormin' => $data['nsuperiormin'],
        ':ninferiormax' => $data['ninferiormax'],
        ':ninferiormin' => $data['ninferiormin'],
        ':matter' => $data['matter'],
        ':architecture' => $data['architecture'],
        ':description' => $data['description'],
        ':script' => $data['script'],
        ':hitpoints' => $data['hitpoints'],
        ':psychicresistmin' => $data['psychicresistmin'],
        ':psychicresistmax' => $data['psychicresistmax'],
        ':physicalresistmin' => $data['physicalresistmin'],
        ':physicalresistmax' => $data['physicalresistmax'],
        ':magicresistmin' => $data['magicresistmin'],
        ':magicresistmax' => $data['magicresistmax'],
        ':obscureresistmax' => $data['obscureresistmax'],
        ':obscureresistmin' => $data['obscureresistmin'],
        ':technologyresistmin' => $data['technologyresistmin'],
        ':technologyresistmax' => $data['technologyresistmax'],
        ':socialresistmax' => $data['socialresistmax'],
        ':socialresistmin' => $data['socialresistmin'],
        ':arm' => $data['arm'],
        ':gender' => $data['gender'],
        ':taxesorigine' => $data['taxesorigine'] ?? 0,
        ':instanceupgrade' => $data['instanceupgrade'] ?? null,
    ];

    if ($stmt->execute($params)) {
        return $stmt->fetchColumn();
    }

    return null;
}


class ActionType {
    public const ACHAT = 'achat';
    public const AMELIORATION = 'amélioration';
    public const APPARITION = 'apparition';
    public const COMBAT = 'combat';
    public const COMPETENCE = 'compétence';
    public const DEPLACEMENT = 'déplacement';
    public const DIVERS = 'divers';
    public const DON_PX_CT = 'don de PX, Don de CT';
    public const ENTRAINEMENT = 'entraînement';
    public const CLAN = 'clan';
    public const MONSTRE = 'monstre';
    public const MORT = 'mort';
    public const PARCHEMIN = 'parchemin';
    public const POTION = 'potion';
    public const SORTILEGE = 'sortilège';
    public const TRESOR = 'trésor';
    public const TECHNIQUE = 'technique';
}

/**
 * Retourne le coût en points d'action selon le type d'action.
 *
 * @param string $actionType Type d'action (valeur de l'enum actiontype)
 * @return int Coût en points d'action
 * @throws InvalidArgumentException si le type d'action est inconnu
 */
function getActionCost(string $actionType): int {
    // Tableau associatif des coûts par type d'action (exemple arbitraire)
    $costs = [
        ActionType::ACHAT => 3,
        ActionType::AMELIORATION => 4,
        ActionType::APPARITION => 5,
        ActionType::COMBAT => 6,
        ActionType::COMPETENCE => 2,
        ActionType::DEPLACEMENT => 1,
        ActionType::DIVERS => 1,
        ActionType::DON_PX_CT => 2,
        ActionType::ENTRAINEMENT => 3,
        ActionType::CLAN => 4,
        ActionType::MONSTRE => 7,
        ActionType::MORT => 0,
        ActionType::PARCHEMIN => 2,
        ActionType::POTION => 1,
        ActionType::SORTILEGE => 5,
        ActionType::TRESOR => 3,
        ActionType::TECHNIQUE => 4,
    ];

    if (!array_key_exists($actionType, $costs)) {
        throw new InvalidArgumentException("Type d'action inconnu : $actionType");
    }

    return $costs[$actionType];
}

// Exemple d'utilisation
try {
    $action = ActionType::COMBAT;
    $cost = getActionCost($action);
    echo "Le coût de l'action '$action' est $cost points d'action.\n";
} catch (InvalidArgumentException $e) {
    echo "Erreur : " . $e->getMessage();
}


?>


