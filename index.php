<?php
include('db_connection.php');

$q = "SELECT p.plant_name, p.plant_type, p.plant_desc, p.sun_level, p.start_plant, p.end_plant, p.difficulty, p.plant_img,
             GROUP_CONCAT(DISTINCT pe.pest_name ORDER BY pe.pest_name SEPARATOR ', ') AS pests
      FROM plants AS p
      LEFT JOIN plants_pests AS pp ON p.plant_id = pp.plant_id
      LEFT JOIN pests AS pe ON pp.pest_id = pe.pest_id
      GROUP BY p.plant_id, p.plant_name, p.plant_type, p.plant_desc, p.sun_level, p.start_plant, p.end_plant, p.difficulty, p.plant_img
      ORDER BY p.plant_name";

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

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #F6E5E7;
            background-image: radial-gradient(#e8f5e9 1px, transparent 1px),
                radial-gradient(#fce4ec 1px, transparent 1px);
            background-size: 40px 40px;
            background-position: 0 0, 20px 20px;
            display: flex;
        }

        /* SIDEBAR FILTER */
        .sidebar {
            width: 220px;
            background: #A0AB89;
            border-right: 2px solid #f3d1dc;
            padding: 20px;
            height: 100vh;
            position: sticky;
            color: #fff;
            top: 0;
        }

        .sidebar h3 {
            margin-bottom: 15px;
            color: #FFF;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        /* MAIN CONTENT */
        .main {
            flex: 1;
            padding: 20px;
        }

        /* HEADER */
        .header {
            background: #e69b97;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .logo {
            font-family: 'Pacifico', cursive;
            font-size: 24px;
            color: #fff;
        }

        .tabs a {
            text-decoration: none;
            background: rgba(255, 255, 255, 0.6);
            padding: 8px 14px;
            border-radius: 20px;
            margin-left: 10px;
            cursor: pointer;
            color: #000;
        }

        .tabs a.active {
            background: white;
            font-weight: 600;
        }

        .search {
            width: 100%;
            padding: 12px 18px;
            border-radius: 30px;
            border: none;
            outline: none;
        }

        /* GRID */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .empty-state {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            color: #5f6750;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
        }

        /* CARD */
        .card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
            position: relative;
        }

        .card:hover {
            transform: translateY(-6px);
        }

        .card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .card-content {
            padding: 15px;
        }

        .card h3 {
            margin-bottom: 5px;
        }

        .card p {
            font-size: 14px;
            color: #5a5a5a;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .card small {
            color: #777;
            display: block;
            margin-bottom: 10px;
        }

        .tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #fce4ec;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }

        .info {
            font-size: 13px;
            margin-bottom: 5px;
        }

        @media (max-width: 900px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                border-right: none;
                border-bottom: 2px solid #f3d1dc;
            }
        }

        /* color palette: 
    - #F6E5E7 (off-white)
    - #828C6A (green)
    - #A0AB89 (light green)
    - #EFC0BC (light pink)
    - #E69B97 (pink) */
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h3>🌿 Filters</h3>

        <div class="filter-group">
            <strong>Light</strong>
            <label><input type="checkbox"> Full Sun</label>
            <label><input type="checkbox"> Partial Sun</label>
            <label><input type="checkbox"> Shade</label>
        </div>

        <div class="filter-group">
            <strong>Pest</strong>
            <label><input type="checkbox"> ladybugs</label>
            <label><input type="checkbox"> beetles</label>
        </div>

        <div class="filter-group">
            <strong>Difficulty</strong>
            <label><input type="checkbox"> Easy</label>
            <label><input type="checkbox"> Medium</label>
            <label><input type="checkbox"> Hard</label>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">

        <!-- HEADER -->
        <div class="header">
            <div class="header-top">
                <div class="logo">🌸 blossom</div>
                <div class="tabs">
                    <a href="index.php" class="active">Plants</a>
                    <a href="community.php">Community</a>
                </div>
            </div>

            <input class="search" placeholder="search your plant friend...">
        </div>

        <!-- GRID -->
        <div class="grid">
            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="card">
                        <div class="tag"><?php echo escape(strtolower(trim($row['difficulty']))); ?></div>
                        <img src="<?php echo escape(resolvePlantImage($row['plant_img'])); ?>" alt="<?php echo escape($row['plant_name']); ?>">
                        <div class="card-content">
                            <h3><?php echo escape($row['plant_name']); ?></h3>
                            <small><?php echo escape($row['plant_type']); ?></small>
                            <p><?php echo escape($row['plant_desc']); ?></p>
                            <div class="info">☀️ <?php echo escape($row['sun_level']); ?></div>
                            <div class="info">🗓️ Planting window: <?php echo escape(formatPlantingWindow($row['start_plant'], $row['end_plant'])); ?></div>
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

</body>

</html>