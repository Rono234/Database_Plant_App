<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('db_connection.php');

$conditions = [];

// Basic search functionality for plants using the name
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $conditions[] = "p.plant_name LIKE '%$search%'";
}

// Filter by plant type (perennial vs annual)
if(isset($_GET['plant_type']) && !empty($_GET['plant_type'])){
    $plant_type_security = 
        array_map(function($pt) use ($con){
            return mysqli_real_escape_string($con, $pt);
        }, $_GET['plant_type']);
    $pt_array = implode("','", $plant_type_security);
    $conditions[] = "p.plant_type IN ('$pt_array') ";
}

// Filter by sun level
if(isset($_GET['sun_level']) && !empty($_GET['sun_level'])){
    $sun_level_security = 
        array_map(function($s) use ($con){
            return mysqli_real_escape_string($con, $s);
        }, $_GET['sun_level']);
    $sun_level_array = implode("','", $sun_level_security);
    $conditions[] = "p.sun_level LIKE '$sun_level_array%'";
}

// Filter by pests
if(isset($_GET['pests']) && !empty($_GET['pests'])){
    $pests_security = 
        array_map(function($p) use ($con){
            return mysqli_real_escape_string($con, $p);
        }, $_GET['pests']);
    $pests_array = implode("','", $pests_security);
    $conditions[] = "p.plant_id IN(SELECT npp.plant_id FROM plants_pests AS npp JOIN pests AS npe ON npp.pest_id = npe.pest_id WHERE npe.pest_name IN ('$pests_array'))";
}

// Filter by difficulty level of planting
if(isset($_GET['difficulty']) && !empty($_GET['difficulty'])){
    $difficulty_security = 
        array_map(function($d) use ($con){
            return mysqli_real_escape_string($con, $d);
        }, $_GET['difficulty']);
    $difficulty_array = implode("','", $difficulty_security);
    $conditions[] = "p.difficulty IN ('$difficulty_array') ";
}

// Putting together the queries
$q = "SELECT p.plant_name, p.plant_type, p.plant_desc, p.sun_level, s.start_plant, s.end_plant, p.difficulty, p.plant_img,
        GROUP_CONCAT(DISTINCT pe.pest_name ORDER BY pe.pest_name SEPARATOR ', ') AS pests,
      CASE
        WHEN s.start_plant IN (12, 1, 2) THEN 'Winter'
        WHEN s.start_plant BETWEEN 3 and 5 THEN 'Spring'
        WHEN s.start_plant BETWEEN 6 and 8 THEN 'Summer'
        WHEN s.start_plant BETWEEN 9 and 11 THEN 'Fall'
        ELSE 'Unknown'
      END AS planting_season
      FROM plants AS p
      LEFT JOIN plants_pests AS pp ON p.plant_id = pp.plant_id
      LEFT JOIN pests AS pe ON pp.pest_id = pe.pest_id
      INNER JOIN seasons AS s ON p.season_id = s.season_id";

if (!empty($conditions)) {
    $q .= " WHERE " . implode(" AND ", $conditions);
}

$q .= " GROUP BY p.plant_id, p.plant_name, p.plant_type, p.plant_desc, p.sun_level, s.start_plant, s.end_plant, p.difficulty, p.plant_img, planting_season";

// Filter by planting season
if(isset($_GET['season']) && !empty($_GET['season'])){
    $season_security =
        array_map(function($s) use ($con){
            return mysqli_real_escape_string($con, $s);
        }, $_GET['season']);
    $season_array = implode("','", $season_security);
    $q .= " HAVING planting_season IN ('$season_array')";
}

$q .= " ORDER BY RAND()";

$result = mysqli_query($con, $q) or die("Query failed: " . mysqli_error($con));

