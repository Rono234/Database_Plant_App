<?php
include('db_connection.php');

// $post_id = $_GET['post_id'];

function escape($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function resolvePlantImage($fileName)
{
	$name = TRIM((string) $fileName);
	if ($name === '') {
		return 'https://placehold.co/1200x600/F6E5E7/828C6A?text=Plant';
	}

	$baseName = pathinfo($name, PATHINFO_FILENAME);
	$hasExtension = pathinfo($name, PATHINFO_EXTENSION) !== '';

	$candidates = array($name);
	if (!$hasExtension && $baseName !== '') {
		$candidates[] = $baseName . '.jpg';
		$candidates[] = $baseName . '.jpeg';
		$candidates[] = $baseName . '.png';
	}

	

	foreach (array_unique($candidates) as $candidate) {
		$safeCandidate = basename($candidate);
		if (file_exists(__DIR__ . '/images/' . $safeCandidate)) {
			return 'images/' . $safeCandidate;
		}
	}

	return 'https://placehold.co/1200x600/F6E5E7/828C6A?text=' . rawurlencode($baseName ?: 'Plant');
}

function resolvePostImage($fileName)
{
	$name = TRIM((string) $fileName);
	if ($name === '') {
		return 'https://placehold.co/1200x600/F6E5E7/828C6A?text=Post';
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

	return 'https://placehold.co/1200x600/F6E5E7/828C6A?text=' . rawurlencode($baseName ?: 'Post');
}

function getRatingSummary($con, $columnName, $itemId)
{
	$itemId = (int) $itemId;
	$sql = "SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(rating) AS rating_count
		FROM comments
		WHERE $columnName = $itemId AND rating IS NOT NULL";

	$result = mysqli_query($con, $sql);
	if (!$result) {
		return array('avg_rating' => null, 'rating_count' => 0);
	}

	$row = mysqli_fetch_assoc($result);
	return array(
		'avg_rating' => $row && $row['avg_rating'] !== null ? (float) $row['avg_rating'] : null,
		'rating_count' => $row ? (int) $row['rating_count'] : 0
	);
}

$plantId = isset($_GET['plant_id']) ? (int) $_GET['plant_id'] : 0;
$postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
$currentPageTypeParam = isset($_GET['type']) ? (string) $_GET['type'] : '';

$plant = null;
$post = null;
$comments = array();
$ratingSummary = array('avg_rating' => null, 'rating_count' => 0);
$pageType = '';
$errorMessage = '';
$formMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
	$postedType = isset($_POST['target_type']) ? trim((string) $_POST['target_type']) : '';
	$postedId = isset($_POST['target_id']) ? (int) $_POST['target_id'] : 0;
	$userName = isset($_POST['comment_user']) ? trim((string) $_POST['comment_user']) : '';
	$commentText = isset($_POST['comment_text']) ? trim((string) $_POST['comment_text']) : '';
	$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
	$returnType = isset($_POST['return_type']) ? trim((string) $_POST['return_type']) : '';

	if (($postedType === 'plant' || $postedType === 'post') && $postedId > 0 && $userName !== '' && $commentText !== '' && $rating >= 1 && $rating <= 5) {
		$escapedUser = mysqli_real_escape_string($con, $userName);
		mysqli_query($con, "INSERT IGNORE INTO users (user_name) VALUES ('$escapedUser')");

		$userResult = mysqli_query($con, "SELECT user_id FROM users WHERE user_name = '$escapedUser' LIMIT 1");
		$userData = $userResult ? mysqli_fetch_assoc($userResult) : null;
		$userId = $userData ? (int) $userData['user_id'] : 0;

		if ($userId > 0) {
			if ($postedType === 'plant') {
				$stmt = mysqli_prepare(
					$con,
					"INSERT INTO comments (comment_text, rating, comment_date, plant_id, user_id)
					 VALUES (?, ?, DATE(NOW()), ?, ?)"
				);
			} else {
				$stmt = mysqli_prepare(
					$con,
					"INSERT INTO comments (comment_text, rating, comment_date, post_id, user_id)
					 VALUES (?, ?, DATE(NOW()), ?, ?)"
				);
			}

			if ($stmt) {
				mysqli_stmt_bind_param($stmt, 'siii', $commentText, $rating, $postedId, $userId);
				if (mysqli_stmt_execute($stmt)) {
					$redirectUrl = 'detail.php?' . ($postedType === 'post' ? 'post_id=' : 'plant_id=') . $postedId;
					if ($returnType === 'community') {
						$redirectUrl .= '&type=community';
					}
					header('Location: ' . $redirectUrl);
					exit;
				}
				$formMessage = 'Could not save your comment right now. Please try again.';
				mysqli_stmt_close($stmt);
			} else {
				$formMessage = 'Comment service is unavailable right now.';
			}
		} else {
			$formMessage = 'Please use a valid username.';
		}
	} else {
		$formMessage = 'Please enter a username, comment, and a rating from 1 to 5.';
	}
}

if ($plantId > 0) {
	$pageType = 'plant';

	$plantQuery = "SELECT
		p.plant_id,
		p.plant_name,
		p.plant_type,
		p.plant_desc,
		p.sun_level,
		p.season_id,
		p.difficulty,
		p.plant_img,
		s.start_plant,
		s.end_plant,
		GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ', ') AS categories,
		GROUP_CONCAT(DISTINCT pe.pest_name ORDER BY pe.pest_name SEPARATOR ', ') AS pests,
		
		CASE
		WHEN s.start_plant IN (12, 1, 2) THEN 'Winter'
		WHEN s.start_plant BETWEEN 3 and 5 THEN 'Spring'
		WHEN s.start_plant BETWEEN 6 and 8 THEN 'Summer'
		WHEN s.start_plant BETWEEN 9 and 11 THEN 'Fall'
		ELSE 'Unknown'
		END AS planting_season
		
		FROM plants AS p
		INNER JOIN seasons AS s ON p.season_id = s.season_id
		LEFT JOIN plants_categories AS pc ON p.plant_id = pc.plant_id
		LEFT JOIN categories AS c ON pc.category_id = c.category_id
		LEFT JOIN plants_pests AS pp ON p.plant_id = pp.plant_id
		LEFT JOIN pests AS pe ON pp.pest_id = pe.pest_id
		WHERE p.plant_id = $plantId
		
		GROUP BY
		p.plant_id,
		p.plant_name,
		p.plant_type,
		p.plant_desc,
		p.sun_level,
		p.season_id,
		p.difficulty,
		p.plant_img,
		s.start_plant,
		s.end_plant";

	$plantResult = mysqli_query($con, $plantQuery);
	if ($plantResult && mysqli_num_rows($plantResult) > 0) {
		$plant = mysqli_fetch_assoc($plantResult);
	} else {
		$errorMessage = 'Plant not found.';
	}

	$ratingSummary = getRatingSummary($con, 'plant_id', $plantId);

	$commentsQuery = "SELECT
		c.comment_text,
		c.rating,
		c.comment_date,
		u.user_name
	FROM comments AS c
	INNER JOIN users AS u ON c.user_id = u.user_id
	WHERE c.plant_id = $plantId
	ORDER BY c.comment_date DESC, c.comment_id DESC";

	$commentsResult = mysqli_query($con, $commentsQuery);
	if ($commentsResult) {
		while ($row = mysqli_fetch_assoc($commentsResult)) {
			$comments[] = $row;
		}
	}
} elseif ($postId > 0) {
	$pageType = 'post';

	$postQuery = "SELECT pt.post_id, pt.title, pt.body, pt.post_date, pt.post_img, u.user_name
	FROM posts AS pt
	INNER JOIN users AS u ON pt.user_id = u.user_id
	WHERE pt.post_id = $postId";

	$postResult = mysqli_query($con, $postQuery);
	if ($postResult && mysqli_num_rows($postResult) > 0) {
		$post = mysqli_fetch_assoc($postResult);
	} else {
		$errorMessage = 'Post not found.';
	}

	$ratingSummary = getRatingSummary($con, 'post_id', $postId);

	$commentsQuery = "SELECT
		c.comment_text,
		c.rating,
		c.comment_date,
		u.user_name
	FROM comments AS c
	INNER JOIN users AS u ON c.user_id = u.user_id
	WHERE c.post_id = $postId
	ORDER BY c.comment_date DESC, c.comment_id DESC";

	$commentsResult = mysqli_query($con, $commentsQuery);
	if ($commentsResult) {
		while ($row = mysqli_fetch_assoc($commentsResult)) {
			$comments[] = $row;
		}
	}
} else {
	$errorMessage = 'Select a plant or post card to view details.';
}
?>

