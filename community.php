<?php
include('db_connection.php');

$q = "SELECT pt.title, pt.body, pt.post_date, pt.post_img, u.user_name 
    FROM posts AS pt 
    LEFT JOIN users AS u ON pt.user_id = u.user_id 
    ORDER BY pt.post_date DESC;";

$result = mysqli_query($con, $q) or die("Query failed: " . mysqli_error($con));


function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function resolvePostImage($fileName)
{
    $name = trim((string) $fileName);
    if ($name === '') {
        return 'https://placehold.co/600x400/F6E5E7/828C6A?text=Post';
    }

    $baseName = pathinfo($name, PATHINFO_FILENAME);
    $hasExtension = pathinfo($name, PATHINFO_EXTENSION) !== '';

    $candidates = array($name);
    if (!$hasExtension && $baseName !== '') {
        $candidates[] = $baseName . '.jpg';
        $candidates[] = $baseName . '.jpeg';
        $candidates[] = $baseName . '.png';
    }

    $folders = array('postImages/', 'images/');
    foreach ($folders as $folder) {
        $absoluteFolder = __DIR__ . '/' . $folder;
        foreach (array_unique($candidates) as $candidate) {
            $safeCandidate = basename($candidate);
            if (file_exists($absoluteFolder . $safeCandidate)) {
                return $folder . $safeCandidate;
            }
        }
    }

    return 'https://placehold.co/600x400/F6E5E7/828C6A?text=' . rawurlencode($baseName ?: 'Post');
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
                    <a href="index.php">Plants</a>
                    <a href="community.php" class="active">Community</a>
                </div>
            </div>

            <input class="search" placeholder="search your plant friend...">
        </div>

        <!-- GRID -->
        <div class="grid">
            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="card">
            
                        <img src="<?php echo escape(resolvePostImage($row['post_img'])); ?>"
                            alt="<?php echo escape($row['title']); ?>">
                        <div class="card-content">
                            <h3><?php echo escape($row['title']); ?></h3>
                            <small><?php echo escape($row['user_name']); ?></small>
                            <p><?php echo escape($row['body']); ?></p>
                            <div class="info">🗓️ Posted on: <?php echo escape($row['post_date']); ?></div>
                          
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">
                    No posts were found in the database.
                </div>
            <?php } ?>

        </div>

    </div>

</body>

</html>