function formatPlantingWindow($startMonth, $endMonth)
{
    $monthNames = array(
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec'
    );

    $startLabel = isset($monthNames[(int) $startMonth]) ? $monthNames[(int) $startMonth] : 'N/A';
    $endLabel = isset($monthNames[(int) $endMonth]) ? $monthNames[(int) $endMonth] : 'N/A';

    return $startLabel . ' - ' . $endLabel;
}

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function resolvePlantImage($fileName)
{
    $name = trim((string) $fileName);
    if ($name === '') {
        return 'https://placehold.co/600x400/F6E5E7/828C6A?text=Plant';
    }

    $baseName = pathinfo($name, PATHINFO_FILENAME);
    $hasExtension = pathinfo($name, PATHINFO_EXTENSION) !== '';

    $candidates = array($name);
    if (!$hasExtension && $baseName !== '') {
        $candidates[] = $baseName . '.jpg';
        $candidates[] = $baseName . '.jpeg';
        $candidates[] = $baseName . '.png';
    }

    $specialNames = array(
        'lettuce' => 'letttuce.jpg',
        'oregano' => 'oregano.jpeg'
    );
    if (isset($specialNames[strtolower($baseName)])) {
        $candidates[] = $specialNames[strtolower($baseName)];
    }

    foreach (array_unique($candidates) as $candidate) {
        $safeCandidate = basename($candidate);
        if (file_exists(__DIR__ . '/images/' . $safeCandidate)) {
            return 'images/' . $safeCandidate;
        }
    }

    return 'https://placehold.co/600x400/F6E5E7/828C6A?text=' . rawurlencode($baseName ?: 'Plant');
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blossom</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&family=Pacifico&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Cormorant+Garamond" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <form id="filter-form" action="" method="GET"> </form>
        <!-- SIDEBAR -->
        <div class="sidebar">
            <h3>Filters:</h3>

            <div class="filter-section">
                <details class="filter-group" data-filter-group="type">
                    <summary>
                        <strong class="filter-title">Type:</strong>
                        <button class="filter-toggle" type="button" aria-label="Toggle Type filter"></button>
                    </summary>
                    <label><input type="checkbox" name="plant_type[]" form="filter-form" value="Perennial"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['plant_type']) && in_array('Perennial', $_GET['plant_type'])) echo 'checked'; ?>
                        > Perennial
                    </label>

                    <label><input type="checkbox" name="plant_type[]" form="filter-form" value="Annual"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['plant_type']) && in_array('Annual', $_GET['plant_type'])) echo 'checked'; ?>
                        > Annual
                    </label>
                </details>

                <details class="filter-group" data-filter-group="light">
                    <summary>
                        <strong class="filter-title">Light:</strong>
                        <button class="filter-toggle" type="button" aria-label="Toggle Light filter"></button>
                    </summary>
                    <label><input type="checkbox" name="sun_level[]" form="filter-form" value="Full Sun"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['sun_level']) && in_array('Full Sun', $_GET['sun_level'])) echo 'checked'; ?>
                        > Full Sun
                    </label>

                    <label><input type="checkbox" name="sun_level[]" form="filter-form" value="Partial Shade"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['sun_level']) && in_array('Partial Shade', $_GET['sun_level'])) echo 'checked'; ?>
                        > Partial Shade
                    </label>
                </details>

                <details class="filter-group" data-filter-group="season">
                    <summary>
                        <strong class="filter-title">Season:</strong>
                        <button class="filter-toggle" type="button" aria-label="Toggle Season filter"></button>
                    </summary>
                    <label><input type="checkbox" name="season[]" form="filter-form" value="Spring"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['season']) && in_array('Spring', $_GET['season'])) echo 'checked'; ?>
                        > Spring
                    </label>

                    <label><input type="checkbox" name="season[]" form="filter-form" value="Summer"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['season']) && in_array('Summer', $_GET['season'])) echo 'checked'; ?>
                        > Summer
                    </label>

                    <label><input type="checkbox" name="season[]" form="filter-form" value="Fall"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['season']) && in_array('Fall', $_GET['season'])) echo 'checked'; ?>
                        > Fall
                    </label>

                    <label><input type="checkbox" name="season[]" form="filter-form" value="Winter"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['season']) && in_array('Winter', $_GET['season'])) echo 'checked'; ?>
                        > Winter
                    </label>

                </details>

                <details class="filter-group" data-filter-group="pest">
                    <summary>
                        <strong class="filter-title">Pest:</strong>
                        <button class="filter-toggle" type="button" aria-label="Toggle Pest filter"></button>
                    </summary>
                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Ladybug"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Ladybug', $_GET['pests'])) echo 'checked'; ?>
                        > Ladybug
                    </label>

                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Bees"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Bees', $_GET['pests'])) echo 'checked'; ?>
                        > Bees
                    </label>

                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Caterpillar"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Caterpillar', $_GET['pests'])) echo 'checked'; ?>
                        > Caterpillar
                    </label>

                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Aphids"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Aphids', $_GET['pests'])) echo 'checked'; ?>
                        > Aphids
                    </label>

                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Japanese Beetles"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Japanese Beetles', $_GET['pests'])) echo 'checked'; ?>
                        > Japanese Beetles
                    </label>

                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Spider Mites"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Spider Mites', $_GET['pests'])) echo 'checked'; ?>
                        > Spider Mites
                    </label>

                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Lacewings"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Lacewings', $_GET['pests'])) echo 'checked'; ?>
                        > Lacewings
                    </label>

                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Slugs"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Slugs', $_GET['pests'])) echo 'checked'; ?>
                        > Slugs
                    </label>

                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Butterflies"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Butterflies', $_GET['pests'])) echo 'checked'; ?>
                        > Butterflies
                    </label>

                    <label><input type="checkbox" name="pests[]" form="filter-form" value="Hoverflies"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['pests']) && in_array('Hoverflies', $_GET['pests'])) echo 'checked'; ?>
                        > Hoverflies
                    </label>
                </details>

                <details class="filter-group" data-filter-group="difficulty">
                    <summary>
                        <strong class="filter-title">Difficulty:</strong>
                        <button class="filter-toggle" type="button" aria-label="Toggle Difficulty filter"></button>
                    </summary>
                    <label><input type="checkbox" name="difficulty[]" form="filter-form" value="Easy"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['difficulty']) && in_array('Easy', $_GET['difficulty'])) echo 'checked'; ?>
                        > Easy
                    </label>

                    <label><input type="checkbox" name="difficulty[]" form="filter-form" value="Medium"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['difficulty']) && in_array('Medium', $_GET['difficulty'])) echo 'checked'; ?>
                        > Medium
                    </label>

                    <label><input type="checkbox" name="difficulty[]" form="filter-form" value="Hard"
                        onchange = "this.form.submit()"
                        <?php if(isset($_GET['difficulty']) && in_array('Hard', $_GET['difficulty'])) echo 'checked'; ?>
                        > Hard
                    </label>
                </details>
            </div>
        </div>

        <!-- MAIN -->
        <div class="main">

            <!-- HEADER -->
            <div class="header">
                <div class="header-top">
                    <div class="logo">🌸 Blossom</div>
                    <div class="tabs">
                        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Plants</a>
                        <a href="community.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'community.php' ? 'active' : ''; ?>">Community</a>
                    </div>
                </div>

                <!-- SEARCH -->
                <div>
                    <div class="search-container">
                        <input class="search" type="text" name="search" form="filter-form" placeholder="Type here to search for plants..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <input class="search-button" name ="search-button" form="filter-form" type="image" src="images/search.png" alt="Search" width="30" height="30" style="vertical-align: middle; margin-left: 10px; background: transparent; border: none;">
                    </div>
                </div>
            </div>

        <!-- GRID -->
        <div class="grid">
            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="card" style="margin-bottom: 10px;">
                        <div class="tag"><?php echo escape(strtolower(trim($row['difficulty']))); ?></div>
                        <img src="<?php echo escape(resolvePlantImage($row['plant_img'])); ?>" alt="<?php echo escape($row['plant_name']); ?>">
                        <div class="card-content">
                            <h3><?php echo escape($row['plant_name']); ?></h3>
                            <small><?php echo escape($row['plant_type']); ?></small>
                            <p><?php echo escape($row['plant_desc']); ?></p>
                            <div class="info">☀️ <?php echo escape($row['sun_level']); ?></div>
                            <div class="info">🗓️ Planting Window: <?php echo escape(formatPlantingWindow($row['start_plant'], $row['end_plant'])); ?></div>
                            <div class="info">🌻 Season: <?php echo escape($row['planting_season'] ?: 'No listed season'); ?></div>
                            <div class="info">🐞 Insects: <?php echo escape($row['pests'] ?: 'No listed pests'); ?></div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">
                    No plants were found in the database.
                </div>
            <?php } ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var storageKey = 'blossom-open-filters';
            var filterForm = document.getElementById('filter-form');
            var filterGroups = Array.from(document.querySelectorAll('.filter-group[data-filter-group]'));

            function saveOpenGroups() {
                var openGroups = filterGroups
                    .filter(function (group) {
                        return group.open;
                    })
                    .map(function (group) {
                        return group.dataset.filterGroup;
                    });

                sessionStorage.setItem(storageKey, JSON.stringify(openGroups));
            }

            function restoreOpenGroups() {
                var savedState = sessionStorage.getItem(storageKey);

                if (!savedState) {
                    return;
                }

                try {
                    var openGroups = JSON.parse(savedState);

                    filterGroups.forEach(function (group) {
                        group.open = openGroups.indexOf(group.dataset.filterGroup) !== -1;
                    });
                } catch (error) {
                    sessionStorage.removeItem(storageKey);
                }
            }

            restoreOpenGroups();

            filterGroups.forEach(function (group) {
                var summary = group.querySelector('summary');
                var toggle = group.querySelector('.filter-toggle');

                summary.addEventListener('click', function (event) {
                    if (!event.target.closest('.filter-toggle')) {
                        event.preventDefault();
                    }
                });

                summary.addEventListener('keydown', function (event) {
                    if ((event.key === 'Enter' || event.key === ' ') && event.target === summary) {
                        event.preventDefault();
                    }
                });

                toggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    group.open = !group.open;
                    saveOpenGroups();
                });

                group.addEventListener('toggle', saveOpenGroups);
            });

            if (filterForm) {
                filterForm.addEventListener('submit', saveOpenGroups);
            }
        });
    </script>
</body>
</html>