<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Blossom Details</title>
	<link rel="icon" type="image/png" href="images/favicon.png">
	<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Cormorant+Garamond" rel="stylesheet">
	<link rel="stylesheet" href="style.css">
	<script src="app.js" defer></script>
</head>

<body class="detail-page">
	<div class="detail-layout">
		<div class="header detail-header">
			<div class="header-top">
				<div class="logo" style="display: flex; align-items: center;">
                    <img src="images/favicon.png" alt="Blossom">Blossom
                </div>
				<div class="tabs">
					<a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Plants</a>
					<a href="community.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'community.php' ? 'active' : ''; ?>">Community</a>
				</div>
			</div>
			<a class="back-link" href="<?php echo $pageType === 'post' ? 'community.php' : 'index.php'; ?>">← Back to <?php echo $pageType === 'post' ? 'Community' : 'Plants'; ?></a>
		</div>

		<?php if ($errorMessage !== '') { ?>
			<div class="empty-state"><?php echo escape($errorMessage); ?></div>
		<?php } else { ?>
			<?php if ($pageType === 'plant' && $plant) { ?>
				<section class="plant-detail">

					<!-- LEFT: IMAGE -->
					<div class="plant-image">
						<img src="<?php echo escape(resolvePlantImage($plant['plant_img'])); ?>" alt="<?php echo escape($plant['plant_name']); ?>">
					</div>

					<!-- RIGHT: INFO -->
					<div class="plant-info">

						<div class="plant-header">
							<div>
								<h1><?php echo escape($plant['plant_name']); ?></h1>
								<p class="plant-type"><?php echo escape($plant['plant_type']); ?></p>
							</div>

							<!-- TOP RIGHT RATING -->
							<div class="avg-rating">
								<?php echo $ratingSummary['avg_rating'] !== null 
									? escape(number_format($ratingSummary['avg_rating'],1)) 
									: '0'; ?>/5
							</div>
						</div>

						<!-- CLICKABLE STARS -->
						<!-- <div class="rating-input">
							<span data-value="1">☆</span>
							<span data-value="2">☆</span>
							<span data-value="3">☆</span>
							<span data-value="4">☆</span>
							<span data-value="5">☆</span>
						</div> -->

						<p class="plant-desc"><?php echo escape($plant['plant_desc']); ?></p>

						<!-- QUICK FACTS -->
						<div class="quick-facts">
							<h3>Quick Facts</h3>
							<ul>
								<li>🌱 Season: <?php echo escape($plant['planting_season']); ?></li>
								<li>☀️ Sun: <?php echo escape($plant['sun_level']); ?></li>
								<li>📦 Category: <?php echo escape($plant['categories']); ?></li>
								<li>🐛 Pests: <?php echo escape($plant['pests']); ?></li>
								<li>⭐ Difficulty: <?php echo escape($plant['difficulty']); ?></li>
							</ul>
						</div>

					</div>
				</section>
			<?php } ?>

			<?php if ($pageType === 'post' && $post) { ?>
				<?php 
					$postImage = resolvePostImage($post['post_img']);
					$hasImage = strpos($postImage, 'placehold') === false;
				?>
				
				<?php if ($hasImage) { ?>
					<!-- POST WITH IMAGE -->
					<section class="plant-detail">
						<div class="plant-image">
							<img src="<?php echo escape($postImage); ?>" alt="<?php echo escape($post['title']); ?>">
						</div>

						<div class="plant-info">
							<div class="post-content-box">
								<h1><?php echo escape($post['title']); ?></h1>
								<p class="post-body"><?php echo escape($post['body']); ?></p>

								<div class="post-footer-divider"></div>

								<div class="post-user-info post-user-info-spread">
									<div class="post-user-meta">
										<img src="https://ui-avatars.com/api/?name=<?php echo urlencode($post['user_name']); ?>&background=E69B97&color=fff" class="pfp" alt="<?php echo escape($post['user_name']); ?>">
										<div>
											<strong><?php echo escape($post['user_name']); ?></strong>
											<p class="post-date"><?php echo escape($post['post_date']); ?></p>
										</div>
									</div>

									<div class="avg-rating">
										<?php echo $ratingSummary['avg_rating'] !== null
											? escape(number_format($ratingSummary['avg_rating'], 1))
											: '0'; ?>/5
									</div>
								</div>
							</div>
						</div>
					</section>
				<?php } else { ?>
					<!-- POST WITHOUT IMAGE -->
					<section class="post-text-only">
						<div class="post-content-box">
							<h1><?php echo escape($post['title']); ?></h1>
							<p class="post-body"><?php echo escape($post['body']); ?></p>

							<div class="post-footer-divider"></div>

							<div class="post-user-info post-user-info-spread">
								<div class="post-user-meta">
									<img src="https://ui-avatars.com/api/?name=<?php echo urlencode($post['user_name']); ?>&background=E69B97&color=fff" class="pfp" alt="<?php echo escape($post['user_name']); ?>">
									<div>
										<strong><?php echo escape($post['user_name']); ?></strong>
										<p class="post-date"><?php echo escape($post['post_date']); ?></p>
									</div>
								</div>

								<div class="avg-rating">
									<?php echo $ratingSummary['avg_rating'] !== null
										? escape(number_format($ratingSummary['avg_rating'], 1))
										: '0'; ?>/5
								</div>
							</div>
						</div>
					</section>
				<?php } ?>
			<?php } ?>

			<section class="comment-form-wrap">
				<h2>Add Your Comment</h2>
				<?php if ($formMessage !== '') { ?>
					<p class="form-message"><?php echo escape($formMessage); ?></p>
				<?php } ?>
				<form method="POST" action="detail.php<?php echo $pageType === 'post' ? '?post_id=' . (int) $postId : '?plant_id=' . (int) $plantId; ?><?php echo $currentPageTypeParam === 'community' ? '&type=community' : ''; ?>" class="comment-form">
					<input type="hidden" name="add_comment" value="1">
					<input type="hidden" name="target_type" value="<?php echo escape($pageType); ?>">
					<input type="hidden" name="target_id" value="<?php echo escape($pageType === 'post' ? $postId : $plantId); ?>">
					<input type="hidden" name="return_type" value="<?php echo escape($currentPageTypeParam); ?>">

					<label for="comment_user">Username</label>
					<input id="comment_user" name="comment_user" type="text" maxlength="50" required>

					<label for="comment_text">Comment</label>
					<textarea id="comment_text" name="comment_text" rows="4" maxlength="255" required></textarea>

					<label>Rating</label>
					<div class="rating-input rating-picker" data-target="#comment_rating">
						<button type="button" data-value="1" aria-label="Rate 1">☆</button>
						<button type="button" data-value="2" aria-label="Rate 2">☆</button>
						<button type="button" data-value="3" aria-label="Rate 3">☆</button>
						<button type="button" data-value="4" aria-label="Rate 4">☆</button>
						<button type="button" data-value="5" aria-label="Rate 5">☆</button>
					</div>
					<input type="hidden" id="comment_rating" name="rating" value="0" required>

					<button type="submit" class="comment-submit-btn">Post Comment</button>
				</form>
			</section>

			<section class="comments-modern">
				<h2>Community Thoughts</h2>

				<?php if (!empty($comments)) { ?>
					<?php foreach ($comments as $comment) { ?>
						<div class="comment-modern">

							<img src="https://ui-avatars.com/api/?name=<?php echo urlencode($comment['user_name']); ?>&background=E69B97&color=fff" class="pfp" alt="<?php echo escape($comment['user_name']); ?>">

							<div class="comment-body">
								<div class="comment-top">
									<strong><?php echo escape($comment['user_name']); ?></strong>

									<div class="stars">
										<?php
											$rating = (int)$comment['rating'];
											for ($i=1; $i<=5; $i++) {
												echo $i <= $rating ? '★' : '☆';
											}
										?>
									</div>
								</div>

								<div class="comment-date">
									<?php echo escape($comment['comment_date']); ?>
								</div>

								<p><?php echo escape($comment['comment_text']); ?></p>
							</div>

						</div>
					<?php } ?>
				<?php } else { ?>
					<div class="empty-state-comment">No comments yet for this <?php echo $pageType === 'post' ? 'post' : 'plant'; ?>.</div>
				<?php } ?>
			</section>

			<?php
			$current_page = $currentPageTypeParam;
			
			if($current_page == 'community') {?>
				<section class="post-controls">
					<div class="control-item">
						<form action="edit.php" method="POST" enctype="multipart/form-data">
							<button type="button" class="edit_btn" id="open-edit-modal">Edit Post</button>
						</form>
					</div>

					<div class="control-item">
						<form action="delete.php" method="POST" onsubmit="return confirm('Are you sure you want to delete the post?');">
						<input type="hidden" name="post_id" value="<?php echo $postId;?>">
							<button type="submit" name="delete_btn" class="delete_btn">Delete Post</button>
						</form>
					</div>
				</section>
			<?php }?>
		<?php } ?>
	</div>
	
	<div id="editModalOverlay" class="modal">
        <div class="modal-content">
            <h2>Update Your Post Below</h2>
            <form id="post-form" method="POST" action="edit.php" enctype="multipart/form-data">
				<input type="hidden" name="post_id" value="<?php echo $postId;?>">

                <label for="title">Update Title:</label>
                <input type="text" name="title" value="<?php echo $post['title'];?>" required>

                <label for="user">Update Username:</label>
                <input type="text" name="user" value="<?php echo $post['user_name'];?>" required>

                <label for="body">Update Body:</label>
                <textarea type="text" name="body" required><?php echo $post['body'];?></textarea>

                <label for="post_img" class="post_img">
                    <i class="fa fa-cloud-upload"></i>Update Image:
                </label>
                <input type="file" name="post_img" id="post_img" accept="image/*">
				<input type="hidden" name="existing_img" value="<?php echo $post['post_img'];?>">
				<div class="remove_img">
					<input type="checkbox" name="remove_img" id="remove_img" value="yes"">
					<label for="remove_img">Remove Image</label>
				</div>

                <div id="modal-buttons">
                    <button type="button" id="cancel-post">Cancel</button>
                    <button type="submit" id="submit-post">Submit</button>
                </div>
            </form>
        </div>
    </div>
	
	<script>
		const editBtn = document.querySelector('.edit_btn');
		const editModalOverlay = document.getElementById('editModalOverlay');
		const cancelPostBtn = document.getElementById('cancel-post');

		const fileInput = document.getElementById('post_img');
		const fileLabel = document.querySelector('.post_img');

		const remove = document.getElementById('remove_img');

		editBtn?.addEventListener('click', () => {
			editModalOverlay?.classList.add('open');
		});

		cancelPostBtn?.addEventListener('click', () => {
			editModalOverlay?.classList.remove('open');
		});

		fileInput?.addEventListener('change', function() {
			if (this.files && this.files.length > 0 && fileLabel) {
				fileLabel.innerHTML = '<i class="fa fa-check"></i>' + this.files[0].name;
				fileLabel.style.color = getComputedStyle(document.documentElement).getPropertyValue('--green').trim();
			}
		});

		remove?.addEventListener('change', function() {
			if (!fileInput || !fileLabel) {
				return;
			}

			if (this.checked) {
				fileInput.value = '';
				fileLabel.style.pointerEvents = 'none';
				fileLabel.style.opacity = '0.5';
				fileLabel.innerHTML = '<i class="fa fa-trash"></i>Image will be removed!';
				fileLabel.style.color = getComputedStyle(document.documentElement).getPropertyValue('--berry').trim();
			} else {
				fileLabel.innerHTML = '<i class="fa fa-cloud-upload"></i>Update Image:';
				fileLabel.style.color = '';
				fileLabel.style.pointerEvents = 'auto';
				fileLabel.style.opacity = '1';
			}
		});
	</script>
</body>
</html>