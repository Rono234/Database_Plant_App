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
    <link rel="stylesheet" href="style.css">
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