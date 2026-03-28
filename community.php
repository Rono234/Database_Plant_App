<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('db_connection.php');

$conditions = [];

// Basic search functionality for posts and users using the name
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $conditions[] = "(pt.title LIKE '%$search%' OR u.user_name LIKE '%$search%')";
}

// User filter functionality
if(isset($_GET['user_name']) && !empty($_GET['user_name'])){
    $user_name_security = 
        array_map(function($un) use ($con){
            return mysqli_real_escape_string($con, $un);
        }, $_GET['user_name']);
    $un_array = implode("','", $user_name_security);
    $conditions[] = "u.user_name IN ('$un_array') ";
}

// Putting together the queries
$q = "SELECT pt.post_id, pt.title, pt.body, pt.post_date, pt.post_img, u.user_name 
    FROM posts AS pt 
    INNER JOIN users AS u ON pt.user_id = u.user_id";

if (!empty($conditions)) {
    $q .= " WHERE " . implode(" AND ", $conditions);
}

$q .= " GROUP BY pt.post_id, pt.title, pt.body, pt.post_date, pt.post_img, u.user_name
        ORDER BY RAND()";

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
                <details class="filter-group" data-filter-group="user">
                <summary>
                    <strong class="filter-title">User:</strong>
                    <button class="filter-toggle" type="button" aria-label="Toggle User filter"></button>
                </summary>
                    <?php
                        $user_query = "SELECT DISTINCT user_name FROM users ORDER BY user_name";
                        $user_result= mysqli_query($con, $user_query) or die("Query failed: " . mysqli_error($con));
                        
                        while ($user_row = mysqli_fetch_assoc($user_result)){
                            $user_name = $user_row['user_name'];
                            $checked = isset($_GET['user_name']) && in_array($user_name, $_GET['user_name']) ? 'checked' : '';
                            echo '<label><input type="checkbox" name="user_name[]" form="filter-form" value="' . escape($user_name) . '" onchange="this.form.submit()" ' . $checked . '> ' . escape($user_name) . '</label>';
                        }
                    ?>
                </details>
            </div>
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
                    <button class="new-post">+</button>
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
                    <a class="card-link" href="detail.php?post_id=<?php echo (int) $row['post_id']; ?>">
                        <div class="community-container">
                            <div class="card" style="margin-bottom: 10px;">
                                <?php if (!empty($row['post_img'])): ?>
                                    <img src="<?php echo escape(resolvePostImage($row['post_img'])); ?>"
                                        alt="<?php echo escape($row['title']); ?>">
                                <?php endif; ?>

                                <div class="card-content">
                                    <h3><?php echo escape($row['title']); ?></h3>
                                    <small><?php echo escape($row['user_name']); ?></small>
                                    <p><?php echo escape($row['body']); ?></p>
                                    <div class="info">🗓️ Posted on: <?php echo escape($row['post_date']); ?></div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">
                    No posts were found in the database.
                </div>
            <?php } ?>
        </div>

    </div>
    <div id="modalOverlay" class="modal">
        <div class="modal-content">
            <h2>Create New Post</h2>
            <form id="post-form" method="POST" action="post.php" enctype="multipart/form-data">
                <label for="title">Post Title:</label>
                <input type="text" name="title" placeholder="What do would you like to title the post?" required>

                <label for="user">Username:</label>
                <input type="text" name="user" placeholder="Write your username here: " required>

                <label for="body">Body:</label>
                <textarea type="text" name="body" placeholder="Write your post here:" required></textarea>

                <label for="post_img" class="post_img">
                    <i class="fa fa-cloud-upload"></i>Post Image:
                </label>
                <input type="file" name="post_img" id="post_img" accept="image/*">

                <div id="modal-buttons">
                    <button type="button" id="cancel-post">Cancel</button>
                    <button type="submit" id="submit-post">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const newPostBtn = document.querySelector('.new-post');
        const modalOverlay = document.getElementById('modalOverlay');
        const cancelPostBtn = document.getElementById('cancel-post');

        newPostBtn.addEventListener('click', () => {
            modalOverlay.classList.add('open');
        });

        cancelPostBtn.addEventListener('click', () => {
            modalOverlay.classList.remove('open');
        });

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