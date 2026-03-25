<?php
include('db_connection.php');

$conditions = [];

// Basic search functionality for posts and users using the name
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $conditions[] = "pt.title LIKE '%$search%' OR u.user_name LIKE '%$search%'";
}

// Putting together the queries
$q = "SELECT pt.title, pt.body, pt.post_date, pt.post_img, u.user_name 
    FROM posts AS pt 
    LEFT JOIN users AS u ON pt.user_id = u.user_id";

if (!empty($conditions)) {
    $q .= " WHERE " . implode(" AND ", $conditions);
}

$q .= " GROUP BY pt.post_id, pt.title, pt.body, pt.post_date, pt.post_img, u.user_name
        ORDER BY pt.post_date DESC";

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
            min-height: 100vh;
            align-items: stretch;
        }

        /* SIDEBAR FILTER */
        .sidebar {
            width: 200px;
            background: #255626;
            border-right: 2px solid #f3d1dc;
            padding: 20px;
            height: 100vh;
            position: fixed;
            color: #fff;
            top: 0;
            left: 0;
        }

        .filter-section {
            position: sticky;
            top: 20px;
            padding: 15px;
            margin-top: 10px;
        }

        .filter-title{
            display: block; 
            margin-bottom: 10px;
            font-size: 16px;
            /* text-decoration: underline; 
            text-underline-offset: 3px;" */
        }

        .sidebar h3 {
            margin-bottom: 15px;
            color: #FFF;
            font-size: 22px;
            text-align: center;
            text-decoration: underline;
            text-underline-offset: 3px;
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
            margin-left: 210px;
            margin-right: 10px;
        }

        /* HEADER */
        .header {
            background: #116838;
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
            border-radius: 20px;s
            margin-left: 10px;
            cursor: pointer;
            color: #000;
        }

        .tabs a.active {
            background: white;
            font-weight: 600;
        }

        .search-container {
            position: relative;
            margin-top: 10px;
        }

        .search {
            width: 95%;
            padding: 12px 50px 12px 18px; /* Right padding for button */
            border-radius: 30px;
            border: none;
            outline: none;
        }

        .search-button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
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

    <form id="filter-form"></form>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h3>Filters:</h3>

        <div class="filter-section">
            <div class="filter-group">
                <strong class="filter-title">Light:</strong>
                <label><input type="checkbox"> Full Sun</label>
                <label><input type="checkbox"> Partial Sun</label>
                <label><input type="checkbox"> Shade</label>
            </div>

            <div class="filter-group">
                <strong class="filter-title">Pest:</strong>
                <label><input type="checkbox"> Ladybugs</label>
                <label><input type="checkbox"> Beetles</label>
            </div>

            <div class="filter-group">
                <strong class="filter-title">Difficulty:</strong>
                <label><input type="checkbox"> Easy</label>
                <label><input type="checkbox"> Medium</label>
                <label><input type="checkbox"> Hard</label>
            </div>

            <div class="filter-group">
                <strong class="filter-title">Type:</strong>
                <label><input type="checkbox"> Perennial</label>
                <label><input type="checkbox"> Annual</label>
            </div>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">

        <!-- HEADER -->
        <div class="header">
            <div class="header-top">
                <div class="logo">🌸 blossom</div>
                <div class="tabs">
                    <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Plants</a>
                    <a href="community.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'community.php' ? 'active' : ''; ?>">Community</a>
                </div>
            </div>

            <!-- SEARCH -->
            <div>
                <form action="" method="GET">
                    <div class="search-container">
                        <input class="search" type="text" name="search" form="filter-form" placeholder="Type here to search for a post or user..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <input class="search-button" name ="search-button" form="filter-form" type="image" src="images/search.png" alt="Search" width="30" height="30" style="vertical-align: middle; margin-left: 10px; background: transparent; border: none;">
                    </div>
                </form>
            </div>
        </div>

        <!-- GRID -->
        <div class="grid">
            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="card" style="margin-bottom: 10px;">
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